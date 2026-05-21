<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MonitoringSettingsRequest;
use App\Models\Branch;
use App\Models\BranchMonitoringSetting;
use App\Models\SystemNotification;
use App\Services\Audit\AuditService;
use App\Services\Monitoring\BranchMonitoringService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function __construct(
        private BranchMonitoringService $monitoringService,
    ) {}

    public function index(Request $request): View
    {
        $companyId = (int) session('active_company_id');
        $branchId = $this->branchFilter($request);
        $date = $request->date('date') ?: now();
        $from = $date->copy()->startOfDay();
        $to = $date->copy()->endOfDay();

        $snapshot = $this->monitoringService->snapshot($companyId, $branchId, $from, $to);

        $notifications = SystemNotification::with('branch')
            ->where('company_id', $companyId)
            ->where('status', 'UNREAD')
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderByRaw("CASE severity WHEN 'CRITICAL' THEN 0 WHEN 'WARNING' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('admin.monitoring.index', [
            'rows' => $snapshot['rows'],
            'totals' => $snapshot['totals'],
            'notifications' => $notifications,
            'branches' => $this->branches($request),
            'filters' => [
                'branch_id' => $branchId,
                'date' => $date->toDateString(),
            ],
        ]);
    }

    public function settings(Request $request): View
    {
        $companyId = (int) session('active_company_id');
        $branches = Branch::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $settings = BranchMonitoringSetting::query()
            ->where('company_id', $companyId)
            ->get()
            ->keyBy(fn (BranchMonitoringSetting $setting) => $setting->branch_id ?: 0);

        return view('admin.monitoring.settings', [
            'branches' => $branches,
            'settings' => $settings,
            'defaultSetting' => $settings[0] ?? new BranchMonitoringSetting([
                'company_id' => $companyId,
                'branch_id' => null,
                'alert_enabled' => true,
                'loss_threshold' => '0.00',
                'minimum_expected_cash' => null,
                'top_play_alert_amount' => null,
            ]),
        ]);
    }

    public function updateSettings(MonitoringSettingsRequest $request, AuditService $audit): RedirectResponse
    {
        $companyId = (int) session('active_company_id');
        $validBranchIds = Branch::query()
            ->where('company_id', $companyId)
            ->pluck('id')
            ->all();

        foreach ($request->validated('settings') as $entry) {
            $branchId = filled($entry['branch_id'] ?? null) ? (int) $entry['branch_id'] : null;

            if ($branchId !== null && ! in_array($branchId, $validBranchIds, true)) {
                abort(422, 'Sucursal invalida para la empresa activa.');
            }

            $setting = BranchMonitoringSetting::query()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                ],
                [
                    'alert_enabled' => (bool) ($entry['alert_enabled'] ?? false),
                    'loss_threshold' => Money::normalize($entry['loss_threshold']),
                    'minimum_expected_cash' => filled($entry['minimum_expected_cash'] ?? null)
                        ? Money::normalize($entry['minimum_expected_cash'])
                        : null,
                    'top_play_alert_amount' => filled($entry['top_play_alert_amount'] ?? null)
                        ? Money::normalize($entry['top_play_alert_amount'])
                        : null,
                ]
            );

            $audit->record(
                module: 'Monitoring',
                action: 'update_monitoring_thresholds',
                auditable: $setting,
                description: 'Umbrales de monitoreo actualizados.',
                newValues: $setting->only([
                    'company_id',
                    'branch_id',
                    'alert_enabled',
                    'loss_threshold',
                    'minimum_expected_cash',
                    'top_play_alert_amount',
                ]),
            );
        }

        return redirect()
            ->route('admin.monitoring.settings')
            ->with('status', 'Umbrales de monitoreo actualizados.');
    }

    private function branchFilter(Request $request): ?int
    {
        $user = $request->user();

        if ($user?->branch_id && ! $user->hasPermission('branches.view') && ! $user->hasPermission('companies.view')) {
            return (int) $user->branch_id;
        }

        return $request->integer('branch_id') ?: null;
    }

    private function branches(Request $request)
    {
        $companyId = (int) session('active_company_id');
        $branchId = $this->branchFilter($request);

        return Branch::query()
            ->where('company_id', $companyId)
            ->when($branchId && ! $request->user()?->hasPermission('branches.view'), fn ($query) => $query->whereKey($branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }
}
