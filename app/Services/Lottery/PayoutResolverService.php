<?php

namespace App\Services\Lottery;

use App\Models\BetType;
use App\Models\Branch;
use App\Models\Draw;
use App\Models\Lottery;
use App\Models\PayoutRule;
use Illuminate\Support\Collection;
use RuntimeException;

class PayoutResolverService
{
    /**
     * Resuelve la regla de pago más específica para una jugada.
     *
     * Prioridad (de más específica a más general):
     * 1. Sucursal + Sorteo + Jugada + Posición
     * 2. Sucursal + Lotería + Jugada + Posición
     * 3. Sucursal + Jugada + Posición
     * 4. Empresa + Sorteo + Jugada + Posición
     * 5. Empresa + Lotería + Jugada + Posición
     * 6. Empresa + Jugada + Posición
     *
     * @return array{payout_rule_id: int, payout_multiplier: string}
     */
    public function resolve(Branch $branch, Draw $draw, BetType $betType, ?string $position = null): array
    {
        $companyId = $branch->company_id;
        $branchId = $branch->id;
        $lotteryId = $draw->lottery_id;
        $drawId = $draw->id;
        $betTypeId = $betType->id;
        $now = now();

        $baseQuery = PayoutRule::where('status', 'ACTIVE')
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now): void {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            });

        $priorities = [
            ['company_id' => $companyId, 'branch_id' => $branchId, 'draw_id' => $drawId, 'lottery_id' => null, 'bet_type_id' => $betTypeId],
            ['company_id' => $companyId, 'branch_id' => $branchId, 'lottery_id' => $lotteryId, 'draw_id' => null, 'bet_type_id' => $betTypeId],
            ['company_id' => $companyId, 'branch_id' => $branchId, 'lottery_id' => null, 'draw_id' => null, 'bet_type_id' => $betTypeId],
            ['company_id' => $companyId, 'branch_id' => null, 'draw_id' => $drawId, 'lottery_id' => null, 'bet_type_id' => $betTypeId],
            ['company_id' => $companyId, 'branch_id' => null, 'lottery_id' => $lotteryId, 'draw_id' => null, 'bet_type_id' => $betTypeId],
            ['company_id' => $companyId, 'branch_id' => null, 'lottery_id' => null, 'draw_id' => null, 'bet_type_id' => $betTypeId],
        ];

        // Primero buscar con posición exacta
        $positions = $this->candidatePositions($betType, $position);

        foreach ($positions as $candidatePosition) {
            foreach ($priorities as $priority) {
                $rule = $this->findRule($baseQuery, $priority, $candidatePosition);
                if ($rule) {
                    return [
                        'payout_rule_id' => $rule->id,
                        'payout_multiplier' => (string) $rule->payout_multiplier,
                    ];
                }
            }
        }

        // Si no se encuentra con posición, buscar sin posición (ANY)
        foreach ($priorities as $priority) {
            $rule = $this->findRule($baseQuery, $priority, null);
            if ($rule) {
                return [
                    'payout_rule_id' => $rule->id,
                    'payout_multiplier' => (string) $rule->payout_multiplier,
                ];
            }
        }

        throw new RuntimeException("No existe una regla de pago activa para {$betType->name} en la sucursal {$branch->name}.");
    }

    /**
     * @return array<int, string>
     */
    private function candidatePositions(BetType $betType, ?string $position): array
    {
        if ($position !== null && $position !== '') {
            return [$position];
        }

        return match (strtoupper($betType->code)) {
            'TRIPLETA' => ['EXACT'],
            'QUINIELA', 'PALE', 'SUPER_PALE' => ['FIRST'],
            default => [],
        };
    }

    private function findRule($baseQuery, array $filters, ?string $position): ?PayoutRule
    {
        $query = (clone $baseQuery)
            ->where('company_id', $filters['company_id'])
            ->where('bet_type_id', $filters['bet_type_id']);

        foreach (['branch_id', 'lottery_id', 'draw_id'] as $field) {
            if ($filters[$field] !== null) {
                $query->where($field, $filters[$field]);
            } else {
                $query->whereNull($field);
            }
        }

        if ($position !== null) {
            $query->where('position', $position);
        } else {
            $query->whereNull('position');
        }

        return $query->first();
    }

    /**
     * Obtiene todas las reglas de pago aplicables para una sucursal, agrupadas por ámbito.
     */
    public function getApplicableRules(int $companyId, ?int $branchId = null): Collection
    {
        $now = now();

        return PayoutRule::with(['betType', 'lottery', 'draw', 'branch'])
            ->where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now): void {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            })
            ->when($branchId !== null, fn ($q) => $q->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id')))
            ->orderBy('branch_id', 'desc')
            ->orderBy('draw_id', 'desc')
            ->orderBy('lottery_id', 'desc')
            ->get();
    }
}
