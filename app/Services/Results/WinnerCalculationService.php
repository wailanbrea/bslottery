<?php

namespace App\Services\Results;

use App\Models\Draw;
use App\Models\PaymentAuthorization;
use App\Models\PayoutRule;
use App\Models\Result;
use App\Models\TicketDetail;
use App\Models\User;
use App\Models\WinnerTicket;
use App\Services\Audit\AuditService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class WinnerCalculationService
{
    /**
     * Calcula ganadores usando la misma lógica que TicketPro (TypeScript).
     *
     * - Quiniela (q): 1 número, gana si coincide con 1ª, 2ª o 3ª.
     *   Pagos: primera, segunda, tercera (independientes).
     *
     * - Pale (pl/pale): 2 números.
     *   Gana si coinciden: 1ª-2ª (pale), 2ª-3ª (pale_segunda_tercera),
     *   1ª-3ª (pale, solo si 2ª != 3ª).
     *   Soporta números duplicados (ej: 00-00 necesita 2x "00").
     *
     * - Tripleta (tri/tripleta): 3 números.
     *   Coincidencia por multiplicidad: 3 aciertos = tripleta, 2 = tripletas_pata.
     *
     * - Super Pale (s/super): 2 números en 2 loterías.
     *   Coincide 1ª lotería1 + 1ª lotería2 = superPale.
     */
    public function calculate(Draw $draw, User $user): array
    {
        $result = Result::where('draw_id', $draw->id)
            ->where('status', 'CONFIRMED')
            ->first();

        if (! $result) {
            throw new \RuntimeException('El resultado debe estar CONFIRMED antes de calcular ganadores.');
        }

        if (in_array($draw->status, ['WINNERS_CALCULATED', 'PAYMENTS_RELEASED', 'FINALIZED'], true)) {
            throw new \RuntimeException('Los ganadores ya fueron calculados para este sorteo.');
        }

        $draw->update(['status' => 'CALCULATING_WINNERS']);

        // Normalizar ganadores a 2 dígitos (N2)
        $G1 = [
            $this->N2($result->first_number),
            $this->N2($result->second_number ?? '00'),
            $this->N2($result->third_number ?? '00'),
        ];

        $winners = [];
        $totalPrize = 0;

        TicketDetail::with(['ticket', 'betType', 'company'])
            ->where('draw_id', $draw->id)
            ->where('status', 'ACTIVE')
            ->where('company_id', $draw->company_id)
            ->orderBy('id')
            ->chunkById(500, function ($details) use ($G1, &$winners, &$totalPrize): void {
                DB::transaction(function () use ($details, $G1, &$winners, &$totalPrize): void {
                    foreach ($details as $detail) {
                        $betType = $detail->betType;
                        $tipo = strtolower($betType->code);
                        $monto = Money::toFloat($detail->amount);

                        // Parsear numeros jugados (separados por espacio o guion).
                        $nums = $this->parseNumbers($detail->number_value);

                        if (empty($nums)) {
                            $detail->update(['status' => 'LOSER']);
                            $this->updateTicketStatusIfAllLosers($detail);
                            continue;
                        }

                        $matchResult = match (true) {
                            in_array($tipo, ['q', 'quiniela'], true) => $this->evaluateQuiniela($detail, $nums, $G1, $monto),
                            in_array($tipo, ['pl', 'pale'], true) => $this->evaluatePale($detail, $nums, $G1, $monto),
                            in_array($tipo, ['tri', 'tripleta'], true) => $this->evaluateTripleta($detail, $nums, $G1, $monto),
                            in_array($tipo, ['s', 'super', 'super_pale'], true) => $this->evaluateSuperPale($detail, $nums, $G1, $monto),
                            default => $this->evaluatePale($detail, $nums, $G1, $monto),
                        };

                        if ($matchResult && $matchResult['is_winner']) {
                            $winner = $this->createWinner($detail, $matchResult);
                            $winners[] = $winner;
                            $totalPrize += $matchResult['prize_amount'];
                        }
                    }
                });
            });

        DB::transaction(function () use ($draw, $winners, $totalPrize): void {
            PaymentAuthorization::create([
                'company_id' => $draw->company_id,
                'lottery_id' => $draw->lottery_id,
                'draw_id' => $draw->id,
                'status' => 'PENDING',
                'total_winners' => count($winners),
                'total_prize_amount' => $totalPrize,
            ]);

            $draw->update([
                'status' => 'WINNERS_CALCULATED',
                'winners_calculated_at' => now(),
            ]);
        });

        app(AuditService::class)->record(
            module: 'Results',
            action: 'winners_calculated',
            description: "Cálculo de ganadores: sorteo {$draw->name}. Ganadores: " . count($winners) . ", premio total: RD\$ " . number_format($totalPrize, 2),
            auditable: $draw,
        );

        return ['winners_count' => count($winners), 'total_prize' => $totalPrize];
    }

    /**
     * Quiniela: 1 número. Gana si coincide con 1ª, 2ª o 3ª.
     * Cada posición se evalúa independientemente.
     */
    private function evaluateQuiniela(TicketDetail $detail, array $nums, array $G1, float $monto): ?array
    {
        if (count($nums) !== 1) {
            $detail->update(['status' => 'LOSER']);
            $this->updateTicketStatusIfAllLosers($detail);
            return null;
        }

        $n = $nums[0];
        $totalAmount = 0;
        $hits = [];
        $positions = [];

        if ($n === $G1[0]) {
            $amt = $this->calcPayout($detail, 'FIRST', $monto);
            $totalAmount += $amt;
            $hits[] = "quiniela-primera";
            $positions[] = 'FIRST';
        }
        if ($n === $G1[1]) {
            $amt = $this->calcPayout($detail, 'SECOND', $monto);
            $totalAmount += $amt;
            $hits[] = "quiniela-segunda";
            $positions[] = 'SECOND';
        }
        if ($n === $G1[2]) {
            $amt = $this->calcPayout($detail, 'THIRD', $monto);
            $totalAmount += $amt;
            $hits[] = "quiniela-tercera";
            $positions[] = 'THIRD';
        }

        if (empty($hits)) {
            $detail->update(['status' => 'LOSER']);
            $this->updateTicketStatusIfAllLosers($detail);
            return null;
        }

        return [
            'is_winner' => true,
            'matched_position' => implode('+', $positions),
            'payout_multiplier' => $totalAmount / max($monto, 0.01),
            'prize_amount' => $totalAmount,
        ];
    }

    /**
     * Pale: 2 números.
     * Verifica 1ª-2ª (pale), 2ª-3ª (pale_segunda_tercera), y 1ª-3ª (pale, solo si 2ª != 3ª).
     * Soporta números duplicados con hasPairMultiset.
     */
    private function evaluatePale(TicketDetail $detail, array $nums, array $G1, float $monto): ?array
    {
        if (count($nums) !== 2) {
            $detail->update(['status' => 'LOSER']);
            $this->updateTicketStatusIfAllLosers($detail);
            return null;
        }

        $totalAmount = 0;
        $reasons = [];

        // 1ª-2ª (paga "pale" = FIRST position)
        if ($this->hasPairMultiset($nums, $G1[0], $G1[1])) {
            $amt = $this->calcPayout($detail, 'FIRST', $monto);
            $totalAmount += $amt;
            $reasons[] = 'pale_primera_segunda';
        }

        // 2ª-3ª (paga "pale_segunda_tercera" = SECOND position)
        if ($this->hasPairMultiset($nums, $G1[1], $G1[2])) {
            $amt = $this->calcPayout($detail, 'SECOND', $monto);
            $totalAmount += $amt;
            $reasons[] = 'pale_segunda_tercera';
        }

        // 1ª-3ª (paga "pale" = FIRST), SOLO si 2ª != 3ª para evitar duplicado
        if ($G1[1] !== $G1[2] && $this->hasPairMultiset($nums, $G1[0], $G1[2])) {
            $amt = $this->calcPayout($detail, 'FIRST', $monto);
            $totalAmount += $amt;
            $reasons[] = 'pale_primera_tercera';
        }

        if (empty($reasons)) {
            $detail->update(['status' => 'LOSER']);
            $this->updateTicketStatusIfAllLosers($detail);
            return null;
        }

        return [
            'is_winner' => true,
            'matched_position' => implode('+', $reasons),
            'payout_multiplier' => $totalAmount / max($monto, 0.01),
            'prize_amount' => $totalAmount,
        ];
    }

    /**
     * Tripleta: 3 números.
     * Cuenta coincidencias con multiplicidad:
     * - 3 aciertos → tripleta (EXACT position)
     * - 2 aciertos → tripletas_pata (ANY position)
     */
    private function evaluateTripleta(TicketDetail $detail, array $nums, array $G1, float $monto): ?array
    {
        if (count($nums) !== 3) {
            $detail->update(['status' => 'LOSER']);
            $this->updateTicketStatusIfAllLosers($detail);
            return null;
        }

        $hit = $this->countMatchesMultiset($nums, $G1);

        if ($hit === 0 || $hit === 1) {
            $detail->update(['status' => 'LOSER']);
            $this->updateTicketStatusIfAllLosers($detail);
            return null;
        }

        if ($hit === 3) {
            $amt = $this->calcPayout($detail, 'EXACT', $monto);
            return [
                'is_winner' => true,
                'matched_position' => 'tripleta',
                'payout_multiplier' => $amt / max($monto, 0.01),
                'prize_amount' => $amt,
            ];
        }

        // hit === 2: pata
        $amt = $this->calcPayout($detail, 'ANY', $monto);
        return [
            'is_winner' => true,
            'matched_position' => 'tripletas_pata',
            'payout_multiplier' => $amt / max($monto, 0.01),
            'prize_amount' => $amt,
        ];
    }

    /**
     * Super Pale: 2 números, cross-lottery.
     * Verifica que los números jugados contengan G1[0] de la lotería actual
     * Y el primer número de la segunda lotería (superSecondFirst).
     * NOTA: La implementación completa requiere el resultado de la segunda lotería,
     * que se pasa como parámetro adicional cuando esté disponible.
     */
    private function evaluateSuperPale(TicketDetail $detail, array $nums, array $G1, float $monto): ?array
    {
        if (count($nums) !== 2) {
            $detail->update(['status' => 'LOSER']);
            $this->updateTicketStatusIfAllLosers($detail);
            return null;
        }

        // Para Super Pale necesitamos el resultado de la segunda lotería.
        // Esta información viene del contexto de la jugada (multiple loterías).
        // Por ahora, si no hay segunda lotería configurada en el detail, se marca como LOSER.
        // La lógica completa se implementa cuando el ticket_detail tenga la referencia a la 2ª lotería.

        // Buscar si este detail tiene una lotería secundaria configurada
        $lotteryId2 = $detail->lottery_id_2 ?? null;
        if (! $lotteryId2) {
            $detail->update(['status' => 'LOSER']);
            $this->updateTicketStatusIfAllLosers($detail);
            return null;
        }

        // Buscar el resultado de la segunda lotería en el mismo sorteo
        $result2 = Result::where('lottery_id', $lotteryId2)
            ->where('draw_id', $detail->draw_id)
            ->where('status', 'CONFIRMED')
            ->first();

        if (! $result2) {
            $detail->update(['status' => 'LOSER']);
            $this->updateTicketStatusIfAllLosers($detail);
            return null;
        }

        $superFirst = $this->N2($result2->first_number);
        $set = array_flip($nums);

        $ok = isset($set[$G1[0]]) && isset($set[$superFirst]);
        if (! $ok) {
            $detail->update(['status' => 'LOSER']);
            $this->updateTicketStatusIfAllLosers($detail);
            return null;
        }

        $amt = $this->calcPayout($detail, 'FIRST', $monto);
        return [
            'is_winner' => true,
            'matched_position' => 'superPale',
            'payout_multiplier' => $amt / max($monto, 0.01),
            'prize_amount' => $amt,
        ];
    }

    /**
     * Calcula el monto de pago: multiplicador_configurado * monto_apostado.
     */
    private function calcPayout(TicketDetail $detail, string $position, float $monto): float
    {
        $multiplier = $this->resolvePositionPayout(
            $detail->company_id,
            $detail->branch_id,
            $detail->lottery_id,
            $detail->draw_id,
            $detail->bet_type_id,
            $position
        );

        return round($monto * $multiplier, 2);
    }

    /**
     * Resuelve el multiplicador para una posición específica.
     */
    private function resolvePositionPayout(int $companyId, int $branchId, int $lotteryId, int $drawId, int $betTypeId, string $position): float
    {
        $rule = PayoutRule::where('company_id', $companyId)
            ->where('bet_type_id', $betTypeId)
            ->where('position', $position)
            ->where('status', 'ACTIVE')
            ->where('effective_from', '<=', now())
            ->where(function ($q): void {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
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
            ->orderBy('branch_id', 'desc')
            ->orderBy('draw_id', 'desc')
            ->orderBy('lottery_id', 'desc')
            ->first();

        if ($rule) {
            return (float) $rule->payout_multiplier;
        }

        $rule = PayoutRule::where('company_id', $companyId)
            ->where('bet_type_id', $betTypeId)
            ->whereNull('position')
            ->where('status', 'ACTIVE')
            ->first();

        if (! $rule) {
            throw new \RuntimeException('No existe regla de pago activa para calcular ganadores.');
        }

        return (float) $rule->payout_multiplier;
    }

    // ==================== HELPERS (igual que TS) ====================

    /**
     * Normalizar número a 2 dígitos: "1" → "01", "64" → "64".
     */
    private function N2(string $s): string
    {
        $t = trim((string) ($s ?? ''));
        if (strlen($t) >= 2) {
            return $t;
        }

        return strlen($t) === 1 ? "0{$t}" : '00';
    }

    /**
     * Parsear números desde el valor almacenado.
     * Soporta separación por espacio, guion, o ambos.
     */
    private function parseNumbers(string $numbers): array
    {
        $nums = preg_split('/[\s\-]+/', trim($numbers));
        return array_values(array_filter(array_map(fn ($s) => $this->N2($s), $nums), fn ($s) => $s !== '00' || $s === '00'));
    }

    /**
     * Verificar par con multiplicidad (maneja pales como "00-00").
     * Si a === b, necesita 2 ocurrencias del mismo número.
     */
    private function hasPairMultiset(array $nums, string $a, string $b): bool
    {
        if ($a === $b) {
            return count(array_filter($nums, fn ($x) => $x === $a)) >= 2;
        }

        $set = array_flip($nums);

        return isset($set[$a]) && isset($set[$b]);
    }

    /**
     * Contar coincidencias con multiplicidad (para tripleta).
     */
    private function countMatchesMultiset(array $nums, array $winners): int
    {
        $freq = [];
        foreach ($winners as $w) {
            $freq[$w] = ($freq[$w] ?? 0) + 1;
        }

        $c = 0;
        foreach ($nums as $n) {
            if (($freq[$n] ?? 0) > 0) {
                $freq[$n]--;
                $c++;
            }
        }

        return $c;
    }

    // ==================== PERSISTENCIA ====================

    private function createWinner(TicketDetail $detail, array $matchResult): WinnerTicket
    {
        $threshold = Money::toFloat($detail->company?->big_prize_threshold ?? 0);
        $prizeAmount = Money::toFloat($matchResult['prize_amount']);
        $status = $threshold > 0 && $prizeAmount >= $threshold ? 'HELD' : 'PENDING_RELEASE';

        $winner = WinnerTicket::create([
            'company_id' => $detail->company_id,
            'branch_id' => $detail->branch_id,
            'ticket_id' => $detail->ticket_id,
            'ticket_detail_id' => $detail->id,
            'lottery_id' => $detail->lottery_id,
            'draw_id' => $detail->draw_id,
            'bet_type_id' => $detail->bet_type_id,
            'number_value' => $detail->number_value,
            'matched_position' => $matchResult['matched_position'],
            'amount_played' => $detail->amount,
            'payout_multiplier' => $matchResult['payout_multiplier'],
            'prize_amount' => $matchResult['prize_amount'],
            'status' => $status,
        ]);

        if ($status === 'HELD') {
            app(AuditService::class)->record(
                module: 'Prizes',
                action: 'held',
                auditable: $winner,
                description: "Premio retenido por umbral de premio grande. Ticket #{$detail->ticket->ticket_number}, RD\$ " . number_format($prizeAmount, 2),
            );
        }

        $detail->update([
            'status' => 'WINNER',
            'result_position' => $matchResult['matched_position'],
        ]);

        $detail->ticket->update(['status' => 'WINNER']);

        return $winner;
    }

    private function updateTicketStatusIfAllLosers(TicketDetail $detail): void
    {
        $hasWinners = TicketDetail::where('ticket_id', $detail->ticket_id)
            ->where('status', 'WINNER')
            ->exists();

        if (! $hasWinners) {
            $activeExists = TicketDetail::where('ticket_id', $detail->ticket_id)
                ->where('status', 'ACTIVE')
                ->exists();

            if (! $activeExists) {
                $detail->ticket->update(['status' => 'LOSER']);
            }
        }
    }

    public function authorizePayments(Draw $draw, User $user, ?string $notes = null): PaymentAuthorization
    {
        $auth = PaymentAuthorization::where('draw_id', $draw->id)
            ->where('status', 'PENDING')
            ->first();

        if (! $auth) {
            throw new \RuntimeException('No hay autorización pendiente para este sorteo.');
        }

        $auth->update([
            'status' => 'AUTHORIZED',
            'authorized_by' => $user->id,
            'authorized_at' => now(),
            'notes' => $notes ? ($auth->notes . "\n" . $notes) : $auth->notes,
        ]);

        WinnerTicket::where('draw_id', $draw->id)
            ->where('status', 'PENDING_RELEASE')
            ->update(['status' => 'RELEASED']);

        $draw->update([
            'status' => 'PAYMENTS_RELEASED',
            'payments_released_at' => now(),
        ]);

        app(AuditService::class)->record(
            module: 'Results',
            action: 'payments_authorized',
            description: "Pagos autorizados: sorteo {$draw->name}. {$auth->total_winners} ganadores, RD\$ " . number_format($auth->total_prize_amount, 2),
            auditable: $draw,
        );

        return $auth->fresh();
    }
}
