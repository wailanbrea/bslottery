<?php

namespace App\Services\Cash;

use App\Models\BankTransfer;
use App\Models\CashSession;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class BankTransferService
{
    public const MOVEMENT_TYPES = [
        'SALE' => ['label' => 'Venta por transferencia', 'direction' => 'IN'],
        'PRIZE_PAYMENT' => ['label' => 'Premio por transferencia', 'direction' => 'OUT'],
        'CASH_IN' => ['label' => 'Entrada por transferencia', 'direction' => 'IN'],
        'CASH_OUT' => ['label' => 'Salida por transferencia', 'direction' => 'OUT'],
        'EXPENSE' => ['label' => 'Gasto por transferencia', 'direction' => 'OUT'],
    ];

    public function __construct(
        private CashService $cashService,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function createPending(CashSession $session, User $user, array $data): BankTransfer
    {
        $movementType = strtoupper((string) $data['movement_type']);

        if (! isset(self::MOVEMENT_TYPES[$movementType])) {
            throw new \RuntimeException('Tipo de transferencia invalido.');
        }

        return BankTransfer::create([
            'company_id' => $session->company_id,
            'branch_id' => $session->branch_id,
            'cash_session_id' => $session->id,
            'movement_type' => $movementType,
            'user_id' => $user->id,
            'bank_name' => trim((string) $data['bank_name']),
            'reference' => trim((string) $data['reference']),
            'amount' => Money::normalize($data['amount']),
            'direction' => self::MOVEMENT_TYPES[$movementType]['direction'],
            'status' => 'PENDING',
            'transferred_at' => $data['transferred_at'] ?? now(),
            'evidence_path' => $data['evidence_path'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function confirm(BankTransfer $transfer, User $verifiedBy): BankTransfer
    {
        if ($transfer->status !== 'PENDING') {
            throw new \RuntimeException('Solo se pueden confirmar transferencias pendientes.');
        }

        $session = $transfer->cashSession;

        if (! $session || ! $session->isOpen()) {
            throw new \RuntimeException('La transferencia requiere una caja abierta para confirmarse.');
        }

        return DB::transaction(function () use ($transfer, $verifiedBy, $session): BankTransfer {
            $transfer->update([
                'status' => 'CONFIRMED',
                'verified_by' => $verifiedBy->id,
                'verified_at' => now(),
            ]);

            $this->cashService->recordMovement(
                session: $session,
                user: $verifiedBy,
                type: $transfer->movement_type,
                amount: $transfer->amount,
                direction: $transfer->direction,
                description: "Transferencia confirmada {$transfer->bank_name} / Ref. {$transfer->reference}",
                referenceType: 'BankTransfer',
                referenceId: $transfer->id,
                paymentMethod: 'BANK_TRANSFER',
                bankTransferId: $transfer->id,
            );

            return $transfer->refresh();
        });
    }

    public function reject(BankTransfer $transfer, User $verifiedBy, ?string $notes = null): BankTransfer
    {
        if ($transfer->status !== 'PENDING') {
            throw new \RuntimeException('Solo se pueden rechazar transferencias pendientes.');
        }

        $transfer->update([
            'status' => 'REJECTED',
            'verified_by' => $verifiedBy->id,
            'verified_at' => now(),
            'notes' => $notes ? trim(($transfer->notes ? $transfer->notes."\n" : '').'Rechazo: '.$notes) : $transfer->notes,
        ]);

        return $transfer;
    }
}
