<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashIncident;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLoan;
use App\Models\JournalEntry;
use App\Models\PayrollPeriod;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Payroll\PayrollService;
use Database\Seeders\AccountingAccountSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Branch $branch;
    private PayrollService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $company = Company::query()->create([
            'name'   => 'Banca Nómina',
            'status' => 'ACTIVE',
        ]);

        $this->branch = Branch::query()->create([
            'company_id' => $company->id,
            'code'       => 'NOM01',
            'name'       => 'Sucursal Nómina',
            'status'     => 'ACTIVE',
        ]);

        $role = Role::query()->where('slug', 'COMPANY_OWNER')->firstOrFail();

        $this->admin = User::query()->create([
            'company_id' => $company->id,
            'branch_id'  => $this->branch->id,
            'name'       => 'Admin Nómina',
            'username'   => 'admin_nom',
            'password'   => Hash::make('Password1234'),
            'role_id'    => $role->id,
            'status'     => 'ACTIVE',
        ]);

        $this->seed(AccountingAccountSeeder::class);

        $this->actingAs($this->admin);
        $this->service = app(PayrollService::class);
    }

    // --------------------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------------------

    private function makeEmployee(array $attrs = []): Employee
    {
        return Employee::create(array_merge([
            'company_id'         => $this->branch->company_id,
            'branch_id'          => $this->branch->id,
            'user_id'            => null,
            'name'               => 'Empleado Test',
            'salary_type'        => 'FIXED',
            'base_salary'        => '20000.00',
            'commission_percent' => '0.00',
            'status'             => 'ACTIVE',
            'hired_at'           => '2026-01-01',
        ], $attrs));
    }

    private function generate(string $start = '2026-05-01', string $end = '2026-05-31'): PayrollPeriod
    {
        return $this->service->generate(
            company:     $this->branch->company,
            frequency:   'MONTHLY',
            periodStart: $start,
            periodEnd:   $end,
            creator:     $this->admin,
        );
    }


    // --------------------------------------------------------------------------
    // generate() — salary & commission
    // --------------------------------------------------------------------------

    public function test_fixed_salary_generates_correct_detail(): void
    {
        $this->makeEmployee(['base_salary' => '25000.00']);

        $period = $this->generate();
        $detail = $period->details->first();

        $this->assertEquals('25000.00', $detail->base_salary);
        $this->assertEquals('0.00', $detail->commission);
        $this->assertEquals('25000.00', $detail->gross_pay);
        $this->assertEquals('0.00', $detail->total_deductions);
        $this->assertEquals('25000.00', $detail->net_pay);

        $period->refresh();
        $this->assertEquals('25000.00', $period->total_gross);
        $this->assertEquals('0.00', $period->total_deductions);
        $this->assertEquals('25000.00', $period->total_net);
        $this->assertEquals('DRAFT', $period->status);
    }

    public function test_commission_is_percentage_of_ticket_sales_in_period(): void
    {
        $this->makeEmployee([
            'base_salary'        => '0.00',
            'commission_percent' => '10.00',
            'user_id'            => $this->admin->id,
        ]);

        // Two tickets sold within the period totaling 5 000
        foreach ([2000, 3000] as $amount) {
            Ticket::create([
                'company_id'   => $this->branch->company_id,
                'branch_id'    => $this->branch->id,
                'user_id'      => $this->admin->id,
                'ticket_number' => 'T-'.rand(1000, 9999),
                'sale_mode'    => 'ONLINE',
                'total_amount' => $amount,
                'status'       => 'ACTIVE',
                'sold_at'      => '2026-05-15 10:00:00',
            ]);
        }

        $detail = $this->generate()->details->first();

        $this->assertEquals('500.00', $detail->commission); // 10 % of 5 000
        $this->assertEquals('500.00', $detail->gross_pay);
        $this->assertEquals('500.00', $detail->net_pay);
    }

    public function test_ticket_outside_period_is_not_counted_in_commission(): void
    {
        $this->makeEmployee([
            'base_salary'        => '0.00',
            'commission_percent' => '10.00',
            'user_id'            => $this->admin->id,
        ]);

        // Ticket from April — should NOT count for May payroll
        Ticket::create([
            'company_id'   => $this->branch->company_id,
            'branch_id'    => $this->branch->id,
            'user_id'      => $this->admin->id,
            'ticket_number' => 'T-APR-001',
            'sale_mode'    => 'ONLINE',
            'total_amount' => '10000.00',
            'status'       => 'ACTIVE',
            'sold_at'      => '2026-04-30 23:59:59',
        ]);

        $detail = $this->generate()->details->first();

        $this->assertEquals('0.00', $detail->commission);
        $this->assertEquals('0.00', $detail->gross_pay);
    }

    public function test_cancelled_ticket_is_excluded_from_commission(): void
    {
        $this->makeEmployee([
            'base_salary'        => '0.00',
            'commission_percent' => '10.00',
            'user_id'            => $this->admin->id,
        ]);

        Ticket::create([
            'company_id'   => $this->branch->company_id,
            'branch_id'    => $this->branch->id,
            'user_id'      => $this->admin->id,
            'ticket_number' => 'T-CANC-001',
            'sale_mode'    => 'ONLINE',
            'total_amount' => '5000.00',
            'status'       => 'CANCELLED',
            'sold_at'      => '2026-05-10 10:00:00',
        ]);

        $detail = $this->generate()->details->first();

        $this->assertEquals('0.00', $detail->commission);
    }

    // --------------------------------------------------------------------------
    // generate() — advance deductions
    // --------------------------------------------------------------------------

    public function test_approved_advance_is_deducted(): void
    {
        $employee = $this->makeEmployee(['base_salary' => '20000.00']);

        EmployeeAdvance::create([
            'company_id'   => $this->branch->company_id,
            'branch_id'    => $this->branch->id,
            'employee_id'  => $employee->id,
            'approved_by'  => $this->admin->id,
            'amount'       => '3000.00',
            'status'       => 'APPROVED',
            'requested_at' => '2026-05-10',
        ]);

        $detail = $this->generate()->details->first();

        $this->assertEquals('3000.00', $detail->advance_deduction);
        $this->assertEquals('3000.00', $detail->total_deductions);
        $this->assertEquals('17000.00', $detail->net_pay);
    }

    public function test_pending_advance_is_not_deducted(): void
    {
        $employee = $this->makeEmployee(['base_salary' => '20000.00']);

        EmployeeAdvance::create([
            'company_id'   => $this->branch->company_id,
            'branch_id'    => $this->branch->id,
            'employee_id'  => $employee->id,
            'amount'       => '5000.00',
            'status'       => 'PENDING',
            'requested_at' => '2026-05-10',
        ]);

        $detail = $this->generate()->details->first();

        $this->assertEquals('0.00', $detail->advance_deduction);
        $this->assertEquals('20000.00', $detail->net_pay);
    }

    // --------------------------------------------------------------------------
    // generate() — loan deductions
    // --------------------------------------------------------------------------

    public function test_loan_installment_is_deducted(): void
    {
        $employee = $this->makeEmployee(['base_salary' => '15000.00']);

        EmployeeLoan::create([
            'company_id'  => $this->branch->company_id,
            'branch_id'   => $this->branch->id,
            'employee_id' => $employee->id,
            'approved_by' => $this->admin->id,
            'principal'   => '12000.00',
            'balance'     => '8000.00',
            'installment' => '2000.00',
            'status'      => 'ACTIVE',
            'started_at'  => '2026-03-01',
        ]);

        $detail = $this->generate()->details->first();

        $this->assertEquals('2000.00', $detail->loan_deduction);
        $this->assertEquals('13000.00', $detail->net_pay);
    }

    public function test_loan_installment_capped_at_remaining_balance(): void
    {
        $employee = $this->makeEmployee(['base_salary' => '15000.00']);

        EmployeeLoan::create([
            'company_id'  => $this->branch->company_id,
            'branch_id'   => $this->branch->id,
            'employee_id' => $employee->id,
            'approved_by' => $this->admin->id,
            'principal'   => '5000.00',
            'balance'     => '1500.00', // less than installment
            'installment' => '2000.00',
            'status'      => 'ACTIVE',
            'started_at'  => '2026-01-01',
        ]);

        $detail = $this->generate()->details->first();

        $this->assertEquals('1500.00', $detail->loan_deduction); // capped at balance
        $this->assertEquals('13500.00', $detail->net_pay);
    }

    // --------------------------------------------------------------------------
    // generate() — cash shortage deductions
    // --------------------------------------------------------------------------

    public function test_resolved_cash_shortage_is_deducted(): void
    {
        $employee = $this->makeEmployee([
            'base_salary' => '20000.00',
            'user_id'     => $this->admin->id,
        ]);

        CashIncident::create([
            'company_id'  => $this->branch->company_id,
            'branch_id'   => $this->branch->id,
            'user_id'     => $this->admin->id,
            'type'        => 'CASH_SHORTAGE',
            'severity'    => 'CRITICAL',
            'status'      => 'RESOLVED',
            'title'       => 'Faltante de caja',
            'description' => 'Faltante en cierre del 2026-05-15',
            'amount'      => '1500.00',
            'resolved_by' => $this->admin->id,
            'resolved_at' => now(),
        ]);

        $detail = $this->generate()->details->first();

        $this->assertEquals('1500.00', $detail->cash_shortage);
        $this->assertEquals('1500.00', $detail->total_deductions);
        $this->assertEquals('18500.00', $detail->net_pay);
    }

    public function test_cash_shortage_outside_period_is_not_deducted(): void
    {
        $employee = $this->makeEmployee([
            'base_salary' => '20000.00',
            'user_id'     => $this->admin->id,
        ]);

        $incident = CashIncident::forceCreate([
            'company_id'  => $this->branch->company_id,
            'branch_id'   => $this->branch->id,
            'user_id'     => $this->admin->id,
            'type'        => 'CASH_SHORTAGE',
            'severity'    => 'CRITICAL',
            'status'      => 'RESOLVED',
            'title'       => 'Faltante de caja',
            'description' => 'Faltante en abril',
            'amount'      => '2000.00',
            'resolved_by' => $this->admin->id,
            'resolved_at' => '2026-04-20 10:00:00',
            'created_at'  => '2026-04-15 10:00:00', // outside May period
            'updated_at'  => '2026-04-20 10:00:00',
        ]);

        $detail = $this->generate()->details->first();

        $this->assertEquals('0.00', $detail->cash_shortage);
        $this->assertEquals('20000.00', $detail->net_pay);
    }

    public function test_dismissed_cash_shortage_is_not_deducted(): void
    {
        $employee = $this->makeEmployee([
            'base_salary' => '20000.00',
            'user_id'     => $this->admin->id,
        ]);

        CashIncident::create([
            'company_id'       => $this->branch->company_id,
            'branch_id'        => $this->branch->id,
            'user_id'          => $this->admin->id,
            'type'             => 'CASH_SHORTAGE',
            'severity'         => 'CRITICAL',
            'status'           => 'DISMISSED', // desestimada — no imputable al cajero
            'title'            => 'Faltante desestimado',
            'description'      => 'Error administrativo, no atribuible al cajero',
            'amount'           => '1500.00',
            'resolved_by'      => $this->admin->id,
            'resolved_at'      => now(),
            'resolution_notes' => 'No imputable al cajero',
        ]);

        $detail = $this->generate()->details->first();

        // DISMISSED no debe descontar nada al cajero
        $this->assertEquals('0.00', $detail->cash_shortage);
        $this->assertEquals('20000.00', $detail->net_pay);
    }

    public function test_open_cash_shortage_is_not_deducted(): void
    {
        $employee = $this->makeEmployee([
            'base_salary' => '20000.00',
            'user_id'     => $this->admin->id,
        ]);

        CashIncident::create([
            'company_id'  => $this->branch->company_id,
            'branch_id'   => $this->branch->id,
            'user_id'     => $this->admin->id,
            'type'        => 'CASH_SHORTAGE',
            'severity'    => 'CRITICAL',
            'status'      => 'OPEN', // not resolved yet
            'title'       => 'Faltante pendiente',
            'description' => 'Faltante sin confirmar',
            'amount'      => '3000.00',
        ]);

        $detail = $this->generate()->details->first();

        $this->assertEquals('0.00', $detail->cash_shortage);
        $this->assertEquals('20000.00', $detail->net_pay);
    }

    public function test_employee_without_user_skips_shortage_deduction(): void
    {
        // Employee has no user_id — shortage check should be skipped
        $employee = $this->makeEmployee([
            'base_salary' => '10000.00',
            'user_id'     => null,
        ]);

        $detail = $this->generate()->details->first();

        $this->assertEquals('0.00', $detail->cash_shortage);
        $this->assertEquals('10000.00', $detail->net_pay);
    }

    // --------------------------------------------------------------------------
    // generate() — combined & edge cases
    // --------------------------------------------------------------------------

    public function test_net_pay_never_goes_below_zero(): void
    {
        $employee = $this->makeEmployee(['base_salary' => '5000.00']);

        EmployeeAdvance::create([
            'company_id'   => $this->branch->company_id,
            'branch_id'    => $this->branch->id,
            'employee_id'  => $employee->id,
            'approved_by'  => $this->admin->id,
            'amount'       => '8000.00', // exceeds salary
            'status'       => 'APPROVED',
            'requested_at' => '2026-05-01',
        ]);

        $detail = $this->generate()->details->first();

        $this->assertEquals('0.00', $detail->net_pay);
    }

    public function test_period_totals_aggregate_all_employees(): void
    {
        $this->makeEmployee(['name' => 'Empleado A', 'base_salary' => '20000.00']);
        $this->makeEmployee(['name' => 'Empleado B', 'base_salary' => '30000.00']);

        $period = $this->generate();
        $period->refresh();

        $this->assertCount(2, $period->details);
        $this->assertEquals('50000.00', $period->total_gross);
        $this->assertEquals('0.00', $period->total_deductions);
        $this->assertEquals('50000.00', $period->total_net);
    }

    // --------------------------------------------------------------------------
    // approve()
    // --------------------------------------------------------------------------

    public function test_approve_transitions_draft_to_approved(): void
    {
        $this->makeEmployee();
        $period = $this->generate();

        $approved = $this->service->approve($period, $this->admin);

        $this->assertEquals('APPROVED', $approved->status);
        $this->assertNotNull($approved->approved_at);
        $this->assertEquals($this->admin->id, $approved->approved_by);
    }

    public function test_approve_throws_when_period_is_not_draft(): void
    {
        $this->makeEmployee();
        $period = $this->generate();
        $this->service->approve($period, $this->admin);

        $this->expectException(\LogicException::class);
        $this->service->approve($period->fresh(), $this->admin);
    }

    // --------------------------------------------------------------------------
    // pay()
    // --------------------------------------------------------------------------

    public function test_pay_transitions_approved_to_paid(): void
    {
        $this->makeEmployee();
        $period = $this->service->approve($this->generate(), $this->admin);

        $paid = $this->service->pay($period, $this->admin);

        $this->assertEquals('PAID', $paid->status);
        $this->assertNotNull($paid->paid_at);
    }

    public function test_pay_throws_on_draft_period(): void
    {
        $this->makeEmployee();
        $period = $this->generate();

        $this->expectException(\LogicException::class);
        $this->service->pay($period, $this->admin);
    }

    public function test_pay_marks_advances_as_paid(): void
    {
        $employee = $this->makeEmployee(['base_salary' => '20000.00']);

        $advance = EmployeeAdvance::create([
            'company_id'   => $this->branch->company_id,
            'branch_id'    => $this->branch->id,
            'employee_id'  => $employee->id,
            'approved_by'  => $this->admin->id,
            'amount'       => '2000.00',
            'status'       => 'APPROVED',
            'requested_at' => '2026-05-10',
        ]);

        $period = $this->service->approve($this->generate(), $this->admin);
        $this->service->pay($period, $this->admin);

        $this->assertEquals('PAID', $advance->fresh()->status);
        $this->assertNotNull($advance->fresh()->paid_at);
    }

    public function test_pay_reduces_loan_balance_by_installment(): void
    {
        $employee = $this->makeEmployee(['base_salary' => '20000.00']);

        $loan = EmployeeLoan::create([
            'company_id'  => $this->branch->company_id,
            'branch_id'   => $this->branch->id,
            'employee_id' => $employee->id,
            'approved_by' => $this->admin->id,
            'principal'   => '10000.00',
            'balance'     => '10000.00',
            'installment' => '1000.00',
            'status'      => 'ACTIVE',
            'started_at'  => '2026-01-01',
        ]);

        $period = $this->service->approve($this->generate(), $this->admin);
        $this->service->pay($period, $this->admin);

        $this->assertEquals('9000.00', $loan->fresh()->balance);
        $this->assertEquals('ACTIVE', $loan->fresh()->status);
    }

    public function test_pay_marks_loan_paid_off_when_balance_reaches_zero(): void
    {
        $employee = $this->makeEmployee(['base_salary' => '10000.00']);

        $loan = EmployeeLoan::create([
            'company_id'  => $this->branch->company_id,
            'branch_id'   => $this->branch->id,
            'employee_id' => $employee->id,
            'approved_by' => $this->admin->id,
            'principal'   => '1000.00',
            'balance'     => '1000.00',
            'installment' => '1000.00', // final installment
            'status'      => 'ACTIVE',
            'started_at'  => '2026-01-01',
        ]);

        $period = $this->service->approve($this->generate(), $this->admin);
        $this->service->pay($period, $this->admin);

        $this->assertEquals('0.00', $loan->fresh()->balance);
        $this->assertEquals('PAID_OFF', $loan->fresh()->status);
    }

    public function test_pay_creates_cash_movement_when_session_provided(): void
    {
        $this->makeEmployee(['base_salary' => '15000.00']);

        $cashSession = CashSession::create([
            'company_id'   => $this->branch->company_id,
            'branch_id'    => $this->branch->id,
            'user_id'      => $this->admin->id,
            'opened_by'    => $this->admin->id,
            'status'       => 'OPEN',
            'opening_amount' => '50000.00',
            'opened_at'    => now(),
        ]);

        $period = $this->service->approve($this->generate(), $this->admin);
        $this->service->pay($period, $this->admin, $cashSession);

        $this->assertDatabaseHas('cash_movements', [
            'cash_session_id' => $cashSession->id,
            'type'            => 'PAYROLL',
            'direction'       => 'OUT',
        ]);
    }

    public function test_pay_creates_journal_entry_when_branch_available(): void
    {
        $this->makeEmployee(['base_salary' => '15000.00']);

        $cashSession = CashSession::create([
            'company_id'   => $this->branch->company_id,
            'branch_id'    => $this->branch->id,
            'user_id'      => $this->admin->id,
            'opened_by'    => $this->admin->id,
            'status'       => 'OPEN',
            'opening_amount' => '50000.00',
            'opened_at'    => now(),
        ]);

        $period = $this->service->approve($this->generate(), $this->admin);
        $this->service->pay($period, $this->admin, $cashSession);

        $this->assertDatabaseHas('journal_entries', [
            'company_id' => $this->branch->company_id,
            'branch_id'  => $this->branch->id,
        ]);
    }

    public function test_pay_without_cash_session_skips_cash_movement(): void
    {
        $this->makeEmployee(['base_salary' => '10000.00']);

        $period = $this->service->approve($this->generate(), $this->admin);
        $this->service->pay($period, $this->admin, cashSession: null);

        $this->assertDatabaseCount('cash_movements', 0);
    }
}
