<?php

namespace App\Services\Lottery;

use App\Models\BetType;
use App\Models\Branch;
use App\Models\Draw;
use App\Models\LimitConsumption;
use App\Models\LimitRule;
use App\Support\Money;
use Illuminate\Support\Collection;

class LimitValidationService
{
    /**
     * Valida que una jugada no exceda los límites configurados.
     *
     * @return array{allowed: bool, available_amount: float|null, blocking_rule_id: int|null, limit_rule_id: int|null, reason: string|null}
     */
    public function validate(Branch $branch, Draw $draw, BetType $betType, string $numberValue, int|float|string $requestedAmount): array
    {
        $requestedAmount = Money::toFloat($requestedAmount);
        $rules = $this->getApplicableRules($branch->company_id, $branch->id, $draw->lottery_id, $draw->id, $betType->id, $numberValue);

        if ($rules->isEmpty()) {
            return ['allowed' => true, 'available_amount' => null, 'blocking_rule_id' => null, 'limit_rule_id' => null, 'reason' => null];
        }

        $mostRestrictive = null;
        $mostRestrictiveAvailable = null;
        $blocked = false;
        $blockingRuleId = null;
        $blockReason = null;

        foreach ($rules as $rule) {
            $totalConsumed = $this->getConsumedForRule($rule, $branch, $draw, $betType, $numberValue, lock: true);
            $maxForThisRule = Money::toFloat($rule->max_amount_per_number);
            $availableForThisRule = $maxForThisRule - $totalConsumed;

            if ($mostRestrictiveAvailable === null || $availableForThisRule < $mostRestrictiveAvailable) {
                $mostRestrictive = $rule;
                $mostRestrictiveAvailable = $availableForThisRule;
            }

            if ($availableForThisRule < $requestedAmount) {
                $scopeLabel = ($rule->scope ?? 'PER_BRANCH') === 'COMPANY' ? 'empresa' : 'sucursal';

                if ($rule->policy === 'BLOCK_FULL') {
                    $blocked = true;
                    $blockingRuleId = $rule->id;
                    $blockReason = "Número {$numberValue} excede el límite ({$scopeLabel}). Disponible: RD\$" . number_format(max(0, $availableForThisRule), 2) . ", solicitado: RD\$" . number_format($requestedAmount, 2) . '.';
                    break;
                }

                if ($rule->policy === 'ALLOW_AVAILABLE' && $availableForThisRule <= 0) {
                    $blocked = true;
                    $blockingRuleId = $rule->id;
                    $blockReason = "Número {$numberValue} agotado ({$scopeLabel}). Límite: RD\${$maxForThisRule}, consumido: RD\$" . number_format($totalConsumed, 2) . '.';
                    break;
                }

                // REQUEST_AUTHORIZATION — no bloquea
            }
        }

        if ($blocked) {
            return [
                'allowed' => false,
                'available_amount' => max(0, $mostRestrictiveAvailable ?? 0),
                'blocking_rule_id' => $blockingRuleId,
                'limit_rule_id' => $mostRestrictive?->id,
                'reason' => $blockReason,
            ];
        }

        return [
            'allowed' => true,
            'available_amount' => $mostRestrictiveAvailable,
            'blocking_rule_id' => null,
            'limit_rule_id' => $mostRestrictive?->id,
            'reason' => null,
        ];
    }

    /**
     * Retorna el monto disponible para un número dado, o null si no hay límite.
     * No requiere transacción activa.
     */
    public function getAvailableForNumber(Branch $branch, Draw $draw, BetType $betType, string $numberValue): ?float
    {
        $rules = $this->getApplicableRules($branch->company_id, $branch->id, $draw->lottery_id, $draw->id, $betType->id, $numberValue);

        if ($rules->isEmpty()) {
            return null;
        }

        $minAvailable = null;

        foreach ($rules as $rule) {
            $totalConsumed = $this->getConsumedForRule($rule, $branch, $draw, $betType, $numberValue, lock: false);
            $available = Money::toFloat($rule->max_amount_per_number) - $totalConsumed;

            if ($minAvailable === null || $available < $minAvailable) {
                $minAvailable = $available;
            }
        }

        return max(0, $minAvailable ?? 0);
    }

