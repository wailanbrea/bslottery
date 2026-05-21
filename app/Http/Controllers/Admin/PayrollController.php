<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLoan;
use App\Models\PayrollDetail;
use App\Models\PayrollPeriod;
use App\Services\Payroll\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(private readonly PayrollService $payrollService) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', PayrollPeriod::class);

        $companyId = session('active_company_id');

        $periods = PayrollPeriod::where('company_id', $companyId)
            ->withCount('details')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('period_start')
            ->paginate(20)
            ->withQueryString();

        $activeEmployees = Employee::where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->count();

        $pendingAdvances = EmployeeAdvance::where('company_id', $companyId)
            ->where('status', 'PENDING')
            ->count();

        $activeLoans = EmployeeLoan::where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->where('balance', '>', 0)
            ->count();

        return view('admin.payroll.index', compact('periods', 'activeEmployees', 'pendingAdvances', 'activeLoans'));
    }

    public function show(PayrollPeriod $payrollPeriod): View
    {
        Gate::authorize('view', $payrollPeriod);

        $payrollPeriod->load('details.employee.branch', 'creator', 'approver');

        return view('admin.payroll.show', ['period' => $payrollPeriod]);
    }

    public function generate(Request $request): RedirectResponse
    {
        Gate::authorize('create', PayrollPeriod::class);

        $data = $request->validate([
            'frequency'    => 'required|in:WEEKLY,BIWEEKLY,MONTHLY',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
        ]);

        $company = auth()->user()->company;

        // Prevent duplicate periods
        $existing = PayrollPeriod::where('company_id', $company->id)
            ->where('period_start', $data['period_start'])
            ->where('period_end', $data['period_end'])
            ->first();

        if ($existing) {
            return back()->withErrors(['period_start' => 'Ya existe una nómina para este período.']);
        }

        $period = $this->payrollService->generate(
            company: $company,
            frequency: $data['frequency'],
            periodStart: $data['period_start'],
            periodEnd: $data['period_end'],
            creator: auth()->user(),
        );

        return redirect()->route('admin.payroll.show', $period)
            ->with('status', "Nómina generada con {$period->details->count()} empleados.");
    }

    public function approve(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        Gate::authorize('update', $payrollPeriod);

        $this->payrollService->approve($payrollPeriod, auth()->user());

        return back()->with('status', 'Nómina aprobada.');
    }

    public function pay(Request $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        Gate::authorize('update', $payrollPeriod);

        $cashSession = null;
        if ($request->filled('cash_session_id')) {
            $cashSession = CashSession::find($request->cash_session_id);
        }

        $this->payrollService->pay($payrollPeriod, auth()->user(), $cashSession);

        return redirect()->route('admin.payroll.index')
            ->with('status', "Nómina #{$payrollPeriod->id} pagada. Neto: RD$".number_format((float)$payrollPeriod->total_net, 2).".");
    }

    public function updateDetail(Request $request, PayrollPeriod $payrollPeriod, PayrollDetail $payrollDetail): RedirectResponse
    {
        Gate::authorize('update', $payrollPeriod);

        if (! $payrollPeriod->isDraft()) {
            return back()->withErrors(['detail' => 'Solo se pueden editar nóminas en estado BORRADOR.']);
        }

        $data = $request->validate([
            'bonus'            => 'required|numeric|min:0',
            'other_deductions' => 'required|numeric|min:0',
        ]);

        $bonus     = bcadd((string) $data['bonus'], '0', 2);
        $otherDed  = bcadd((string) $data['other_deductions'], '0', 2);

        $grossPay = bcadd(bcadd($payrollDetail->base_salary, $payrollDetail->commission, 2), $bonus, 2);

        $totalDeductions = bcadd(
            bcadd($payrollDetail->advance_deduction, $payrollDetail->loan_deduction, 2),
            bcadd($payrollDetail->cash_shortage, $otherDed, 2),
            2
        );

        $netPay = bcsub($grossPay, $totalDeductions, 2);
        if (bccomp($netPay, '0', 2) < 0) {
            $netPay = '0.00';
        }

        $payrollDetail->update([
            'bonus'            => $bonus,
            'other_deductions' => $otherDed,
            'gross_pay'        => $grossPay,
            'total_deductions' => $totalDeductions,
            'net_pay'          => $netPay,
        ]);

        // Recalculate period totals
        $payrollPeriod->load('details');
        $totalGross = $payrollPeriod->details->reduce(
            fn ($carry, $d) => bcadd($carry, $d->gross_pay, 2), '0'
        );
        $totalDed = $payrollPeriod->details->reduce(
            fn ($carry, $d) => bcadd($carry, $d->total_deductions, 2), '0'
        );
        $totalNet = $payrollPeriod->details->reduce(
            fn ($carry, $d) => bcadd($carry, $d->net_pay, 2), '0'
        );

        $payrollPeriod->update([
            'total_gross'      => $totalGross,
            'total_deductions' => $totalDed,
            'total_net'        => $totalNet,
        ]);

        return back()->with('status', "Detalle de {$payrollDetail->employee->name} actualizado.");
    }
}
