<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BetTypeStoreRequest;
use App\Http\Requests\Admin\BetTypeUpdateRequest;
use App\Models\BetType;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BetTypeController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', BetType::class);

        $companyId = session('active_company_id');
        $search = $request->query('search', '');

        $betTypes = BetType::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.bet-types.index', compact('betTypes', 'search'));
    }

    public function create(): View
    {
        Gate::authorize('create', BetType::class);

        return view('admin.bet-types.form', ['betType' => new BetType]);
    }

    public function store(BetTypeStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', BetType::class);

        $betType = BetType::create($request->validated());

        app(AuditService::class)->record(
            module: 'BetType',
            action: 'created',
            auditable: $betType,
            description: "Tipo de jugada {$betType->name} ({$betType->code}) creado.",
            newValues: $betType->toArray(),
        );

        return redirect()->route('admin.bet-types.index')->with('status', 'Tipo de jugada creado.');
    }

    public function edit(BetType $betType): View
    {
        Gate::authorize('update', $betType);

        return view('admin.bet-types.form', compact('betType'));
    }

    public function update(BetTypeUpdateRequest $request, BetType $betType): RedirectResponse
    {
        Gate::authorize('update', $betType);

        $oldValues = $betType->toArray();
        $betType->update($request->validated());

        app(AuditService::class)->record(
            module: 'BetType',
            action: 'updated',
            auditable: $betType,
            description: "Tipo de jugada {$betType->name} actualizado.",
            oldValues: $oldValues,
            newValues: $betType->toArray(),
        );

        return redirect()->route('admin.bet-types.index')->with('status', 'Tipo de jugada actualizado.');
    }
}
