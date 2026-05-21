<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DrawStoreRequest;
use App\Http\Requests\Admin\DrawUpdateRequest;
use App\Models\Draw;
use App\Models\Lottery;
use App\Services\Audit\AuditService;
use App\Services\Lottery\DrawLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DrawController extends Controller
{
    public function __construct(
        private DrawLifecycleService $drawLifecycleService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Draw::class);

        $companyId = session('active_company_id');
        $search = $request->query('search', '');
        $status = $request->query('status', '');

        $draws = Draw::with('lottery')
            ->withCount([
                'ticketDetails as active_ticket_details_count' => fn ($query) => $query->where('status', 'ACTIVE'),
            ])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhereHas('lottery', fn ($q) => $q->where('name', 'like', "%{$search}%"))))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('draw_date', 'desc')
            ->orderBy('scheduled_time', 'asc')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.draws.index', compact('draws', 'search', 'status'));
    }

    public function create(): View
    {
        abort_unless($request->user()?->hasPermission('draws.update'), 403);

        $companyId = session('active_company_id');
        $lotteries = Lottery::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        return view('admin.draws.form', ['draw' => new Draw, 'lotteries' => $lotteries]);
    }

    public function store(DrawStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', Draw::class);

        $draw = Draw::create($request->validated());

        app(AuditService::class)->record(
            module: 'Draw',
            action: 'created',
            auditable: $draw,
            description: "Sorteo {$draw->name} creado para {$draw->draw_date->format('Y-m-d')}.",
            newValues: $draw->toArray(),
        );

        return redirect()->route('admin.draws.index')->with('status', 'Sorteo creado.');
    }

    public function edit(Draw $draw): View
    {
        Gate::authorize('update', $draw);

        $companyId = session('active_company_id');
        $lotteries = Lottery::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        return view('admin.draws.form', compact('draw', 'lotteries'));
    }

    public function update(DrawUpdateRequest $request, Draw $draw): RedirectResponse
    {
        Gate::authorize('update', $draw);

        $oldValues = $draw->toArray();
        $draw->update($request->validated());

        app(AuditService::class)->record(
            module: 'Draw',
            action: 'updated',
            auditable: $draw,
            description: "Sorteo {$draw->name} actualizado.",
            oldValues: $oldValues,
            newValues: $draw->toArray(),
        );

        return redirect()->route('admin.draws.index')->with('status', 'Sorteo actualizado.');
    }

    public function updateOpenTimeForCompany(Request $request): RedirectResponse
    {
        Gate::authorize('create', Draw::class);

        $data = $request->validate([
            'open_time' => 'required|date_format:H:i',
        ]);

        $companyId = session('active_company_id');
        $updated = Draw::query()
            ->where('company_id', $companyId)
            ->update(['open_time' => $data['open_time']]);

        app(AuditService::class)->record(
            module: 'Draw',
            action: 'bulk_open_time_updated',
            description: "Hora de apertura actualizada a {$data['open_time']} para {$updated} sorteo(s).",
            newValues: [
                'open_time' => $data['open_time'],
                'draws_updated' => $updated,
            ],
        );

        return redirect()->route('admin.draws.index')
            ->with('status', "Hora de apertura actualizada para {$updated} sorteo(s).");
    }

    public function close(Draw $draw, Request $request): RedirectResponse
    {
        Gate::authorize('close', $draw);

        $data = $request->validate([
            'ticket_resolution_policy' => 'nullable|in:NONE,KEEP_CURRENT,TRANSFER_NEXT,CANCEL_TICKETS',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->drawLifecycleService->close(
                draw: $draw,
                user: auth()->user(),
                ticketPolicy: $data['ticket_resolution_policy'] ?? DrawLifecycleService::POLICY_NONE,
                reason: $data['reason'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }

        return redirect()->route('admin.draws.index')->with('status', 'Sorteo cerrado.');
    }

    public function cancel(Draw $draw, Request $request): RedirectResponse
    {
        Gate::authorize('close', $draw);

        $data = $request->validate([
            'ticket_resolution_policy' => 'required|in:TRANSFER_NEXT,CANCEL_TICKETS',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->drawLifecycleService->cancel(
                draw: $draw,
                user: auth()->user(),
                ticketPolicy: $data['ticket_resolution_policy'],
                reason: $data['reason'],
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }

        return redirect()->route('admin.draws.index')->with('status', 'Sorteo cancelado.');
    }
}
