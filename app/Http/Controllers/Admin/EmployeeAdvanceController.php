<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EmployeeAdvanceController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', EmployeeAdvance::class);

        $companyId = session('active_company_id');
        $branchId  = session('active_branch_id');

        $advances = EmployeeAdvance::with('employee', 'branch', 'approver')
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('requested_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.employees.advances', compact('advances'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', EmployeeAdvance::class);

        $data = $request->validate([
            'employee_id'  => 'required|exists:employees,id',
            'amount'       => 'required|numeric|min:1',
            'requested_at' => 'required|date',
            'notes'        => 'nullable|string|max:500',
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $data['company_id'] = $employee->company_id;
        $data['branch_id']  = $employee->branch_id;
        $data['status']     = 'PENDING';

        $advance = EmployeeAdvance::create($data);

        app(AuditService::class)->record(
            module: 'Employees', action: 'advance_created', auditable: $advance,
            description: "Avance RD$".number_format((float)$advance->amount,2)." solicitado para {$employee->name}.",
        );

        return back()->with('status', 'Avance registrado.');
    }

    public function approve(Request $request, EmployeeAdvance $advance): RedirectResponse
    {
        Gate::authorize('update', $advance);

        $data = $request->validate(['action' => 'required|in:approve,reject']);

        $advance->update([
            'status'      => $data['action'] === 'approve' ? 'APPROVED' : 'REJECTED',
            'approved_by' => auth()->id(),
        ]);

        $label = $data['action'] === 'approve' ? 'aprobado' : 'rechazado';

        app(AuditService::class)->record(
            module: 'Employees', action: 'advance_'.$data['action'].'d', auditable: $advance,
            description: "Avance #{$advance->id} {$label}.",
        );

        return back()->with('status', "Avance {$label}.");
    }
}
