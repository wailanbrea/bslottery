<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\CashIncident;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLoan;
use App\Models\PayrollDetail;
use App\Models\PayrollPeriod;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(
        private readonly AccountingService $accountingService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Creates a draft payroll period and calculates each employee's pay.
     */
    public function generate(
        Company $company,
        string $frequency,
        string $periodStart,
        string $periodEnd,
        User $creator,
    ): PayrollPeriod {
        return DB::transaction(function () use ($company, $frequency, $periodStart, $periodEnd, $creator): PayrollPeriod {
            $period = PayrollPeriod::create([
                'company_id'   => $company->id,
                'frequency'    => $frequency,
                'period_start' => $periodStart,
                'period_end'   => $periodEnd,
                'status'       => 'DRAFT',
                'created_by'   => $creator->id,
            ]);

            $employees = Employee::where('company_id', $company->id)
                ->where('status', 'ACTIVE')
                ->get();

            $totalGross = '0.00';
            $totalDeductions = '0.00';
            $totalNet = '0.00';

            foreach ($employees as $employee) {
                $detail = $this->calculateDetail($period, $employee, $periodStart, $periodEnd);
                $totalGross = bcadd($totalGross, $detail->gross_pay, 2);
                $totalDeductions = bcadd($totalDeductions, $detail->total_deductions, 2);
                $totalNet = bcadd($totalNet, $detail->net_pay, 2);
            }

            $period->update([
                'total_gross'      => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net'        => $totalNet,
            ]);

            $this->auditService->record(
                module: 'Payroll',
                action: 'generated',
                auditable: $period,
                description: "Nómina {$frequency} {$periodStart}→{$periodEnd} generada con ".count($employees)." empleados.",
            );

            return $period->load('details.employee');
        });
    }

    /**
     * Approves the payroll period (locks detail rows).
     */
    public function approve(PayrollPeriod $period, User $approver): PayrollPeriod
    {
        if (! $period->isDraft()) {
            throw new \LogicException('Solo se puede aprobar una nómina en estado DRAFT.');
        }

        $period->update([
            'status'      => 'APPROVED',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->auditService->record(
            module: 'Payroll',
            action: 'approved',
            auditable: $period,
            description: "Nómina #{$period->id} aprobada.",
        );

        return $period->fresh();
    }

    /**
     * Pays the payroll: marks PAID, deducts from cash/advances/loans, generates accounting entry.
     */
    public function pay(PayrollPeriod $period, User $payer, ?CashSession $cashSession = null): PayrollPeriod
    {
        if (! $period->isApproved()) {
            throw new \LogicException('Solo se puede pagar una nómina aprobada.');
        }

        return DB::transaction(function () use ($period, $payer, $cashSession): PayrollPeriod {
            $period->load('details.employee');

            foreach ($period->details as $detail) {
                $this->payDetail($detail, $payer, $cashSession);
            }

            $period->update([
                'status'  => 'PAID',
                'paid_at' => now(),
            ]);

            // Cash movement for total payout
            if ($cashSession) {
                CashMovement::create([
                    'company_id'      => $period->company_id,
                    'branch_id'       => $cashSession->branch_id,
                    'cash_session_id' => $cashSession->id,
                    'user_id'         => $payer->id,
                    'type'            => 'PAYROLL',
                    'direction'       => 'OUT',
                    'amount'          => $period->total_net,
                    'description'     => "Pago nómina #{$period->id} período {$period->period_start->format('d/m/Y')}–{$period->period_end->format('d/m/Y')}",
                    'reference_type'  => 'PayrollPeriod',
                    'reference_id'    => $period->id,
                ]);
            }

            // Formal accounting journal entry
            $branchId = $cashSession?->branch_id ?? $payer->branch_id;
            if ($branchId) {
                $this->accountingService->entryForPayroll(
                    companyId: $period->company_id,
                    branchId: $branchId,
                    amount: $period->total_net,
                    createdBy: $payer,
                    payrollId: $period->id,
                );
            }

            $this->auditService->record(
                module: 'Payroll',
                action: 'paid',
                auditable: $period,
                description: "Nómina #{$period->id} pagada. Total neto: RD$".number_format((float) $period->total_net, 2).".",
            );

            return $period->fresh();
        });
    }

    private function calculateDetail(PayrollPeriod $period, Employee $employee, string $start, string $end): PayrollDetail
    {
        // Base salary (always included)
        $baseSalary = (string) $employee->base_salary;

        // Commission: % of ticket sales for this employee in the period
        $commission = '0.00';
        if ((float) $employee->commission_percent > 0) {
            $sales = Ticket::where('user_id', $employee->user_id)
                ->where('status', 'ACTIVE')
                ->whereBetween('sold_at', [$start.' 00:00:00', $end.' 23:59:59'])
                ->sum('total_amount');
            $commission = bcmul((string) $sales, bcdiv((string) $employee->commission_percent, '100', 6), 2);
        }

        $grossPay = bcadd($baseSalary, $commission, 2);

        // Advances approved for this period
        $advanceDeduction = '0.00';
        $advances = EmployeeAdvance::where('employee_id', $employee->id)
            ->where('status', 'APPROVED')
            ->where('paid_at', null)
            ->get();
        foreach ($advances as $adv) {
            $advanceDeduction = bcadd($advanceDeduction, (string) $adv->amount, 2);
        }

        // Active loans — deduct one installment
        $loanDeduction = '0.00';
        $loans = EmployeeLoan::where('employee_id', $employee->id)
            ->where('status', 'ACTIVE')
            ->where('balance', '>', 0)
            ->get();
        foreach ($loans as $loan) {
            $installment = (string) $loan->currentInstallment();
            $loanDeduction = bcadd($loanDeduction, $installment, 2);
        }

        // Cash shortage: sum of RESOLVED CASH_SHORTAGE incidents for this employee's user in the period
        $cashShortage = '0.00';
        if ($employee->user_id) {
            $shortageSum = CashIncident::where('user_id', $employee->user_id)
                ->where('type', 'CASH_SHORTAGE')
                ->where('status', 'RESOLVED')
                ->whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59'])
                ->sum('amount');
            $cashShortage = bcmul((string) ($shortageSum ?? 0), '1', 2);
        }

        $totalDeductions = bcadd(bcadd(bcadd($advanceDeduction, $loanDeduction, 2), $cashShortage, 2), '0', 2);
        $netPay = bcsub($grossPay, $totalDeductions, 2);
        if (bccomp($netPay, '0', 2) < 0) {
            $netPay = '0.00';
        }

        return PayrollDetail::create([
            'payroll_period_id'  => $period->id,
            'company_id'         => $period->company_id,
            'branch_id'          => $employee->branch_id ?? $period->company_id,
            'employee_id'        => $employee->id,
            'base_salary'        => $baseSalary,
            'commission'         => $commission,
            'bonus'              => '0.00',
            'gross_pay'          => $grossPay,
            'advance_deduction'  => $advanceDeduction,
            'loan_deduction'     => $loanDeduction,
            'cash_shortage'      => $cashShortage,
            'other_deductions'   => '0.00',
            'total_deductions'   => $totalDeductions,
            'net_pay'            => $netPay,
        ]);
    }

    private function payDetail(PayrollDetail $detail, User $payer, ?CashSession $cashSession): void
    {
        // Mark advances as PAID
        EmployeeAdvance::where('employee_id', $detail->employee_id)
            ->where('status', 'APPROVED')
            ->whereNull('paid_at')
            ->update([
                'status'  => 'PAID',
                'paid_at' => now()->toDateString(),
            ]);

        // Reduce loan balances
        $loans = EmployeeLoan::where('employee_id', $detail->employee_id)
            ->where('status', 'ACTIVE')
            ->where('balance', '>', 0)
            ->get();

        foreach ($loans as $loan) {
            $installment = (float) $loan->currentInstallment();
            $newBalance = max(0, (float) $loan->balance - $installment);
            $loan->update([
                'balance' => $newBalance,
                'status'  => $newBalance <= 0 ? 'PAID_OFF' : 'ACTIVE',
            ]);
        }

        $detail->update(['status' => 'PAID']);
    }
}
