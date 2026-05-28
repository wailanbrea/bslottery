<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LotteryStoreRequest;
use App\Http\Requests\Admin\LotteryUpdateRequest;
use App\Models\Lottery;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LotteryController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Lottery::class);

        $companyId = session('active_company_id');
        $search = $request->query('search', '');

        $lotteries = Lottery::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.lotteries.index', compact('lotteries', 'search'));
    }

    public function create(): View
    {
        Gate::authorize('create', Lottery::class);

        return view('admin.lotteries.form', ['lottery' => new Lottery]);
    }

    public function store(LotteryStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', Lottery::class);

        $lottery = Lottery::create($request->validated());

        app(AuditService::class)->record(
            module: 'Lottery',
            action: 'created',
            auditable: $lottery,
            description: "Lotería {$lottery->name} ({$lottery->code}) creada.",
            newValues: $lottery->toArray(),
        );

        return redirect()->route('admin.lotteries.index')->with('status', 'Lotería creada.');
    }

    public function edit(Lottery $lottery): View
    {
        Gate::authorize('update', $lottery);

        return view('admin.lotteries.form', compact('lottery'));
    }

    public function update(LotteryUpdateRequest $request, Lottery $lottery): RedirectResponse
    {
        Gate::authorize('update', $lottery);

        $oldValues = $lottery->toArray();
        $lottery->update($request->validated());

        app(AuditService::class)->record(
            module: 'Lottery',
            action: 'updated',
            auditable: $lottery,
            description: "Lotería {$lottery->name} actualizada.",
            oldValues: $oldValues,
            newValues: $lottery->toArray(),
        );

        return redirect()->route('admin.lotteries.index')->with('status', 'Lotería actualizada.');
    }

    public function updateStatus(Request $request, Lottery $lottery): RedirectResponse
    {
        Gate::authorize('toggle', $lottery);

        $data = $request->validate([
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $oldValues = $lottery->toArray();
        $lottery->update(['status' => $data['status']]);

        $statusLabel = $data['status'] === 'ACTIVE' ? 'abierta' : 'cerrada';

        app(AuditService::class)->record(
            module: 'Lottery',
            action: 'status_updated',
            auditable: $lottery,
            description: "Loteria {$lottery->name} {$statusLabel}.",
            oldValues: $oldValues,
            newValues: $lottery->toArray(),
        );

        return redirect()->route('admin.lotteries.index')->with('status', "Loteria {$lottery->name} {$statusLabel}.");
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        Gate::authorize('toggleAny', Lottery::class);

        $data = $request->validate([
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $companyId = session('active_company_id');

        $query = Lottery::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', '!=', $data['status']);

        $lotteryIds = $query->pluck('id');
        $updated = 0;

        if ($lotteryIds->isNotEmpty()) {
            $updated = Lottery::query()
                ->whereIn('id', $lotteryIds)
                ->update(['status' => $data['status']]);
        }

        $statusLabel = $data['status'] === 'ACTIVE' ? 'abiertas' : 'cerradas';

        app(AuditService::class)->record(
            module: 'Lottery',
            action: 'bulk_status_updated',
            description: "{$updated} loterias {$statusLabel} en lote.",
            newValues: [
                'status' => $data['status'],
                'lotteries_updated' => $lotteryIds->all(),
                'count' => $updated,
            ],
        );

        return redirect()->route('admin.lotteries.index')->with('status', "{$updated} loterias {$statusLabel}.");
    }
}
