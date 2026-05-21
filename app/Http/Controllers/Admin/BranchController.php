<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBranchRequest;
use App\Http\Requests\Admin\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BranchController extends Controller
{
    public function index(Request $request): View
    {
        $query = Branch::query()->with('company')->orderBy('code');
        $this->applyScope($request, $query);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return view('admin.branches.index', [
            'branches' => $query->paginate(15)->withQueryString(),
            'search' => $search ?? '',
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Branch::class);

        return view('admin.branches.form', [
            'branch' => new Branch([
                'status' => 'ACTIVE',
                'can_sell_online' => true,
                'offline_max_minutes' => 120,
                'offline_total_limit' => '0.00',
                'cash_control_enabled' => true,
                'accounting_enabled' => true,
                'payroll_enabled' => true,
            ]),
            'companies' => $this->companiesFor($request),
        ]);
    }

    public function store(StoreBranchRequest $request, AuditService $audit): RedirectResponse
    {
        Gate::authorize('create', Branch::class);

        $data = $request->validated();
        $data['company_id'] = $request->user()->isSuperAdmin() ? $data['company_id'] : $request->user()->company_id;
        $data = $this->normalizeBooleans($data);

        $branch = Branch::query()->create($data);

        $audit->record('branches', 'create', 'Sucursal creada.', $branch, newValues: $branch->toArray());

        return redirect()->route('admin.branches.index')->with('status', 'Sucursal creada.');
    }

    public function edit(Request $request, Branch $branch): View
    {
        Gate::authorize('update', $branch);

        return view('admin.branches.form', [
            'branch' => $branch,
            'companies' => $this->companiesFor($request),
        ]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch, AuditService $audit): RedirectResponse
    {
        Gate::authorize('update', $branch);

        $old = $branch->toArray();
        $branch->update($this->normalizeBooleans($request->validated()));

        $audit->record('branches', 'update', 'Sucursal actualizada.', $branch, oldValues: $old, newValues: $branch->fresh()->toArray());

        return redirect()->route('admin.branches.index')->with('status', 'Sucursal actualizada.');
    }

    private function applyScope(Request $request, $query): void
    {
        if (! $request->user()->isSuperAdmin()) {
            $query->where('company_id', $request->user()->company_id);
        }

        if ($request->user()->branch_id) {
            $query->whereKey($request->user()->branch_id);
        }
    }

    private function companiesFor(Request $request)
    {
        $query = Company::query()->orderBy('name');

        if (! $request->user()->isSuperAdmin()) {
            $query->whereKey($request->user()->company_id);
        }

        return $query->get();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeBooleans(array $data): array
    {
        foreach (['can_sell_online', 'can_sell_offline', 'cash_control_enabled', 'accounting_enabled', 'payroll_enabled'] as $field) {
            $data[$field] = (bool) ($data[$field] ?? false);
        }

        return $data;
    }
}
