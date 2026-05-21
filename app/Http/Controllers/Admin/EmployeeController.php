<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Employee::class);

        $companyId = session('active_company_id');
        $branchId  = session('active_branch_id');

        $employees = Employee::with('branch', 'user')
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.employees.index', compact('employees'));
    }

    public function create(): View
    {
        Gate::authorize('create', Employee::class);

        $companyId = session('active_company_id');
        $branches  = Branch::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();
        $users     = User::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();

        return view('admin.employees.form', [
            'employee' => new Employee,
            'branches' => $branches,
            'users'    => $users,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Employee::class);

        $data = $this->validated($request);
        $data['company_id'] = session('active_company_id');

        $employee = Employee::create($data);

        app(AuditService::class)->record(
            module: 'Employees', action: 'created', auditable: $employee,
            description: "Empleado {$employee->name} creado.",
            newValues: $employee->toArray(),
        );

        return redirect()->route('admin.employees.index')->with('status', "Empleado {$employee->name} creado.");
    }

    public function edit(Employee $employee): View
    {
        Gate::authorize('update', $employee);

        $companyId = session('active_company_id');
        $branches  = Branch::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();
        $users     = User::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();

        return view('admin.employees.form', compact('employee', 'branches', 'users'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $old  = $employee->toArray();
        $data = $this->validated($request);
        $employee->update($data);

        app(AuditService::class)->record(
            module: 'Employees', action: 'updated', auditable: $employee,
            description: "Empleado {$employee->name} actualizado.",
            oldValues: $old, newValues: $employee->toArray(),
        );

        return redirect()->route('admin.employees.index')->with('status', "Empleado {$employee->name} actualizado.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'branch_id'          => 'nullable|exists:branches,id',
            'user_id'            => 'nullable|exists:users,id',
            'name'               => 'required|string|max:150',
            'document_number'    => 'nullable|string|max:50',
            'phone'              => 'nullable|string|max:50',
            'address'            => 'nullable|string|max:500',
            'position'           => 'nullable|string|max:100',
            'salary_type'        => 'required|in:FIXED,COMMISSION,MIXED',
            'base_salary'        => 'required|numeric|min:0',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
            'status'             => 'sometimes|in:ACTIVE,INACTIVE,TERMINATED',
            'hired_at'           => 'nullable|date',
            'terminated_at'      => 'nullable|date|after_or_equal:hired_at',
        ]);
    }
}
