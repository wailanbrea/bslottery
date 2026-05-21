<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CashIncidentDismissRequest;
use App\Http\Requests\Admin\CashIncidentResolveRequest;
use App\Models\CashIncident;
use App\Services\Audit\AuditService;
use App\Services\Cash\CashIncidentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashIncidentController extends Controller
{
    public function __construct(
        private CashIncidentService $cashIncidentService,
    ) {}

    public function index(Request $request): View
    {
        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');

        $incidents = CashIncident::with(['branch', 'cashSession', 'user', 'resolvedBy'])
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', strtoupper((string) $request->input('status'))))
            ->when($request->filled('type'), fn ($q) => $q->where('type', strtoupper((string) $request->input('type'))))
            ->orderByRaw("CASE WHEN status = 'OPEN' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.cash.incidents.index', compact('incidents'));
    }

    public function resolve(CashIncidentResolveRequest $request, CashIncident $incident): RedirectResponse
    {
        $this->ensureIncidentScope($incident);

        try {
            $incident = $this->cashIncidentService->resolve($incident, auth()->user(), $request->input('resolution_notes'));

            app(AuditService::class)->record(
                module: 'Cash',
                action: 'incident_resolved',
                auditable: $incident,
                description: "Incidencia de caja #{$incident->id} resuelta.",
            );

            return redirect()->route('admin.cash.incidents.index')->with('status', 'Incidencia resuelta.');
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function dismiss(CashIncidentDismissRequest $request, CashIncident $incident): RedirectResponse
    {
        $this->ensureIncidentScope($incident);

        try {
            $incident = $this->cashIncidentService->dismiss($incident, auth()->user(), $request->input('dismiss_reason'));

            app(AuditService::class)->record(
                module: 'Cash',
                action: 'incident_dismissed',
                auditable: $incident,
                description: "Incidencia de caja #{$incident->id} desestimada (no se descontará al cajero).",
            );

            return redirect()->route('admin.cash.incidents.index')
                ->with('status', 'Incidencia desestimada. No se aplicará descuento al cajero.');
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    private function ensureIncidentScope(CashIncident $incident): void
    {
        abort_unless($incident->company_id === session('active_company_id'), 404);

        $branchId = session('active_branch_id');

        if ($branchId) {
            abort_unless($incident->branch_id === $branchId, 404);
        }
    }
}
