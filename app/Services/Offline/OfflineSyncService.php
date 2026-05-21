<?php

declare(strict_types=1);

namespace App\Services\Offline;

use App\Models\Branch;
use App\Models\Draw;
use App\Models\OfflineLimitAllocation;
use App\Models\OfflineSession;
use App\Models\SyncBatch;
use App\Models\SyncConflict;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Lottery\LimitValidationService;
use App\Services\Sales\TicketSaleService;
use Illuminate\Support\Facades\DB;

class OfflineSyncService
{
    public function __construct(
        private readonly TicketSaleService $saleService,
        private readonly LimitValidationService $limitValidator,
    ) {}

    /**
     * Opens an offline session for a device/user.
     * The session starts as PENDING until authorized by a manager (or auto-authorized if permitted).
     */
    public function openSession(
        User $user,
        Branch $branch,
        ?int $deviceId,
        int $ticketLimit = 100,
        ?string $amountLimit = null,
        int $expiresInHours = 8,
    ): OfflineSession {
        return DB::transaction(function () use ($user, $branch, $deviceId, $ticketLimit, $amountLimit, $expiresInHours): OfflineSession {
            $session = OfflineSession::create([
                'company_id'              => $branch->company_id,
                'branch_id'               => $branch->id,
                'user_id'                 => $user->id,
                'device_id'               => $deviceId,
                'status'                  => 'ACTIVE',
                'opened_at'               => now(),
                'expires_at'              => now()->addHours($expiresInHours),
                'allocated_tickets_limit' => $ticketLimit,
                'allocated_amount'        => $amountLimit,
                'authorized_by'           => $user->id,
                'authorized_at'           => now(),
            ]);

            if ($amountLimit !== null) {
                OfflineLimitAllocation::create([
                    'offline_session_id' => $session->id,
                    'company_id' => $branch->company_id,
                    'branch_id' => $branch->id,
                    'lottery_id' => null,
                    'draw_id' => null,
                    'bet_type_id' => null,
                    'number_value' => null,
                    'allocated_amount' => $this->centsToMoney($this->moneyToCents($amountLimit)),
                    'used_amount' => '0.00',
                ]);
            }

            return $session;
        });
    }

