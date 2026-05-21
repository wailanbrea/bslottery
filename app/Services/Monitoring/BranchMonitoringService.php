<?php

namespace App\Services\Monitoring;

use App\Models\Branch;
use App\Models\BranchMonitoringSetting;
use App\Models\CashSession;
use App\Models\PrizePayment;
use App\Models\Ticket;
use App\Models\TicketDetail;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BranchMonitoringService
{
    public function __construct(
        private SystemNotificationService $notifications,
    ) {}

    /**
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, string|int>}
     */
    public function snapshot(int $companyId, ?int $branchId, CarbonInterface $from, CarbonInterface $to): array
    {
        $branches = Branch::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($query) => $query->whereKey($branchId))
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status']);

        $sales = $this->salesByBranch($companyId, $branchId, $from, $to);
        $prizes = $this->prizesByBranch($companyId, $branchId, $from, $to);
        $cash = $this->activeCashByBranch($companyId, $branchId);
        $topPlays = $this->topPlaysByBranch($companyId, $branchId, $from, $to);
        $settings = $this->settingsByBranch($companyId);

        $rows = [];
        $totals = [
            'branches_count' => $branches->count(),
            'sales_total' => '0.00',
            'prizes_total' => '0.00',
            'net_total' => '0.00',
            'branches_in_loss' => 0,
            'branches_low_cash' => 0,
            'branches_with_top_play_alert' => 0,
        ];

        foreach ($branches as $branch) {
            $setting = $settings[$branch->id] ?? $settings[0] ?? $this->defaultSetting($companyId, $branch->id);
            $branchSales = (string) ($sales[$branch->id]->sales_total ?? '0.00');
            $branchPrizes = (string) ($prizes[$branch->id]->prizes_total ?? '0.00');
            $net = Money::subtract($branchSales, $branchPrizes);
            $expectedCash = (string) ($cash[$branch->id]->expected_cash ?? '0.00');
            $topPlay = $topPlays[$branch->id] ?? null;
            $alerts = [];
            $lossAmount = $this->lossAmount($net, $expectedCash);
            $isLoss = $setting->alert_enabled && Money::isGreaterThan($lossAmount, (string) $setting->loss_threshold);
            $isLowCash = $setting->alert_enabled
                && $setting->minimum_expected_cash !== null
                && Money::isLessThan($expectedCash, (string) $setting->minimum_expected_cash);
            $isTopPlayAlert = $setting->alert_enabled
                && $topPlay
                && $setting->top_play_alert_amount !== null
                && Money::isGreaterThan((string) $topPlay->amount_total, (string) $setting->top_play_alert_amount);

            if ($isLoss) {
                $totals['branches_in_loss']++;
                $this->notifyLoss($companyId, $branch->id, $branch->name, $lossAmount, $net, $expectedCash, $from);
                $alerts[] = 'LOSS';
            }

            if ($isLowCash) {
                $totals['branches_low_cash']++;
                $this->notifyLowCash(
                    $companyId,
                    $branch->id,
                    $branch->name,
                    $expectedCash,
                    (string) $setting->minimum_expected_cash,
                    $from
                );
                $alerts[] = 'LOW_CASH';
            }

            if ($isTopPlayAlert) {
                $totals['branches_with_top_play_alert']++;
                $this->notifyHighTopPlay(
                    $companyId,
                    $branch->id,
                    $branch->name,
                    $topPlay,
                    (string) $setting->top_play_alert_amount,
                    $from
                );
                $alerts[] = 'TOP_PLAY';
            }

            $totals['sales_total'] = Money::add($totals['sales_total'], $branchSales);
            $totals['prizes_total'] = Money::add($totals['prizes_total'], $branchPrizes);
            $totals['net_total'] = Money::add($totals['net_total'], $net);

            $rows[] = [
                'branch' => $branch,
                'tickets_count' => (int) ($sales[$branch->id]->tickets_count ?? 0),
                'sales_total' => Money::normalize($branchSales),
                'prizes_total' => Money::normalize($branchPrizes),
                'net_total' => $net,
                'expected_cash' => Money::normalize($expectedCash),
                'is_loss' => $isLoss,
                'is_low_cash' => $isLowCash,
                'is_top_play_alert' => $isTopPlayAlert,
                'loss_amount' => $lossAmount,
                'top_play' => $topPlay,
                'alerts' => $alerts,
                'monitoring_setting' => $setting,
            ];
        }

        return ['rows' => $rows, 'totals' => $totals];
    }

    /**
     * @return Collection<int, object>
     */
    private function salesByBranch(int $companyId, ?int $branchId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Ticket::query()
            ->where('company_id', $companyId)
            ->where('status', '!=', 'CANCELLED')
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereBetween('sold_at', [$from, $to])
            ->selectRaw('branch_id, COUNT(*) as tickets_count, COALESCE(SUM(total_amount), 0) as sales_total')
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');
    }

    /**
     * @return Collection<int, object>
     */
    private function prizesByBranch(int $companyId, ?int $branchId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return PrizePayment::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('branch_id, COALESCE(SUM(amount), 0) as prizes_total')
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');
    }

    /**
     * @return Collection<int, object>
     */
    private function activeCashByBranch(int $companyId, ?int $branchId): Collection
    {
        return CashSession::query()
            ->where('company_id', $companyId)
            ->where('status', 'OPEN')
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->selectRaw('branch_id, COALESCE(SUM(expected_cash), 0) as expected_cash')
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');
    }

    /**
     * @return array<int, object>
     */
    private function topPlaysByBranch(int $companyId, ?int $branchId, CarbonInterface $from, CarbonInterface $to): array
    {
        $grouped = TicketDetail::query()
            ->with(['betType', 'lottery', 'draw'])
            ->where('company_id', $companyId)
            ->where('status', '!=', 'CANCELLED')
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereHas('ticket', fn ($query) => $query->whereBetween('sold_at', [$from, $to])->where('status', '!=', 'CANCELLED'))
            ->select('branch_id', 'number_value', 'bet_type_id', 'lottery_id', 'draw_id')
            ->selectRaw('COUNT(*) as plays_count, COALESCE(SUM(amount), 0) as amount_total')
            ->groupBy('branch_id', 'number_value', 'bet_type_id', 'lottery_id', 'draw_id')
            ->orderBy('branch_id')
            ->orderByDesc(DB::raw('amount_total'))
            ->get();

        $top = [];
        foreach ($grouped as $row) {
            $top[$row->branch_id] ??= $row;
        }

        return $top;
    }

    /**
     * @return Collection<int, BranchMonitoringSetting>
     */
    private function settingsByBranch(int $companyId): Collection
    {
        return BranchMonitoringSetting::query()
            ->where('company_id', $companyId)
            ->get()
            ->keyBy(fn (BranchMonitoringSetting $setting) => $setting->branch_id ?: 0);
    }

    private function defaultSetting(int $companyId, int $branchId): BranchMonitoringSetting
    {
        return new BranchMonitoringSetting([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'alert_enabled' => true,
            'loss_threshold' => '0.00',
            'minimum_expected_cash' => null,
            'top_play_alert_amount' => null,
        ]);
    }

    private function lossAmount(string $net, string $expectedCash): string
    {
        $netLoss = Money::isNegative($net) ? Money::absolute($net) : '0.00';
        $cashLoss = Money::isNegative($expectedCash) ? Money::absolute($expectedCash) : '0.00';

        return Money::isGreaterThan($netLoss, $cashLoss) ? $netLoss : $cashLoss;
    }

    private function notifyLoss(int $companyId, int $branchId, string $branchName, string $lossAmount, string $net, string $expectedCash, CarbonInterface $date): void
    {
        $this->notifications->upsertUnread(
            companyId: $companyId,
            branchId: $branchId,
            type: 'BRANCH_LOSS',
            severity: 'CRITICAL',
            title: "Sucursal {$branchName} requiere efectivo",
            body: "La sucursal presenta perdida o caja negativa. Perdida estimada RD$ ".number_format((float) $lossAmount, 2).'.',
            amount: $lossAmount,
            fingerprint: "branch-loss:{$companyId}:{$branchId}:".$date->toDateString(),
            payload: [
                'net_total' => $net,
                'expected_cash' => $expectedCash,
                'date' => $date->toDateString(),
            ],
        );
    }

    private function notifyLowCash(int $companyId, int $branchId, string $branchName, string $expectedCash, string $minimumExpectedCash, CarbonInterface $date): void
    {
        $shortfall = Money::subtract($minimumExpectedCash, $expectedCash);

        $this->notifications->upsertUnread(
            companyId: $companyId,
            branchId: $branchId,
            type: 'BRANCH_LOW_CASH',
            severity: 'WARNING',
            title: "Sucursal {$branchName} con efectivo bajo",
            body: "La caja estimada esta por debajo del minimo configurado. Faltante sugerido RD$ ".number_format((float) $shortfall, 2).'.',
            amount: $shortfall,
            fingerprint: "branch-low-cash:{$companyId}:{$branchId}:".$date->toDateString(),
            payload: [
                'expected_cash' => $expectedCash,
                'minimum_expected_cash' => $minimumExpectedCash,
                'date' => $date->toDateString(),
            ],
        );
    }

    private function notifyHighTopPlay(int $companyId, int $branchId, string $branchName, object $topPlay, string $threshold, CarbonInterface $date): void
    {
        $this->notifications->upsertUnread(
            companyId: $companyId,
            branchId: $branchId,
            type: 'BRANCH_TOP_PLAY_HIGH',
            severity: 'WARNING',
            title: "Jugada alta en {$branchName}",
            body: "La jugada {$topPlay->number_value} acumula RD$ ".number_format((float) $topPlay->amount_total, 2).' en la sucursal.',
            amount: (string) $topPlay->amount_total,
            fingerprint: "branch-top-play:{$companyId}:{$branchId}:{$topPlay->number_value}:".$date->toDateString(),
            payload: [
                'number_value' => $topPlay->number_value,
                'bet_type_id' => $topPlay->bet_type_id,
                'lottery_id' => $topPlay->lottery_id,
                'draw_id' => $topPlay->draw_id,
                'threshold' => $threshold,
                'date' => $date->toDateString(),
            ],
        );
    }
}
