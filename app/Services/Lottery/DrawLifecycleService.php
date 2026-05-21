<?php

namespace App\Services\Lottery;

use App\Models\Branch;
use App\Models\Draw;
use App\Models\LimitConsumption;
use App\Models\Ticket;
use App\Models\TicketDetail;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Sales\TicketSaleService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DrawLifecycleService
{
    public const POLICY_NONE = 'NONE';

    public const POLICY_KEEP_CURRENT = 'KEEP_CURRENT';

    public const POLICY_TRANSFER_NEXT = 'TRANSFER_NEXT';

    public const POLICY_CANCEL_TICKETS = 'CANCEL_TICKETS';

    public function __construct(
        private TicketSaleService $ticketSaleService,
        private LimitValidationService $limitValidationService,
        private AuditService $auditService,
    ) {}

    public function close(Draw $draw, User $user, string $ticketPolicy = self::POLICY_NONE, ?string $reason = null): Draw
    {
        return $this->resolveAndTransition(
            draw: $draw,
            user: $user,
            targetStatus: 'CLOSED',
            ticketPolicy: $ticketPolicy,
            reason: $reason,
        );
    }

    public function cancel(Draw $draw, User $user, string $ticketPolicy, string $reason): Draw
    {
        return $this->resolveAndTransition(
            draw: $draw,
            user: $user,
            targetStatus: 'CANCELLED',
            ticketPolicy: $ticketPolicy,
            reason: $reason,
        );
    }

    private function resolveAndTransition(
        Draw $draw,
        User $user,
        string $targetStatus,
        string $ticketPolicy,
        ?string $reason,
    ): Draw {
        if (! $draw->isOpen()) {
            throw new \RuntimeException('Solo se pueden cerrar o cancelar sorteos abiertos.');
        }

        $activeTickets = $this->activeTicketsForDraw($draw);

        if ($activeTickets->isNotEmpty() && $ticketPolicy === self::POLICY_NONE) {
            $ticketPolicy = $targetStatus === 'CLOSED' ? self::POLICY_KEEP_CURRENT : self::POLICY_NONE;
        }

        if ($activeTickets->isNotEmpty() && $ticketPolicy === self::POLICY_NONE) {
            throw new \RuntimeException('Este sorteo tiene tickets activos. Debe decidir si transferirlos o anularlos.');
        }

        if ($activeTickets->isEmpty()) {
            $ticketPolicy = self::POLICY_NONE;
        }

        if (! in_array($ticketPolicy, [self::POLICY_NONE, self::POLICY_KEEP_CURRENT, self::POLICY_TRANSFER_NEXT, self::POLICY_CANCEL_TICKETS], true)) {
            throw new \RuntimeException('Política de tickets inválida.');
        }

        if ($targetStatus === 'CANCELLED' && blank($reason)) {
            throw new \RuntimeException('Debe indicar el motivo de cancelación.');
        }

        return DB::transaction(function () use ($draw, $user, $targetStatus, $ticketPolicy, $reason, $activeTickets): Draw {
            $draw = Draw::query()->lockForUpdate()->findOrFail($draw->id);
            $oldValues = $draw->toArray();
            $targetDraw = null;

            if ($ticketPolicy === self::POLICY_TRANSFER_NEXT) {
                $targetDraw = $this->nextDrawFor($draw);
                $this->transferTicketsToDraw($draw, $targetDraw, $user);
            }

            if ($ticketPolicy === self::POLICY_CANCEL_TICKETS) {
                $this->cancelTickets($activeTickets, $user, $reason ?: "Sorteo {$draw->name} {$targetStatus}.");
            }

            $draw->update([
                'status' => $targetStatus,
                'closed_at' => $targetStatus === 'CLOSED' ? now() : $draw->closed_at,
                'cancelled_at' => $targetStatus === 'CANCELLED' ? now() : $draw->cancelled_at,
                'cancelled_by' => $targetStatus === 'CANCELLED' ? $user->id : $draw->cancelled_by,
                'cancel_reason' => $targetStatus === 'CANCELLED' ? $reason : $draw->cancel_reason,
                'ticket_resolution_policy' => $ticketPolicy,
                'transferred_to_draw_id' => $targetDraw?->id,
            ]);

            $this->auditService->record(
                module: 'Draw',
                action: strtolower($targetStatus),
                auditable: $draw,
                description: $this->auditDescription($draw, $targetStatus, $ticketPolicy, $activeTickets->count(), $targetDraw),
                oldValues: $oldValues,
                newValues: [
                    'status' => $targetStatus,
                    'ticket_resolution_policy' => $ticketPolicy,
                    'tickets_affected' => $activeTickets->count(),
                    'transferred_to_draw_id' => $targetDraw?->id,
                    'reason' => $reason,
                ],
            );

            return $draw->fresh(['lottery', 'transferredToDraw']);
        });
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function activeTicketsForDraw(Draw $draw): Collection
    {
        return Ticket::query()
            ->where('company_id', $draw->company_id)
            ->where('status', 'ACTIVE')
            ->whereHas('details', fn ($query) => $query
                ->where('draw_id', $draw->id)
                ->where('status', 'ACTIVE'))
            ->with(['details' => fn ($query) => $query->where('status', 'ACTIVE')])
            ->get();
    }

    private function nextDrawFor(Draw $draw): Draw
    {
        $nextDraw = Draw::query()
            ->where('company_id', $draw->company_id)
            ->where('lottery_id', $draw->lottery_id)
            ->where('status', 'OPEN')
            ->where('id', '!=', $draw->id)
            ->where(function ($query) use ($draw): void {
                $query->whereDate('draw_date', '>', $draw->draw_date)
                    ->orWhere(function ($query) use ($draw): void {
                        $query->whereDate('draw_date', $draw->draw_date)
                            ->where('scheduled_time', '>', $draw->scheduled_time);
                    });
            })
            ->orderBy('draw_date')
            ->orderBy('scheduled_time')
            ->first();

        if ($nextDraw) {
            return $nextDraw;
        }

        return Draw::create([
            'company_id' => $draw->company_id,
            'lottery_id' => $draw->lottery_id,
            'name' => $draw->name,
            'draw_date' => $draw->draw_date->copy()->addDay()->toDateString(),
            'open_time' => $draw->open_time ?? '00:00',
            'scheduled_time' => $draw->scheduled_time,
            'close_time' => $draw->close_time,
            'status' => 'OPEN',
        ]);
    }

    private function transferTicketsToDraw(Draw $sourceDraw, Draw $targetDraw, User $user): void
    {
        Ticket::query()
            ->where('company_id', $sourceDraw->company_id)
            ->where('status', 'ACTIVE')
            ->whereHas('details', fn ($query) => $query
                ->where('draw_id', $sourceDraw->id)
                ->where('status', 'ACTIVE'))
            ->with(['details' => fn ($query) => $query->where('status', 'ACTIVE')])
            ->get()
            ->each(function (Ticket $ticket) use ($sourceDraw, $targetDraw, $user): void {
                $otherActiveDetails = $ticket->details->where('draw_id', '!=', $sourceDraw->id);

                if ($otherActiveDetails->isNotEmpty()) {
                    throw new \RuntimeException("El ticket #{$ticket->ticket_number} tiene jugadas activas en más de un sorteo. Anúlelo manualmente o divida el flujo.");
                }

                $ticket->details
                    ->where('draw_id', $sourceDraw->id)
                    ->each(function (TicketDetail $detail) use ($sourceDraw, $targetDraw, $user): void {
                        $this->markOldLimitAsCancelled($detail);
                        $branch = Branch::findOrFail($detail->branch_id);
                        $this->limitValidationService->consume(
                            branch: $branch,
                            draw: $targetDraw,
                            betType: $detail->betType,
                            numberValue: $detail->number_value,
                            amount: (float) $detail->amount,
                        );

                        $detail->update([
                            'lottery_id' => $targetDraw->lottery_id,
                            'draw_id' => $targetDraw->id,
                            'transferred_from_draw_id' => $sourceDraw->id,
                            'transferred_at' => now(),
                            'transferred_by' => $user->id,
                        ]);
                    });
            });
    }

    /**
     * @param Collection<int, Ticket> $tickets
     */
    private function cancelTickets(Collection $tickets, User $user, string $reason): void
    {
        $tickets->each(function (Ticket $ticket) use ($user, $reason): void {
            $this->ticketSaleService->cancel($ticket, $user, $reason);
        });
    }

    private function markOldLimitAsCancelled(TicketDetail $detail): void
    {
        LimitConsumption::query()
            ->where('company_id', $detail->company_id)
            ->where('branch_id', $detail->branch_id)
            ->where('lottery_id', $detail->lottery_id)
            ->where('draw_id', $detail->draw_id)
            ->where('bet_type_id', $detail->bet_type_id)
            ->where('number_value', $detail->number_value)
            ->increment('cancelled_amount', $detail->amount);
    }

    /**
     * @param Collection<int, Ticket> $tickets
     */
    private function auditDescription(Draw $draw, string $targetStatus, string $policy, int $ticketCount, ?Draw $targetDraw): string
    {
        $action = $targetStatus === 'CANCELLED' ? 'cancelado' : 'cerrado';
        $description = "Sorteo {$draw->name} {$action}. Política de tickets: {$policy}. Tickets afectados: {$ticketCount}.";

        if ($targetDraw) {
            $description .= " Transferidos al sorteo {$targetDraw->name} del {$targetDraw->draw_date->format('Y-m-d')}.";
        }

        return $description;
    }
}