    /**
     * Builds the bootstrap data package for offline operation.
     */
    public function buildBootstrap(OfflineSession $session): array
    {
        $session->load(['branch.company']);
        $branch = $session->branch;
        $companyId = $branch->company_id;

        $lotteries = \App\Models\Lottery::where('status', 'ACTIVE')
            ->where(fn ($q) => $q->where('company_id', $companyId)->orWhereNull('company_id'))
            ->get(['id', 'name', 'code', 'status']);

        $draws = Draw::with('lottery')
            ->where('company_id', $companyId)
            ->where('status', 'OPEN')
            ->where('draw_date', '>=', now()->toDateString())
            ->orderBy('draw_date')
            ->orderBy('scheduled_time')
            ->get();

        $betTypes = \App\Models\BetType::where('status', 'ACTIVE')
            ->where(fn ($q) => $q->where('company_id', $companyId)->orWhereNull('company_id'))
            ->get(['id', 'name', 'code', 'digits_count', 'min_numbers', 'max_numbers', 'numbers_count', 'requires_position', 'is_cross_lottery', 'max_amount']);

        $payoutRules = \App\Models\PayoutRule::where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->get(['id', 'bet_type_id', 'lottery_id', 'branch_id', 'multiplier', 'position']);

        return [
            'session'      => $session->only(['uuid', 'status', 'expires_at', 'allocated_tickets_limit', 'used_tickets_count', 'allocated_amount']),
            'branch'       => $branch->only(['id', 'name', 'code']),
            'lotteries'    => $lotteries,
            'draws'        => $draws,
            'bet_types'    => $betTypes,
            'payout_rules' => $payoutRules,
            'allocations'  => $session->allocations()
                ->get(['id', 'lottery_id', 'draw_id', 'bet_type_id', 'number_value', 'allocated_amount', 'used_amount']),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Processes a sync batch: accepts valid tickets, creates conflicts for invalid ones.
     */
    public function processBatch(OfflineSession $session, array $tickets, ?string $payloadHash = null): SyncBatch
    {
        return DB::transaction(function () use ($session, $tickets, $payloadHash): SyncBatch {
            $branch = $session->branch;
            $user = $session->user;

            $batch = SyncBatch::create([
                'offline_session_id' => $session->id,
                'company_id'         => $session->company_id,
                'branch_id'          => $session->branch_id,
                'device_id'          => $session->device_id,
                'status'             => 'PROCESSING',
                'submitted_at'       => now(),
                'total_tickets'      => count($tickets),
                'payload_hash'       => $payloadHash,
            ]);

            $accepted = 0;
            $rejected = 0;
            $acceptedUuids = [];
            $failed = [];
            $genericAllocation = $session->allocations()
                ->whereNull('lottery_id')
                ->whereNull('draw_id')
                ->whereNull('bet_type_id')
                ->whereNull('number_value')
                ->lockForUpdate()
                ->first();

            foreach ($tickets as $ticketData) {
                $conflict = $this->processSingleTicket($batch, $session, $branch, $user, $ticketData, $genericAllocation, $accepted);

                if ($conflict === null) {
                    $accepted++;
                    $acceptedUuids[] = $ticketData['uuid'];
                } else {
                    $rejected++;
                    $failed[] = [
                        'uuid' => $ticketData['uuid'] ?? null,
                        'reason' => $conflict->conflict_reason,
                        'conflict_id' => $conflict->id,
                    ];
                }
            }

            $batch->update([
                'status'           => $rejected === 0 ? 'COMPLETED' : ($accepted > 0 ? 'PARTIAL' : 'FAILED'),
                'processed_at'     => now(),
                'accepted_tickets' => $accepted,
                'rejected_tickets' => $rejected,
            ]);

            $session->increment('used_tickets_count', $accepted);
            $batch->setAttribute('accepted_uuids', $acceptedUuids);
            $batch->setAttribute('failed', $failed);

            return $batch->load('conflicts');
        });
    }

    private function processSingleTicket(
        SyncBatch $batch,
        OfflineSession $session,
        Branch $branch,
        User $user,
        array $ticketData,
        ?OfflineLimitAllocation $genericAllocation,
        int $acceptedInCurrentBatch,
    ): ?SyncConflict {
        try {
            if (! empty($ticketData['uuid']) && Ticket::where('uuid', $ticketData['uuid'])->exists()) {
                return null;
            }

            $draw = Draw::find($ticketData['draw_id'] ?? null);

            if (! $draw) {
                return $this->conflict($batch, $session, $ticketData, 'VALIDATION_ERROR', 'Sorteo no encontrado.');
            }

            if ($draw->status !== 'OPEN') {
                return $this->conflict($batch, $session, $ticketData, 'DRAW_CLOSED', "Sorteo {$draw->name} ya cerró.");
            }

            if ($session->allocated_tickets_limit > 0 && ($session->used_tickets_count + $acceptedInCurrentBatch) >= $session->allocated_tickets_limit) {
                return $this->conflict($batch, $session, $ticketData, 'VALIDATION_ERROR', 'Cupo de tickets offline agotado.');
            }

            $plays = $ticketData['plays'] ?? [];
            $ticketAmountCents = $this->ticketAmountCents($plays);

            if ($genericAllocation) {
                $allocatedCents = $this->moneyToCents($genericAllocation->allocated_amount);
                $usedCents = $this->moneyToCents($genericAllocation->used_amount);

                if (($usedCents + $ticketAmountCents) > $allocatedCents) {
                    return $this->conflict($batch, $session, $ticketData, 'LIMIT_EXCEEDED', 'Cupo de monto offline agotado.');
                }
            }

            $details = array_map(fn (array $play): array => [
                'bet_type_id' => $play['bet_type_id'],
                'number_value' => $play['number_value'],
                'amount' => $play['amount'],
            ], $plays);

            $this->saleService->sellFromMobile(
                user: $user,
                drawId: (int) $ticketData['draw_id'],
                details: $details,
                uuid: $ticketData['uuid'],
                soldAt: $ticketData['sold_at'] ?? null,
            );

            if ($genericAllocation) {
                $genericAllocation->forceFill([
                    'used_amount' => $this->centsToMoney($this->moneyToCents($genericAllocation->used_amount) + $ticketAmountCents),
                ])->save();
            }

            return null;
        } catch (\Throwable $e) {
            $type = str_contains($e->getMessage(), 'límite') ? 'LIMIT_EXCEEDED' : 'VALIDATION_ERROR';

            return $this->conflict($batch, $session, $ticketData, $type, $e->getMessage());
        }
    }

    private function conflict(SyncBatch $batch, OfflineSession $session, array $ticketData, string $type, string $reason): SyncConflict
    {
        return SyncConflict::create([
            'sync_batch_id'      => $batch->id,
            'offline_session_id' => $session->id,
            'company_id'         => $session->company_id,
            'branch_id'          => $session->branch_id,
            'conflict_type'      => $type,
            'status'             => 'PENDING',
            'ticket_data'        => $ticketData,
            'conflict_reason'    => $reason,
        ]);
    }

    public function resolveConflict(SyncConflict $conflict, User $resolver, string $action, ?string $resolution = null): SyncConflict
    {
        return DB::transaction(function () use ($conflict, $resolver, $action, $resolution): SyncConflict {
            $status = $action === 'accept' ? 'RESOLVED_ACCEPT' : 'RESOLVED_REJECT';

            if ($action === 'accept') {
                $branch = $conflict->branch;
                $draw = Draw::find($conflict->ticket_data['draw_id'] ?? null);

                if ($draw && $branch) {
                    $details = array_map(fn (array $play): array => [
                        'bet_type_id' => $play['bet_type_id'],
                        'number_value' => $play['number_value'],
                        'amount' => $play['amount'],
                    ], $conflict->ticket_data['plays'] ?? []);

                    $this->saleService->sellFromMobile(
                        user: $resolver,
                        drawId: $draw->id,
                        details: $details,
                        uuid: $conflict->ticket_data['uuid'] ?? (string) \Illuminate\Support\Str::uuid(),
                        soldAt: $conflict->ticket_data['sold_at'] ?? null,
                    );
                }
            }

            $conflict->update([
                'status'      => $status,
                'resolution'  => $resolution,
                'resolved_by' => $resolver->id,
                'resolved_at' => now(),
            ]);

            return $conflict->fresh();
        });
    }

    /**
     * Convert decimal money strings to integer cents without float math.
     */
    private function moneyToCents(int|string|null $amount): int
    {
        $value = trim((string) ($amount ?? '0'));
        if ($value === '') {
            return 0;
        }

        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '+-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        $whole = preg_replace('/\D/', '', $whole) ?: '0';
        $fraction = substr(str_pad(preg_replace('/\D/', '', $fraction) ?: '', 2, '0'), 0, 2);
        $cents = ((int) $whole * 100) + (int) $fraction;

        return $negative ? -$cents : $cents;
    }

    private function centsToMoney(int $cents): string
    {
        $negative = $cents < 0;
        $absolute = abs($cents);

        return ($negative ? '-' : '') . intdiv($absolute, 100) . '.' . str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<int, array{amount: int|string}> $plays
     */
    private function ticketAmountCents(array $plays): int
    {
        return array_reduce(
            $plays,
            fn (int $carry, array $play): int => $carry + $this->moneyToCents($play['amount'] ?? '0'),
            0,
        );
    }
}
