<?php

namespace Tests\Feature;

use App\Models\BetType;
use App\Models\Branch;
use App\Models\CashSession;
use App\Models\Company;
use App\Models\Draw;
use App\Models\LicenseState;
use App\Models\Lottery;
use App\Models\Permission;
use App\Models\PrintJob;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketDetail;
use App\Models\User;
use Database\Seeders\AccountingAccountSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OperationalReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Company $company;
    private Branch $mainBranch;
    private Branch $secondBranch;
    private Lottery $lottery;
    private Draw $draw;
    private BetType $betType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->createValidLicenseState();

        $this->post(route('setup.initial.store'), [
            'company_name' => 'Banca Reportes',
            'legal_name' => 'Banca Reportes SRL',
            'rnc' => '123456789',
            'phone' => '809-555-0000',
            'company_email' => 'reportes@example.com',
            'address' => 'Calle Test #1',
            'branch_code' => 'CENTRAL',
            'branch_name' => 'Central',
            'admin_name' => 'Admin Reportes',
            'admin_username' => 'admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'Password1234',
            'admin_password_confirmation' => 'Password1234',
        ]);

        $this->seed(AccountingAccountSeeder::class);

        $this->admin = auth()->user();
        $this->company = Company::query()->firstOrFail();
        $this->mainBranch = Branch::query()->firstOrFail();
        $this->secondBranch = Branch::query()->create([
            'company_id' => $this->company->id,
            'code' => 'NORTE',
            'name' => 'Norte',
            'status' => 'ACTIVE',
        ]);

        $this->lottery = Lottery::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Lotería Test',
            'code' => 'TEST',
            'country' => 'DO',
            'status' => 'ACTIVE',
        ]);

        $this->draw = Draw::query()->create([
            'company_id' => $this->company->id,
            'lottery_id' => $this->lottery->id,
            'name' => 'Noche',
            'draw_date' => now()->toDateString(),
            'scheduled_time' => '20:00',
            'close_time' => '23:59',
            'status' => 'OPEN',
        ]);

        $this->betType = BetType::query()->create([
            'company_id' => $this->company->id,
            'code' => 'QUINIELA',
            'name' => 'Quiniela',
            'digits_count' => 2,
            'min_numbers' => 1,
            'max_numbers' => 1,
            'requires_position' => false,
            'status' => 'ACTIVE',
        ]);

        $this->createTicket($this->mainBranch, 'CENTRAL-001', '100.00', '7200.00', '25');
        $this->createTicket($this->secondBranch, 'NORTE-001', '200.00', '14400.00', '50');
    }

    public function test_reports_summary_uses_company_scope_and_date_filters(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reports.index', [
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Tickets vendidos')
            ->assertSee('RD$ 300.00')
            ->assertSee('Reportes operativos');
    }

    public function test_sales_by_lottery_can_filter_by_lottery_and_draw(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reports.sales-by-lottery', [
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
                'lottery_id' => $this->lottery->id,
                'draw_id' => $this->draw->id,
            ]))
            ->assertOk()
            ->assertSee('Ventas por lotería y sorteo')
            ->assertSee('Lotería Test')
            ->assertSee('Noche')
            ->assertSee('RD$ 300.00');
    }

    public function test_branch_scoped_report_user_cannot_filter_into_other_branch(): void
    {
        $role = Role::query()->create([
            'name' => 'Reportes Sucursal',
            'slug' => 'REPORT_BRANCH',
            'level' => 20,
            'status' => 'ACTIVE',
        ]);
        $role->permissions()->attach(Permission::query()->where('slug', 'reports.view')->firstOrFail());

        $user = User::query()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->mainBranch->id,
            'name' => 'Reporte Central',
            'username' => 'reporte-central',
            'email' => 'reporte-central@example.com',
            'password' => Hash::make('Password1234'),
            'role_id' => $role->id,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($user)
            ->get(route('admin.reports.sales-by-branch', [
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
                'branch_id' => $this->secondBranch->id,
            ]))
            ->assertOk()
            ->assertSee('Central')
            ->assertSee('RD$ 100.00')
            ->assertDontSee('Norte')
            ->assertDontSee('RD$ 200.00');
    }

    public function test_cash_summary_reports_sessions_with_financial_totals(): void
    {
        CashSession::query()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->mainBranch->id,
            'user_id' => $this->admin->id,
            'opened_by' => $this->admin->id,
            'closed_by' => $this->admin->id,
            'opening_amount' => '500.00',
            'expected_cash' => '600.00',
            'counted_cash' => '590.00',
            'sales_total' => '100.00',
            'prizes_paid_total' => '0.00',
            'status' => 'CLOSED',
            'opened_at' => now(),
            'closed_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.cash-summary', [
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Cuadre de caja')
            ->assertSee('RD$ 600.00')
            ->assertSee('-RD$ 10.00');
    }

    public function test_reprinted_tickets_report_uses_print_jobs_as_auditable_source(): void
    {
        $ticket = $this->createTicket($this->mainBranch, 'CENTRAL-RP1', '75.00', '5400.00', '64');
        $ticket->forceFill(['print_count' => 2])->save();

        PrintJob::query()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->mainBranch->id,
            'ticket_id' => $ticket->id,
            'type' => 'REPRINT',
            'content' => 'REIMPRESION CENTRAL-RP1',
            'status' => 'PRINTED',
            'attempts' => 1,
            'printed_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.reprinted', [
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
                'branch_id' => $this->mainBranch->id,
            ]))
            ->assertOk()
            ->assertSee('Tickets reimpresos')
            ->assertSee('CENTRAL-RP1')
            ->assertSee('Central')
            ->assertSee('admin')
            ->assertSee('PRINTED');
    }

    private function createTicket(Branch $branch, string $number, string $amount, string $possiblePrize, string $playedNumber): Ticket
    {
        $ticket = Ticket::query()->create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'user_id' => $this->admin->id,
            'ticket_number' => $number,
            'sale_mode' => 'ONLINE',
            'total_amount' => $amount,
            'total_possible_prize' => $possiblePrize,
            'status' => 'ACTIVE',
            'sold_at' => now(),
        ]);

        TicketDetail::query()->create([
            'ticket_id' => $ticket->id,
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'lottery_id' => $this->lottery->id,
            'draw_id' => $this->draw->id,
            'bet_type_id' => $this->betType->id,
            'number_value' => $playedNumber,
            'normalized_number' => $playedNumber,
            'amount' => $amount,
            'payout_multiplier' => '72.00',
            'possible_prize' => $possiblePrize,
            'status' => 'ACTIVE',
        ]);

        return $ticket;
    }

    private function createValidLicenseState(): LicenseState
    {
        return LicenseState::query()->create([
            'project_code' => 'BSLOTTERY',
            'license_key' => 'LIC-TEST',
            'device_fingerprint' => 'test-installation',
            'device_name' => 'Servidor principal',
            'device_type' => 'web',
            'client_location_code' => 'principal',
            'domain' => 'localhost',
            'app_version' => '1.0.0',
            'status' => 'active',
            'reason_code' => 'LICENSE_ACTIVE',
            'expires_at' => now()->addYear(),
            'last_validation_success' => true,
            'last_validation_at' => now(),
            'last_server_time' => now(),
            'last_seen_system_time' => now(),
            'features' => ['offline_mode' => true],
            'limits' => ['offline_grace_hours' => 72],
            'metadata' => [],
            'client' => [],
            'location' => [],
            'is_active' => true,
        ]);
    }
}
