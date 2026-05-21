<?php

namespace App\Services\Results;

use App\Models\CashSession;
use App\Models\PrizePayment;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WinnerTicket;
use App\Services\Accounting\AccountingService;
use App\Services\Audit\AuditService;
use App\Services\Cash\CashService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class PrizePaymentService
{
    public function __construct(
        private CashService $cashService,
        private AccountingService $accountingService,
    ) {}

    /**
     * Paga un premio a un ganador.
     */
    public function pay(WinnerTicket $winner, User $paidBy, ?CashSession $cashSession = null): PrizePayment
    {
        if ($winner->status !== 'RELEASED') {
            throw new \RuntimeException('El premio no está liberado para pago. Estado: ' . $winner->status);
        }

        if ($winner->paid_at) {
            throw new \RuntimeException('Este premio ya fue pagado.');
        }

        if (! $cashSession || ! $cashSession->isOpen()) {
            throw new \RuntimeException('Debe abrir caja antes de pagar premios.');
        }

        return DB::transaction(function () use ($winner, $paidBy, $cashSession): PrizePayment {
            $cashSession = CashSession::whereKey($cashSession->id)->lockForUpdate()->firstOrFail();
            $this->cashService->ensureCanPayOut($cashSession, $winner->prize_amount);

            $payment = PrizePayment::create([
                'company_id' => $winner->company_id,
                'branch_id' => $winner->branch_id,
                'ticket_id' => $winner->ticket_id,
                'winner_ticket_id' => $winner->id,
                'cash_session_id' => $cashSession?->id,
                'amount' => $winner->prize_amount,
                'paid_by' => $paidBy->id,
                'paid_at' => now(),
                'status' => 'PAID',
            ]);

            $winner->update([
                'status' => 'PAID',
                'paid_at' => now(),
                'paid_by' => $paidBy->id,
            ]);

            // Actualizar ticket
            $winner->ticket->update(['paid_at' => now()]);
            $winner->ticketDetail->update(['status' => 'PAID']);

            // Verificar si todas las jugadas del ticket están pagadas
            $allPaid = $winner->ticket->details()->where('status', '!=', 'PAID')->where('status', '!=', 'LOSER')->where('status', '!=', 'CANCELLED')->doesntExist();
            if ($allPaid) {
                $winner->ticket->update(['status' => 'PAID']);
            }

            // Movimiento de caja obligatorio.
            $this->cashService->recordMovement(
                session: $cashSession,
                user: $paidBy,
                type: 'PRIZE_PAYMENT',
                amount: $winner->prize_amount,
                direction: 'OUT',
                description: "Pago premio ticket #{$winner->ticket->ticket_number} — {$winner->number_value}",
                referenceType: 'PrizePayment',
                referenceId: $payment->id,
            );

            // Asiento contable
            $this->accountingService->entryForPrizePayment(
                companyId: $winner->company_id,
                branchId: $winner->branch_id,
                amount: $winner->prize_amount,
                createdBy: $paidBy,
                prizePaymentId: $payment->id,
            );

            app(AuditService::class)->record(
                module: 'Prizes',
                action: 'paid',
                auditable: $payment,
                description: "Premio pagado: RD\$ " . number_format($winner->prize_amount, 2)
                    . " — Ticket #{$winner->ticket->ticket_number}"
                    . " — {$winner->number_value} ({$winner->matched_position})",
            );

            return $payment;
        });
    }

    /**
     * @return array<int, PrizePayment>
     */
    public function payReleasedWinnersForTicket(Ticket $ticket, User $paidBy, ?CashSession $cashSession = null): array
    {
        if (! $cashSession || ! $cashSession->isOpen()) {
            throw new \RuntimeException('Debe abrir caja antes de pagar premios.');
        }

        $ticket->loadMissing(['winnerTickets' => fn ($query) => $query->where('status', 'RELEASED')]);

        $winners = $ticket->winnerTickets;

        if ($winners->isEmpty()) {
            throw new \RuntimeException('Este ticket no tiene premios liberados pendientes de pago.');
        }

        $total = '0.00';
        foreach ($winners as $winner) {
            $total = Money::add($total, $winner->prize_amount);
        }

        return DB::transaction(function () use ($winners, $paidBy, $cashSession, $total): array {
            $cashSession = CashSession::whereKey($cashSession->id)->lockForUpdate()->firstOrFail();
            $this->cashService->ensureCanPayOut($cashSession, $total);

            $payments = [];
            foreach ($winners as $winner) {
                $payments[] = $this->pay($winner->refresh(), $paidBy, $cashSession);
            }

            return $payments;
        });
    }
}

