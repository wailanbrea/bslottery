<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CashCloseRequest;
use App\Http\Requests\Admin\CashMovementRequest;
use App\Http\Requests\Admin\CashOpenRequest;
use App\Models\Branch;
use App\Models\CashSession;
use App\Services\Audit\AuditService;
use App\Services\Cash\CashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CashController extends Controller
{
    public function __construct(
        private CashService $cashService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CashSession::class);

        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');

        $sessions = CashSession::with(['user', 'branch', 'openedBy', 'closedBy', 'reconciliation.denominations'])
            ->withCount(['incidents' => fn ($q) => $q->where('status', 'OPEN')])
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('opened_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.cash.index', compact('sessions'));
    }

    public function current(): View|RedirectResponse
    {
        Gate::authorize('view', [CashSession::class]);

        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');

        if (! $branchId) {
            return redirect()->route('dashboard')->withErrors('Selecciona una sucursal activa.');
        }

        $session = $this->cashService->getActiveSession($branchId, auth()->id());

        if (! $session) {
            return view('admin.cash.empty');
        }

        $session->load(['movements' => fn ($q) => $q->orderBy('created_at', 'desc')->limit(50)]);

        return view('admin.cash.current', compact('session'));
    }

    public function openForm(): View
    {
        Gate::authorize('create', CashSession::class);

        $companyId = session('active_company_id');
        $branches = Branch::where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->where('cash_control_enabled', true)
            ->orderBy('name')
            ->get();

        return view('admin.cash.open', compact('branches'));
    }

    public function open(CashOpenRequest $request): RedirectResponse
    {
        Gate::authorize('create', CashSession::class);

        $branch = Branch::findOrFail($request->input('branch_id'));
        $user = auth()->user();

        try {
            $session = $this->cashService->open(
                branch: $branch,
                user: $user,
                openingAmount: $request->input('opening_amount'),
                notes: $request->input('notes'),
            );

            app(AuditService::class)->record(
                module: 'Cash',
                action: 'opened',
                auditable: $session,
                description: "Caja abierta en {$branch->name} con RD\$ " . number_format($session->opening_amount, 2),
            );

            return redirect()->route('admin.cash.current')->with('status', 'Caja abierta correctamente.');
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function movementForm(): View
    {
        Gate::authorize('update', CashSession::class);

        $branchId = session('active_branch_id');
        $session = $this->cashService->getActiveSession($branchId, auth()->id());

        if (! $session) {
            return view('admin.cash.empty');
        }

        return view('admin.cash.movement', compact('session'));
    }

    public function recordMovement(CashMovementRequest $request): RedirectResponse
    {
        Gate::authorize('update', CashSession::class);

        $branchId = session('active_branch_id');
        $session = $this->cashService->getActiveSession($branchId, auth()->id());

        if (! $session) {
            return back()->withErrors('No hay caja abierta.');
        }

        try {
            $movement = $this->cashService->recordMovement(
                session: $session,
                user: auth()->user(),
                type: $request->input('type'),
                amount: $request->input('amount'),
                direction: $request->input('direction'),
                description: $request->input('description'),
            );

            app(AuditService::class)->record(
                module: 'Cash',
                action: 'movement',
                auditable: $movement,
                description: "Movimiento {$movement->type}: RD\$ " . number_format($movement->amount, 2) . " ({$movement->direction})",
            );

            return redirect()->route('admin.cash.current')->with('status', 'Movimiento registrado.');
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function closeForm(): View
    {
        Gate::authorize('update', CashSession::class);

        $branchId = session('active_branch_id');
        $session = $this->cashService->getActiveSession($branchId, auth()->id());

        if (! $session) {
            return view('admin.cash.empty');
        }

        $session->refresh();
        $session->recalculateExpectedCash();

        $denominations = CashService::denominationMap();

        return view('admin.cash.close', compact('session', 'denominations'));
    }

    public function close(CashCloseRequest $request): RedirectResponse
    {
        Gate::authorize('update', CashSession::class);

        $branchId = session('active_branch_id');
        $session = $this->cashService->getActiveSession($branchId, auth()->id());

        if (! $session) {
            return back()->withErrors('No hay caja abierta.');
        }

        try {
            $session = $this->cashService->close(
                session: $session,
                closedBy: auth()->user(),
                countedCash: $request->input('counted_cash'),
                notes: $request->input('notes'),
                denominations: $request->input('denominations', []),
            );

            app(AuditService::class)->record(
                module: 'Cash',
                action: 'closed',
                auditable: $session,
                description: "Caja cerrada. Esperado: RD\$ " . number_format($session->expected_cash, 2)
                    . ', Contado: RD\$ ' . number_format($session->counted_cash, 2)
                    . ($session->shortage_amount > 0 ? ', Faltante: RD\$ ' . number_format($session->shortage_amount, 2) : '')
                    . ($session->surplus_amount > 0 ? ', Sobrante: RD\$ ' . number_format($session->surplus_amount, 2) : ''),
            );

            $difference = \App\Support\Money::subtract($session->counted_cash, $session->expected_cash);

            return redirect()->route('admin.cash.index')->with('status', 'Caja cerrada correctamente.')->with('cash_close_summary', [
                'session_id' => $session->id,
                'expected_cash' => $session->expected_cash,
                'counted_cash' => $session->counted_cash,
                'difference' => $difference,
                'shortage_amount' => $session->shortage_amount,
                'surplus_amount' => $session->surplus_amount,
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function confirm(CashSession $session): RedirectResponse
    {
        Gate::authorize('update', $session);

        try {
            $this->cashService->confirm($session, auth()->user());

            app(AuditService::class)->record(
                module: 'Cash',
                action: 'confirmed',
                auditable: $session,
                description: "Cierre de caja #{$session->id} confirmado.",
            );

            return redirect()->route('admin.cash.index')->with('status', 'Caja confirmada.');
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function reopen(CashSession $session): RedirectResponse
    {
        Gate::authorize('update', $session);

        try {
            $this->cashService->reopen($session, auth()->user(), 'Reapertura autorizada.');

            app(AuditService::class)->record(
                module: 'Cash',
                action: 'reopened',
                auditable: $session,
                description: "Caja #{$session->id} reabierta.",
            );

            return redirect()->route('admin.cash.index')->with('status', 'Caja reabierta.');
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }
    }
}
