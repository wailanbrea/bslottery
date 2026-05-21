<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Http\Requests\Admin\UpdateCompanyRequest;
use App\Models\Company;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $query = Company::query()->orderBy('name');

        if (! $request->user()->isSuperAdmin()) {
            $query->whereKey($request->user()->company_id);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('rnc', 'like', "%{$search}%")
                    ->orWhere('external_code', 'like', "%{$search}%");
            });
        }

        return view('admin.companies.index', [
            'companies' => $query->paginate(15)->withQueryString(),
            'search' => $search ?? '',
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Company::class);

        return view('admin.companies.form', [
            'company' => new Company(['status' => 'ACTIVE']),
        ]);
    }

    public function store(StoreCompanyRequest $request, AuditService $audit): RedirectResponse
    {
        Gate::authorize('create', Company::class);

        $company = Company::query()->create($request->validated());

        $audit->record('companies', 'create', 'Empresa creada.', $company, newValues: $company->toArray());

        return redirect()->route('admin.companies.index')->with('status', 'Empresa creada.');
    }

    public function edit(Company $company): View
    {
        Gate::authorize('update', $company);

        return view('admin.companies.form', compact('company'));
    }

    public function update(UpdateCompanyRequest $request, Company $company, AuditService $audit): RedirectResponse
    {
        Gate::authorize('update', $company);

        $old = $company->toArray();
        $company->update($request->validated());

        $audit->record('companies', 'update', 'Empresa actualizada.', $company, oldValues: $old, newValues: $company->fresh()->toArray());

        return redirect()->route('admin.companies.index')->with('status', 'Empresa actualizada.');
    }
}