    /**
     * Obtiene todas las reglas de límite aplicables a un número en un contexto dado.
     */
    public function getApplicableRules(int $companyId, ?int $branchId, int $lotteryId, int $drawId, int $betTypeId, string $numberValue): Collection
    {
        $now = now();

        $rules = LimitRule::where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now): void {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            })
            ->where(function ($q) use ($branchId): void {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->where(function ($q) use ($lotteryId): void {
                $q->where('lottery_id', $lotteryId)->orWhereNull('lottery_id');
            })
            ->where(function ($q) use ($drawId): void {
                $q->where('draw_id', $drawId)->orWhereNull('draw_id');
            })
            ->where(function ($q) use ($betTypeId): void {
                $q->where('bet_type_id', $betTypeId)->orWhereNull('bet_type_id');
            })
            ->get()
            ->filter(fn (LimitRule $rule) => $this->ruleMatchesNumber($rule, $numberValue));

        return $rules;
    }

    /**
     * Determina si una regla aplica al número dado.
     */
    public function ruleMatchesNumber(LimitRule $rule, string $numberValue): bool
    {
        return match ($rule->rule_type) {
            'GLOBAL'        => true,
            'SINGLE_NUMBER' => $rule->number_value === $numberValue,
            'NUMBER_RANGE'  => $this->isInRange($numberValue, $rule->number_from, $rule->number_to),
            'NUMBER_LIST'   => $this->isInList($numberValue, $rule->numbers_json ?? []),
            default         => false,
        };
    }

    /**
     * Expande una regla a un array de números (para reportes).
     */
    public function expandRuleNumbers(LimitRule $rule): array
    {
        return match ($rule->rule_type) {
            'NUMBER_RANGE'  => $this->expandRange($rule->number_from, $rule->number_to),
            'NUMBER_LIST'   => $rule->numbers_json ?? [],
            'SINGLE_NUMBER' => $rule->number_value ? [$rule->number_value] : [],
            'GLOBAL'        => [],
            default         => [],
        };
    }

    /**
     * Consume límite para una venta. Debe ejecutarse dentro de una transacción.
     */
    public function consume(Branch $branch, Draw $draw, BetType $betType, string $numberValue, int|float|string $amount): void
    {
        $amount = Money::normalize($amount);

        $existing = LimitConsumption::where('company_id', $branch->company_id)
            ->where('branch_id', $branch->id)
            ->where('lottery_id', $draw->lottery_id)
            ->where('draw_id', $draw->id)
            ->where('bet_type_id', $betType->id)
            ->where('number_value', $numberValue)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            $existing->increment('sold_amount', $amount);
        } else {
            LimitConsumption::create([
                'company_id'  => $branch->company_id,
                'branch_id'   => $branch->id,
                'lottery_id'  => $draw->lottery_id,
                'draw_id'     => $draw->id,
                'bet_type_id' => $betType->id,
                'number_value' => $numberValue,
                'sold_amount' => $amount,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function getConsumedForRule(LimitRule $rule, Branch $branch, Draw $draw, BetType $betType, string $numberValue, bool $lock): float
    {
        $scope = $rule->scope ?? 'PER_BRANCH';

        $query = LimitConsumption::where('company_id', $branch->company_id)
            ->where('lottery_id', $draw->lottery_id)
            ->where('draw_id', $draw->id)
            ->where('bet_type_id', $betType->id)
            ->where('number_value', $numberValue);

        if ($scope === 'COMPANY') {
            // Suma el consumo de TODAS las sucursales de la empresa
            if ($lock) {
                $query->lockForUpdate();
            }
            $result = $query
                ->selectRaw('SUM(sold_amount) as total_sold, SUM(reserved_offline_amount) as total_reserved, SUM(cancelled_amount) as total_cancelled')
                ->first();
            $sold     = Money::toFloat($result?->total_sold ?? 0);
            $reserved = Money::toFloat($result?->total_reserved ?? 0);
            $cancelled = Money::toFloat($result?->total_cancelled ?? 0);
        } else {
            // Solo el consumo de esta sucursal
            $query->where('branch_id', $branch->id);
            if ($lock) {
                $query->lockForUpdate();
            }
            $consumption = $query->first();
            $sold     = Money::toFloat($consumption?->sold_amount ?? 0);
            $reserved = Money::toFloat($consumption?->reserved_offline_amount ?? 0);
            $cancelled = Money::toFloat($consumption?->cancelled_amount ?? 0);
        }

        return max(0, $sold + $reserved - $cancelled);
    }

    private function isInRange(string $number, ?string $from, ?string $to): bool
    {
        if ($from === null || $to === null) {
            return false;
        }

        return (int) $number >= (int) $from && (int) $number <= (int) $to;
    }

    private function isInList(string $number, array $numberList): bool
    {
        return in_array($number, $numberList, true);
    }

    private function expandRange(?string $from, ?string $to): array
    {
        if ($from === null || $to === null) {
            return [];
        }

        $fromInt = (int) $from;
        $toInt   = (int) $to;

        if ($fromInt > $toInt) {
            return [];
        }

        $numbers = [];
        for ($i = $fromInt; $i <= $toInt; $i++) {
            $numbers[] = str_pad((string) $i, strlen($from), '0', STR_PAD_LEFT);
        }

        return $numbers;
    }
}
