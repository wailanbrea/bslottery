<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EmployeeLoanController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', EmployeeLoan::class);

        $companyId = session('active_company_id');
        $branchId  = session('active_branch_id');

        $loans = EmployeeLoan::with('employee', 'branch', 'approver')
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('started_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.employees.loans', compact('loans'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', EmployeeLoan::class);

        $data = $request->validate([
            'employee_id'  => 'required|exists:employees,id',
            'principal'    => 'required|numeric|min:1',
            'installment'  => 'required|numeric|min:1',
            'started_at'   => 'required|date',
            'notes'        => 'nullable|string|max:500',
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $data['company_id']  = $employee->company_id;
        $data['branch_id']   = $employee->branch_id;
        $data['balance']     = $data['principal'];
        $data['status']      = 'ACTIVE';
        $data['approved_by'] = auth()->id();

        $loan = EmployeeLoan::create($data);

        app(AuditService::class)->record(
            module: 'Employees', action: 'loan_created', auditable: $loan,
            description: "Préstamo RD$".number_format((float)$loan->principal,2)." creado para {$employee->name}. Cuota: RD$".number_format((float)$loan->installment,2).".",
        );

        return back()->with('status', 'Préstamo registrado.');
    }

    public function cancel(EmployeeLoan $loan): RedirectResponse
    {
        Gate::authorize('update', $loan);

        $loan->update(['status' => 'CANCELLED']);

        app(AuditService::class)->record(
            module: 'Employees', action: 'loan_cancelled', auditable: $loan,
            description: "Préstamo #{$loan->id} cancelado.",
        );

        return back()->with('status', 'Préstamo cancelado.');
    }
}
