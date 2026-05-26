<?php

namespace App\Services\Sales;

use App\Models\BetType;
use App\Models\Branch;
use App\Models\CashSession;
use App\Models\Device;
use App\Models\Draw;
use App\Models\LimitConsumption;
use App\Models\PrinterConfig;
use App\Models\PrintJob;
use App\Models\Ticket;
use App\Models\TicketDetail;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Audit\AuditService;
use App\Services\Cash\CashService;
use App\Services\Lottery\LimitValidationService;
use App\Services\Lottery\PayoutResolverService;
use App\Services\Printing\TicketPrintFormatterService;
use App\Support\Money;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class TicketSaleService
{
    /**
     * @param  array<int, array{bet_type_id: int, number_value: string, amount: int|float|string, position?: string|null}>  $plays
     */
    public function __construct(
        private PayoutResolverService $payoutResolver,
        private LimitValidationService $limitValidator,
        private CashService $cashService,
        private AccountingService $accountingService,
        private TicketPrintFormatterService $ticketPrintFormatter,
    ) {}

    /**
     * Vista previa del ticket sin guardar.
     *
     * @return array{plays: array, total_amount: float, total_possible_prize: float}
     */
    public function preview(Branch $branch, Draw $draw, array $plays): array
    {
        $this->validateSalePreconditions($branch, $draw);

        $result = ['plays' => [], 'total_amount' => 0, 'total_possible_prize' => 0];

        foreach ($plays as $play) {
            $betType = BetType::findOrFail($play['bet_type_id']);
            $position = $play['position'] ?? null;
            $amount = Money::normalize($play['amount']);
            $numberValue = str_pad($play['number_value'], $betType->digits_count, '0', STR_PAD_LEFT);

            if ($betType->max_amount !== null && Money::toFloat($amount) > Money::toFloat($betType->max_amount)) {
                throw new \RuntimeException(
                    'El monto RD$'.number_format(Money::toFloat($amount), 2).' supera el máximo permitido de RD$'.number_format((float) $betType->max_amount, 2)." por jugada de {$betType->name}."
                );
            }

            $payout = $this->payoutResolver->resolve($branch, $draw, $betType, $position);

            $result['plays'][] = [
                'bet_type_id' => $betType->id,
                'bet_type_name' => $betType->name,
                'number_value' => $numberValue,
                'amount' => Money::toFloat($amount),
                'payout_multiplier' => $payout['payout_multiplier'],
                'payout_rule_id' => $payout['payout_rule_id'],
                'possible_prize' => Money::toFloat(Money::multiply($amount, $payout['payout_multiplier'])),
            ];

            $result['total_amount'] += Money::toFloat($amount);
            $result['total_possible_prize'] += Money::toFloat(Money::multiply($amount, $payout['payout_multiplier']));
        }

        return $result;
    }

    /**
     * Ejecuta una venta completa dentro de una transacción.
     *
     * @param  array<int, array{bet_type_id: int, number_value: string, amount: int|float|string, position?: string|null}>  $plays
     */
    public function sell(Branch $branch, Draw $draw, User $user, array $plays, ?Device $device = null, ?CashSession $cashSession = null): Ticket
    {
        // Validaciones pre-transacción
        $this->validateSalePreconditions($branch, $draw);

        if ($branch->cash_control_enabled && ! $cashSession) {
            throw new \RuntimeException('Debe abrir caja antes de vender.');
        }

        if ($cashSession && ! $cashSession->isOpen()) {
            throw new \RuntimeException('La caja no está abierta.');
        }

        // Validar cada jugada y resolver pagos/límites (pre-transacción para usar locks)
        $resolvedPlays = [];
        foreach ($plays as $play) {
            $betType = BetType::where('status', 'ACTIVE')->findOrFail($play['bet_type_id']);
            $position = $play['position'] ?? null;
            $amount = Money::normalize($play['amount']);
            $numberValue = str_pad($play['number_value'], $betType->digits_count, '0', STR_PAD_LEFT);

            if (Money::toFloat($amount) <= 0) {
                throw new \RuntimeException("El monto para {$numberValue} debe ser mayor a 0.");
            }

            if ($betType->max_amount !== null && Money::toFloat($amount) > Money::toFloat($betType->max_amount)) {
                throw new \RuntimeException(
                    'El monto RD$'.number_format(Money::toFloat($amount), 2).' supera el máximo permitido de RD$'.number_format((float) $betType->max_amount, 2)." por jugada de {$betType->name}."
                );
            }

            $payout = $this->payoutResolver->resolve($branch, $draw, $betType, $position);
            $possiblePrize = Money::multiply($amount, $payout['payout_multiplier']);

            $resolvedPlays[] = [
                'bet_type' => $betType,
                'number_value' => $numberValue,
                'amount' => $amount,
                'position' => $position,
                'payout_rule_id' => $payout['payout_rule_id'],
                'payout_multiplier' => $payout['payout_multiplier'],
                'possible_prize' => $possiblePrize,
            ];
        }

        return DB::transaction(function () use ($branch, $draw, $user, $device, $cashSession, $resolvedPlays): Ticket {
            // Validar límites con row locking (dentro de transacción)
            foreach ($resolvedPlays as $index => $play) {
                $validation = $this->limitValidator->validate(
                    branch: $branch,
                    draw: $draw,
                    betType: $play['bet_type'],
                    numberValue: $play['number_value'],
                    requestedAmount: $play['amount'],
                );

                if (! $validation['allowed']) {
                    throw new \RuntimeException($validation['reason']);
                }

                $resolvedPlays[$index]['limit_rule_id'] = $validation['limit_rule_id'];
            }

            // Generar número de ticket
            $ticketNumber = $this->generateTicketNumber($branch);

            // Crear ticket
            $ticket = Ticket::create([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'user_id' => $user->id,
                'device_id' => $device?->id,
                'cash_session_id' => $cashSession?->id,
                'ticket_number' => $ticketNumber,
                'sale_mode' => 'ONLINE',
                'total_amount' => array_sum(array_map(fn (array $play): float => Money::toFloat($play['amount']), $resolvedPlays)),
                'total_possible_prize' => array_sum(array_map(fn (array $play): float => Money::toFloat($play['possible_prize']), $resolvedPlays)),
                'status' => 'ACTIVE',
                'sold_at' => now(),
            ]);

            // Crear detalles
            foreach ($resolvedPlays as $play) {
                TicketDetail::create([
                    'ticket_id' => $ticket->id,
                    'company_id' => $branch->company_id,
                    'branch_id' => $branch->id,
                    'lottery_id' => $draw->lottery_id,
                    'draw_id' => $draw->id,
                    'bet_type_id' => $play['bet_type']->id,
                    'number_value' => $play['number_value'],
                    'normalized_number' => $this->limitValidator->normalizeNumber($play['number_value'], $play['bet_type']->code),
                    'amount' => $play['amount'],
                    'payout_rule_id' => $play['payout_rule_id'],
                    'payout_multiplier' => $play['payout_multiplier'],
                    'possible_prize' => $play['possible_prize'],
                    'limit_rule_id' => $play['limit_rule_id'],
                    'status' => 'ACTIVE',
                ]);
            }

            // Consumir límites
            foreach ($resolvedPlays as $play) {
                $this->limitValidator->consume($branch, $draw, $play['bet_type'], $play['number_value'], $play['amount']);
            }

            // Actualizar caja
            if ($cashSession) {
                $this->cashService->recordMovement(
                    session: $cashSession,
                    user: $user,
                    type: 'SALE',
                    amount: $ticket->total_amount,
                    direction: 'IN',
                    description: "Venta ticket #{$ticket->ticket_number}",
                    referenceType: 'Ticket',
                    referenceId: $ticket->id,
                );
            }

            // Asiento contable
            if ($branch->accounting_enabled) {
                $this->accountingService->entryForSale(
                    companyId: $branch->company_id,
                    branchId: $branch->id,
                    amount: $ticket->total_amount,
                    createdBy: $user,
                    ticketId: $ticket->id,
                );
            }

            $printerConfig = $this->resolvePrinterConfig($branch);

            // Trabajo de impresion
            PrintJob::create([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'ticket_id' => $ticket->id,
                'printer_config_id' => $printerConfig?->id,
                'type' => 'TICKET',
                'content' => $this->ticketPrintFormatter->format($ticket, $printerConfig?->paper_width ?? '58MM'),
                'status' => 'PENDING',
            ]);
            // Auditoría
            app(AuditService::class)->record(
                module: 'Sales',
                action: 'sale',
                auditable: $ticket,
                description: "Venta ticket #{$ticket->ticket_number}. Total: RD\$ ".number_format($ticket->total_amount, 2),
                newValues: [
                    'ticket_number' => $ticket->ticket_number,
                    'total_amount' => $ticket->total_amount,
                    'plays_count' => count($resolvedPlays),
                    'branch' => $branch->name,
                ],
            );

            return $ticket;
        });
    }

    /**
     * Anula un ticket.
     */
    public function cancel(Ticket $ticket, User $cancelledBy, string $reason): Ticket
    {
        if (! $ticket->isCancellable()) {
            throw new \RuntimeException('Este ticket no puede ser anulado. Estado: '.$ticket->status);
        }

        DB::transaction(function () use ($ticket, $cancelledBy, $reason): void {
            $ticket->update([
                'status' => 'CANCELLED',
                'cancelled_at' => now(),
                'cancelled_by' => $cancelledBy->id,
                'cancel_reason' => $reason,
            ]);

            $ticket->details()->update(['status' => 'CANCELLED']);

            // Revertir límites: aumentar cancelled_amount en limit_consumptions
            foreach ($ticket->details as $detail) {
                LimitConsumption::where('company_id', $ticket->company_id)
                    ->where('branch_id', $ticket->branch_id)
                    ->where('lottery_id', $detail->lottery_id)
                    ->where('draw_id', $detail->draw_id)
                    ->where('bet_type_id', $detail->bet_type_id)
                    ->where('number_value', $detail->number_value)
                    ->increment('cancelled_amount', $detail->amount);
            }

            // Registrar movimiento de caja
            if ($ticket->cash_session_id) {
                $session = CashSession::find($ticket->cash_session_id);
                if ($session) {
                    $this->cashService->recordMovement(
                        session: $session,
                        user: $cancelledBy,
                        type: 'CANCELLATION',
                        amount: $ticket->total_amount,
                        direction: 'OUT',
                        description: "Anulación ticket #{$ticket->ticket_number}: {$reason}",
                        referenceType: 'Ticket',
                        referenceId: $ticket->id,
                    );
                }
            }

            app(AuditService::class)->record(
                module: 'Sales',
                action: 'cancel',
                auditable: $ticket,
                description: "Ticket #{$ticket->ticket_number} anulado. Motivo: {$reason}",
            );
        });

        return $ticket->fresh();
    }

    /**
     * Reimprime un ticket.
     */
    public function reprint(Ticket $ticket, User $user): PrintJob
    {
        if (! $ticket->isReprintable()) {
            throw new \RuntimeException('Este ticket no puede ser reimpreso.');
        }

        $ticket->increment('print_count');

        $printerConfig = $this->resolvePrinterConfig($ticket->branch);

        $job = PrintJob::create([
            'company_id' => $ticket->company_id,
            'branch_id' => $ticket->branch_id,
            'ticket_id' => $ticket->id,
            'printer_config_id' => $printerConfig?->id,
            'type' => 'REPRINT',
            'content' => $this->ticketPrintFormatter->format($ticket, $printerConfig?->paper_width ?? '58MM', true),
            'status' => 'PENDING',
        ]);

        app(AuditService::class)->record(
            module: 'Sales',
            action: 'reprint',
            auditable: $ticket,
            description: "Reimpresión #{$ticket->print_count} del ticket #{$ticket->ticket_number}",
        );

        return $job;
    }

    private function validateSalePreconditions(Branch $branch, Draw $draw): void
    {
        if ($branch->status !== 'ACTIVE') {
            throw new \RuntimeException('La sucursal no está activa.');
        }

        if (! $branch->can_sell_online) {
            throw new \RuntimeException('Esta sucursal no tiene habilitada la venta online.');
        }

        if (! $draw->isOpen()) {
            throw new \RuntimeException('El sorteo no está abierto. Estado: '.$draw->status);
        }

        $timezone = $branch->company?->timezone ?: config('app.timezone', 'America/Santo_Domingo');
        $now = CarbonImmutable::now($timezone);
        $openAt = CarbonImmutable::parse(
            $draw->draw_date->toDateString().' '.($draw->open_time ?: '00:00'),
            $timezone,
        );
        $closeAt = CarbonImmutable::parse(
            $draw->draw_date->toDateString().' '.($draw->close_time ?: '23:59:59'),
            $timezone,
        );

        if ($now->lt($openAt)) {
            throw new \RuntimeException('El sorteo todavia no esta abierto. Apertura: '.($draw->open_time ?: '00:00'));
        }
        if ($now->gt($closeAt)) {
            throw new \RuntimeException('El sorteo ya paso su hora de cierre: '.($draw->close_time ?: '23:59'));
        }

        if ($draw->draw_date->isToday() && $draw->open_time && now()->format('H:i') < $draw->open_time) {
            throw new \RuntimeException('El sorteo todavia no esta abierto. Apertura: '.$draw->open_time);
        }
        if ($draw->draw_date->isToday() && $draw->close_time && now()->format('H:i') > $draw->close_time) {
            throw new \RuntimeException('El sorteo ya pasó su hora de cierre: '.$draw->close_time);
        }

        $lottery = $draw->lottery;
        if ($lottery && $lottery->status !== 'ACTIVE') {
            throw new \RuntimeException('La lotería no está activa.');
        }
    }

    private function generateTicketNumber(Branch $branch): string
    {
        $prefix = $branch->code.'-'.now()->format('ymd');
        $count = Ticket::where('company_id', $branch->company_id)
            ->where('branch_id', $branch->id)
            ->where('ticket_number', 'like', "{$prefix}-%")
            ->lockForUpdate()
            ->count();

        return sprintf('%s-%04d', $prefix, $count + 1);
    }

    /**
     * Venta originada desde la app móvil Android.
     * No requiere caja abierta (el control de caja aplica solo al web).
     *
     * @param  array<int, array{bet_type_id: int, number_value: string, amount: int|float|string}>  $details
     */
    public function sellFromMobile(User $user, int $drawId, array $details, string $uuid, ?string $soldAt = null, ?Device $device = null): Ticket
    {
        $draw = Draw::with('lottery')
            ->where('company_id', $user->company_id)
            ->findOrFail($drawId);
        $branch = $user->branch;

        if (! $branch) {
            throw new \RuntimeException('El usuario movil no tiene sucursal asignada.');
        }

        $this->validateSalePreconditions($branch, $draw);

        $cashSession = null;
        if ($branch->cash_control_enabled) {
            $cashSession = $this->cashService->getActiveSession($branch->id, $user->id);
            if (! $cashSession) {
                throw new \RuntimeException('Debe abrir caja antes de vender desde Android.');
            }
        }

        $resolvedPlays = [];
        foreach ($details as $detail) {
            $betType = BetType::where('status', 'ACTIVE')->findOrFail($detail['bet_type_id']);
            $amount = Money::normalize($detail['amount']);
            $numberValue = str_pad((string) $detail['number_value'], $betType->digits_count, '0', STR_PAD_LEFT);

            $payout = $this->payoutResolver->resolve($branch, $draw, $betType, null);

            $resolvedPlays[] = [
                'bet_type' => $betType,
                'number_value' => $numberValue,
                'amount' => $amount,
                'payout_rule_id' => $payout['payout_rule_id'],
                'payout_multiplier' => $payout['payout_multiplier'],
                'possible_prize' => Money::multiply($amount, $payout['payout_multiplier']),
            ];
        }

        return DB::transaction(function () use ($branch, $draw, $user, $uuid, $soldAt, $device, $cashSession, $resolvedPlays): Ticket {
            foreach ($resolvedPlays as $index => $play) {
                $validation = $this->limitValidator->validate(
                    branch: $branch,
                    draw: $draw,
                    betType: $play['bet_type'],
                    numberValue: $play['number_value'],
                    requestedAmount: $play['amount'],
                );
                if (! $validation['allowed']) {
                    throw new \RuntimeException($validation['reason']);
                }
                $resolvedPlays[$index]['limit_rule_id'] = $validation['limit_rule_id'];
            }

            $ticketNumber = $this->generateTicketNumber($branch);

            $ticket = Ticket::create([
                'uuid' => $uuid,
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'user_id' => $user->id,
                'device_id' => $device?->id,
                'cash_session_id' => $cashSession?->id,
                'ticket_number' => $ticketNumber,
                'sale_mode' => 'MOBILE',
                'total_amount' => array_sum(array_map(fn (array $p): float => Money::toFloat($p['amount']), $resolvedPlays)),
                'total_possible_prize' => array_sum(array_map(fn (array $p): float => Money::toFloat($p['possible_prize']), $resolvedPlays)),
                'status' => 'ACTIVE',
                'sold_at' => $soldAt ? Carbon::parse($soldAt) : now(),
            ]);

            foreach ($resolvedPlays as $play) {
                TicketDetail::create([
                    'ticket_id' => $ticket->id,
                    'company_id' => $branch->company_id,
                    'branch_id' => $branch->id,
                    'lottery_id' => $draw->lottery_id,
                    'draw_id' => $draw->id,
                    'bet_type_id' => $play['bet_type']->id,
                    'number_value' => $play['number_value'],
                    'normalized_number' => $this->limitValidator->normalizeNumber($play['number_value'], $play['bet_type']->code),
                    'amount' => $play['amount'],
                    'payout_rule_id' => $play['payout_rule_id'],
                    'payout_multiplier' => $play['payout_multiplier'],
                    'possible_prize' => $play['possible_prize'],
                    'limit_rule_id' => $play['limit_rule_id'],
                    'status' => 'ACTIVE',
                ]);
            }

            foreach ($resolvedPlays as $play) {
                $this->limitValidator->consume($branch, $draw, $play['bet_type'], $play['number_value'], $play['amount']);
            }

            if ($cashSession) {
                $this->cashService->recordMovement(
                    session: $cashSession,
                    user: $user,
                    type: 'SALE',
                    amount: $ticket->total_amount,
                    direction: 'IN',
                    description: "Venta movil ticket #{$ticket->ticket_number}",
                    referenceType: 'Ticket',
                    referenceId: $ticket->id,
                );
            }

            if ($branch->accounting_enabled) {
                $this->accountingService->entryForSale(
                    companyId: $branch->company_id,
                    branchId: $branch->id,
                    amount: $ticket->total_amount,
                    createdBy: $user,
                    ticketId: $ticket->id,
                );
            }

            $printerConfig = $this->resolvePrinterConfig($branch);

            PrintJob::create([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'ticket_id' => $ticket->id,
                'printer_config_id' => $printerConfig?->id,
                'device_id' => $device?->id,
                'type' => 'TICKET',
                'content' => $this->ticketPrintFormatter->format($ticket, $printerConfig?->paper_width ?? '58MM'),
                'status' => 'PENDING',
            ]);

            app(AuditService::class)->record(
                module: 'Mobile',
                action: 'sale',
                auditable: $ticket,
                description: "Venta móvil ticket #{$ticket->ticket_number}. Total: RD\$ ".number_format($ticket->total_amount, 2),
            );

            return $ticket;
        });
    }

    private function resolvePrinterConfig(Branch $branch): ?PrinterConfig
    {
        if ($branch->default_printer_id) {
            $defaultPrinter = PrinterConfig::whereKey($branch->default_printer_id)
                ->where('company_id', $branch->company_id)
                ->where('status', 'ACTIVE')
                ->first();

            if ($defaultPrinter) {
                return $defaultPrinter;
            }
        }

        return PrinterConfig::where('company_id', $branch->company_id)
            ->where('status', 'ACTIVE')
            ->where(function ($query) use ($branch): void {
                $query->where('branch_id', $branch->id)->orWhereNull('branch_id');
            })
            ->orderByRaw('CASE WHEN branch_id = ? THEN 0 ELSE 1 END', [$branch->id])
            ->orderBy('id')
            ->first();
    }
}
