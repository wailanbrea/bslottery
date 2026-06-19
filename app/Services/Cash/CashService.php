<?php

namespace App\Services\Cash;

use App\Models\Branch;
use App\Models\BankTransfer;
use App\Models\CashCountDenomination;
use App\Models\CashFundingTransfer;
use App\Models\CashIncident;
use App\Models\CashMovement;
use App\Models\CashReconciliation;
use App\Models\CashSession;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class CashService
{
    public function open(Branch $branch, User $user, int|float|string $openingAmount, ?string $notes = null): CashSession
    {
        $openingAmount = Money::normalize($openingAmount);

        if (! $branch->cash_control_enabled) {
            throw new \RuntimeException('El control de caja no esta habilitado para esta sucursal.');
        }

        $existingOpen = CashSession::where('branch_id', $branch->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['OPEN', 'REOPENED'])
            ->exists();

        if ($existingOpen) {
            throw new \RuntimeException('Ya tienes una caja abierta (o reabierta) en esta sucursal. Cierrala antes de abrir otra.');
        }

        return CashSession::create([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'opened_by' => $user->id,
            'opening_amount' => $openingAmount,
            'expected_cash' => $openingAmount,
            'status' => 'OPEN',
            'opened_at' => now(),
            'notes' => $notes,
        ]);
    }

    public function recordMovement(
        CashSession $session,
        User $user,
        string $type,
        int|float|string $amount,
        string $direction,
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        string $paymentMethod = 'CASH',
        ?int $bankTransferId = null
    ): CashMovement {
        $amount = Money::normalize($amount);
        $paymentMethod = strtoupper($paymentMethod);

        if (! $session->isOpen()) {
            throw new \RuntimeException('La caja no esta abierta.');
        }

        if (! in_array($paymentMethod, ['CASH', 'BANK_TRANSFER'], true)) {
            throw new \RuntimeException('Metodo de pago invalido.');
        }

        $movement = DB::transaction(function () use ($session, $user, $type, $amount, $direction, $description, $referenceType, $referenceId, $paymentMethod, $bankTransferId): CashMovement {
            $movement = CashMovement::create([
                'company_id' => $session->company_id,
                'branch_id' => $session->branch_id,
                'cash_session_id' => $session->id,
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount,
                'direction' => $direction,
                'payment_method' => $paymentMethod,
                'bank_transfer_id' => $bankTransferId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description ?? "Movimiento de {$type}",
            ]);

            $update = [];
            if ($paymentMethod === 'CASH') {
                match ($type) {
                    'SALE' => $update['sales_total'] = DB::raw("sales_total + {$amount}"),
                    'CANCELLATION' => $update['cancellations_total'] = DB::raw("cancellations_total + {$amount}"),
                    'PRIZE_PAYMENT' => $update['prizes_paid_total'] = DB::raw("prizes_paid_total + {$amount}"),
                    'CASH_IN' => $update['cash_in_total'] = DB::raw("cash_in_total + {$amount}"),
                    'CASH_OUT' => $update['cash_out_total'] = DB::raw("cash_out_total + {$amount}"),
                    'EXPENSE' => $update['expenses_total'] = DB::raw("expenses_total + {$amount}"),
                    'PAYROLL_PAYMENT' => $update['cash_out_total'] = DB::raw("cash_out_total + {$amount}"),
                    default => null,
                };
            }

            if ($update) {
                CashSession::whereKey($session->id)->update($update);
            }

            return $movement;
        });

        $session->refresh();
        $session->recalculateExpectedCash();
        $session->save();

        return $movement;
    }

    /**
     * @param array<string, int|string|null> $denominations
     */
    public function close(CashSession $session, User $closedBy, int|float|string|null $countedCash, ?string $notes = null, array $denominations = []): CashSession
    {
        $countedCash = $this->resolveCountedCash($countedCash, $denominations);

        if (! $session->isOpen()) {
            throw new \RuntimeException('La caja ya esta cerrada.');
        }

        if (BankTransfer::where('cash_session_id', $session->id)->where('status', 'PENDING')->exists()) {
            throw new \RuntimeException('No se puede cerrar la caja con transferencias pendientes de verificacion.');
        }

        return DB::transaction(function () use ($session, $closedBy, $countedCash, $notes, $denominations): CashSession {
            $session->refresh();
            $session->recalculateExpectedCash();

            $difference = Money::subtract($countedCash, $session->expected_cash);
            $shortage = Money::isNegative($difference) ? Money::absolute($difference) : '0.00';
            $surplus = Money::isPositive($difference) ? $difference : '0.00';
            $closedAt = now();

            $session->update([
                'counted_cash' => $countedCash,
                'shortage_amount' => $shortage,
                'surplus_amount' => $surplus,
                'status' => 'CLOSED',
                'closed_by' => $closedBy->id,
                'closed_at' => $closedAt,
                'notes' => $notes ? trim(($session->notes ? $session->notes."\n" : '').$notes) : $session->notes,
            ]);

            $reconciliation = CashReconciliation::updateOrCreate(
                ['cash_session_id' => $session->id],
                [
                    'company_id' => $session->company_id,
                    'branch_id' => $session->branch_id,
                    'closed_by' => $closedBy->id,
                    'opening_amount' => $session->opening_amount,
                    'cash_sales_total' => $session->sales_total,
                    'transfer_sales_total' => $this->movementTotal($session, 'SALE', 'BANK_TRANSFER'),
                    'cash_prizes_total' => $session->prizes_paid_total,
                    'transfer_prizes_total' => $this->movementTotal($session, 'PRIZE_PAYMENT', 'BANK_TRANSFER'),
                    'cash_in_total' => $session->cash_in_total,
                    'cash_out_total' => $session->cash_out_total,
                    'expenses_total' => $session->expenses_total,
                    'cancellations_total' => $session->cancellations_total,
                    'expected_cash' => $session->expected_cash,
                    'counted_cash' => $countedCash,
                    'difference_amount' => $difference,
                    'shortage_amount' => $shortage,
                    'surplus_amount' => $surplus,
                    'status' => ($shortage !== '0.00' || $surplus !== '0.00') ? 'PENDING_REVIEW' : 'MATCHED',
                    'notes' => $notes,
                    'closed_at' => $closedAt,
                ]
            );

            $this->persistDenominations($reconciliation, $denominations);
            $this->createDifferenceIncident($session, $reconciliation, $closedBy, $shortage, $surplus);

            return $session->refresh();
        });
    }

    public function confirm(CashSession $session, User $confirmedBy): CashSession
    {
        if ($session->status !== 'CLOSED') {
            throw new \RuntimeException('Solo se puede confirmar una caja cerrada.');
        }

        $session->update([
            'status' => 'CONFIRMED',
            'confirmed_by' => $confirmedBy->id,
            'confirmed_at' => now(),
        ]);

        $session->reconciliation?->update([
            'status' => 'REVIEWED',
            'reviewed_by' => $confirmedBy->id,
            'reviewed_at' => now(),
        ]);

        return $session;
    }

    public function reopen(CashSession $session, User $user, ?string $notes = null): CashSession
    {
        if (! in_array($session->status, ['CLOSED', 'CONFIRMED'], true)) {
            throw new \RuntimeException('Solo se puede reabrir una caja cerrada o confirmada.');
        }

        CashIncident::create([
            'company_id' => $session->company_id,
            'branch_id' => $session->branch_id,
            'cash_session_id' => $session->id,
            'cash_reconciliation_id' => $session->reconciliation?->id,
            'user_id' => $user->id,
            'type' => 'CASH_REOPENED',
            'severity' => 'WARNING',
            'status' => 'OPEN',
            'title' => 'Reapertura de caja',
            'description' => "Caja #{$session->id} reabierta. Motivo: ".($notes ?: 'No especificado'),
        ]);

        $session->update([
            'status' => 'REOPENED',
            'notes' => $notes ? trim(($session->notes ? $session->notes."\n" : '').'Reapertura: '.$notes) : $session->notes,
        ]);

        return $session;
    }

    public function getActiveSession(int $branchId, int $userId): ?CashSession
    {
        // Una caja "activa" es OPEN o REOPENED. Se prefiere OPEN para que las ventas del dia caigan
        // siempre en la caja abierta y no en una reapertura de correccion que pudiera coexistir.
        return CashSession::where('branch_id', $branchId)
            ->where('user_id', $userId)
            ->whereIn('status', ['OPEN', 'REOPENED'])
            ->orderByRaw("CASE WHEN status = 'OPEN' THEN 0 ELSE 1 END")
            ->orderByDesc('opened_at')
            ->first();
    }

    public function recordExpense(CashSession $session, User $user, int|float|string $amount, string $description): CashMovement
    {
        return $this->recordMovement($session, $user, 'EXPENSE', $amount, 'OUT', $description);
    }

    public function ensureCanPayOut(CashSession $session, int|float|string $amount): void
    {
        $session->refresh();
        $session->recalculateExpectedCash();

        if (Money::isLessThan($session->expected_cash, $amount)) {
            throw new \RuntimeException(
                'Efectivo insuficiente en caja. Disponible estimado: RD$ '
                .number_format((float) $session->expected_cash, 2)
                .'. Solicita al administrador registrar una entrada de efectivo antes de pagar.'
            );
        }
    }

    public function recordCashIn(CashSession $session, User $user, int|float|string $amount, string $description): CashMovement
    {
        return $this->recordMovement($session, $user, 'CASH_IN', $amount, 'IN', $description);
    }

    public function fundCashSession(
        CashSession $session,
        User $user,
        int|float|string $amount,
        ?string $source = null,
        ?string $reference = null,
        ?string $notes = null
    ): CashFundingTransfer {
        $amount = Money::normalize($amount);

        if (! $session->isOpen()) {
            throw new \RuntimeException('Solo se puede reforzar una caja abierta.');
        }

        return DB::transaction(function () use ($session, $user, $amount, $source, $reference, $notes): CashFundingTransfer {
            $session = CashSession::query()
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $session->isOpen()) {
                throw new \RuntimeException('Solo se puede reforzar una caja abierta.');
            }

            $transfer = CashFundingTransfer::create([
                'company_id' => $session->company_id,
                'branch_id' => $session->branch_id,
                'cash_session_id' => $session->id,
                'created_by' => $user->id,
                'amount' => $amount,
                'source' => $source,
                'reference' => $reference,
                'notes' => $notes,
                'status' => 'COMPLETED',
                'completed_at' => now(),
            ]);

            $movement = CashMovement::create([
                'company_id' => $session->company_id,
                'branch_id' => $session->branch_id,
                'cash_session_id' => $session->id,
                'user_id' => $user->id,
                'type' => 'CASH_IN',
                'amount' => $amount,
                'direction' => 'IN',
                'payment_method' => 'CASH',
                'reference_type' => CashFundingTransfer::class,
                'reference_id' => $transfer->id,
                'description' => 'Refuerzo de efectivo'.($reference ? " ({$reference})" : ''),
            ]);

            $transfer->update(['cash_movement_id' => $movement->id]);

            CashSession::whereKey($session->id)->update([
                'cash_in_total' => DB::raw("cash_in_total + {$amount}"),
            ]);

            $session->refresh();
            $session->recalculateExpectedCash();
            $session->save();

            return $transfer->refresh();
        });
    }

    public function recordCashOut(CashSession $session, User $user, int|float|string $amount, string $description): CashMovement
    {
        return $this->recordMovement($session, $user, 'CASH_OUT', $amount, 'OUT', $description);
    }

    /**
     * @return array<string, array{type: string, value: string}>
     */
    public static function denominationMap(): array
    {
        return [
            'bill_2000' => ['type' => 'BILL', 'value' => '2000.00'],
            'bill_1000' => ['type' => 'BILL', 'value' => '1000.00'],
            'bill_500' => ['type' => 'BILL', 'value' => '500.00'],
            'bill_200' => ['type' => 'BILL', 'value' => '200.00'],
            'bill_100' => ['type' => 'BILL', 'value' => '100.00'],
            'bill_50' => ['type' => 'BILL', 'value' => '50.00'],
            'coin_25' => ['type' => 'COIN', 'value' => '25.00'],
            'coin_10' => ['type' => 'COIN', 'value' => '10.00'],
            'coin_5' => ['type' => 'COIN', 'value' => '5.00'],
            'coin_1' => ['type' => 'COIN', 'value' => '1.00'],
        ];
    }

    /**
     * @param array<string, int|string|null> $denominations
     */
    private function resolveCountedCash(int|float|string|null $countedCash, array $denominations): string
    {
        $totalCents = 0;

        foreach (self::denominationMap() as $key => $definition) {
            $quantity = max(0, (int) ($denominations[$key] ?? 0));
            $totalCents += Money::toCents($definition['value']) * $quantity;
        }

        if ($totalCents > 0 || $this->hasDenominationInput($denominations)) {
            return Money::fromCents($totalCents);
        }

        if ($countedCash === null) {
            throw new \RuntimeException('Debe indicar el efectivo contado.');
        }

        return Money::normalize($countedCash);
    }

    /**
     * @param array<string, int|string|null> $denominations
     */
    private function hasDenominationInput(array $denominations): bool
    {
        foreach ($denominations as $quantity) {
            if ((int) $quantity > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, int|string|null> $denominations
     */
    private function persistDenominations(CashReconciliation $reconciliation, array $denominations): void
    {
        foreach (self::denominationMap() as $key => $definition) {
            $quantity = max(0, (int) ($denominations[$key] ?? 0));
            $subtotal = Money::fromCents(Money::toCents($definition['value']) * $quantity);

            CashCountDenomination::updateOrCreate(
                [
                    'cash_reconciliation_id' => $reconciliation->id,
                    'type' => $definition['type'],
                    'denomination' => $definition['value'],
                ],
                [
                    'company_id' => $reconciliation->company_id,
                    'branch_id' => $reconciliation->branch_id,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ]
            );
        }
    }

    private function createDifferenceIncident(CashSession $session, CashReconciliation $reconciliation, User $closedBy, string $shortage, string $surplus): void
    {
        if ($shortage === '0.00' && $surplus === '0.00') {
            return;
        }

        $isShortage = $shortage !== '0.00';
        $amount = $isShortage ? $shortage : $surplus;

        // El responsable es el cajero dueño de la sesion, no quien cierra la caja.
        // Si un admin cierra la caja de un cajero, la incidencia (y el descuento posterior
        // en nomina, ver PayrollService) debe asignarse al cajero responsable.
        // Fallback a $closedBy solo si la sesion no tiene user_id (no deberia pasar en flujo normal).
        $responsibleUserId = $session->user_id ?: $closedBy->id;

        CashIncident::create([
            'company_id' => $session->company_id,
            'branch_id' => $session->branch_id,
            'cash_session_id' => $session->id,
            'cash_reconciliation_id' => $reconciliation->id,
            'user_id' => $responsibleUserId,
            'type' => $isShortage ? 'CASH_SHORTAGE' : 'CASH_SURPLUS',
            'severity' => $isShortage ? 'CRITICAL' : 'WARNING',
            'status' => 'OPEN',
            'title' => $isShortage ? 'Faltante de caja' : 'Sobrante de caja',
            'description' => ($isShortage ? 'Faltante' : 'Sobrante')." detectado en cierre de caja #{$session->id}.",
            'amount' => $amount,
        ]);
    }

    private function movementTotal(CashSession $session, string $type, string $paymentMethod): string
    {
        return Money::normalize(
            CashMovement::query()
                ->where('cash_session_id', $session->id)
                ->where('type', $type)
                ->where('payment_method', $paymentMethod)
                ->sum('amount') ?: '0.00'
        );
    }
}
