<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\BetType;
use App\Models\Branch;
use App\Models\CashSession;
use App\Models\Draw;
use App\Models\LicenseState;
use App\Models\Lottery;
use App\Models\PayoutRule;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Cash\CashService;
use App\Services\Sales\TicketSaleService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountingAndCashAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->createValidLicenseState();

        $this->post(route('setup.initial.store'), [
            'company_name' => 'Banca Admin',
            'legal_name' => 'Banca Admin SRL',
            'rnc' => '123456789',
            'phone' => '809-555-0000',
            'company_email' => 'admin@example.com',
            'address' => 'Calle Test #1',
            'branch_code' => 'ADM01',
            'branch_name' => 'Sucursal Central',
            'admin_name' => 'Admin Test',
            'admin_username' => 'admin',
            'admin_email' => 'owner@example.com',
            'admin_password' => 'Password1234',
            'admin_password_confirmation' => 'Password1234',
        ]);

        $this->admin = auth()->user();
        $this->branch = Branch::query()->firstOrFail();
    }

    public function test_sale_auto_creates_default_accounts_when_catalog_is_empty(): void
    {
        $this->assertSame(0, AccountingAccount::query()->count());

        $this->branch->forceFill([
            'cash_control_enabled' => true,
            'accounting_enabled' => true,
            'can_sell_online' => true,
        ])->save();

        $lottery = Lottery::query()->create([
            'company_id' => $this->branch->company_id,
            'name' => 'Loteria Test',
            'code' => 'TEST',
            'country' => 'DO',
            'status' => 'ACTIVE',
        ]);

        $draw = Draw::query()->create([
            'company_id' => $this->branch->company_id,
            'lottery_id' => $lottery->id,
            'name' => 'Noche',
            'draw_date' => now()->toDateString(),
            'scheduled_time' => '20:00',
            'close_time' => '23:59',
            'status' => 'OPEN',
        ]);

        $betType = BetType::query()->create([
            'company_id' => $this->branch->company_id,
            'code' => 'QUINIELA',
            'name' => 'Quiniela',
            'digits_count' => 2,
            'min_numbers' => 1,
            'max_numbers' => 1,
            'requires_position' => false,
            'status' => 'ACTIVE',
        ]);

        PayoutRule::query()->create([
            'company_id' => $this->branch->company_id,
            'bet_type_id' => $betType->id,
            'match_type' => 'DIRECT',
            'payout_multiplier' => '72.00',
            'effective_from' => now()->subDay(),
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);

        $session = app(CashService::class)->open($this->branch, $this->admin, '5000.00');

        $ticket = app(TicketSaleService::class)->sell(
            branch: $this->branch,
            draw: $draw,
            user: $this->admin,
            plays: [[
                'bet_type_id' => $betType->id,
                'number_value' => '25',
                'amount' => '100.00',
            ]],
            cashSession: $session,
        );

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame(12, AccountingAccount::query()->count());
        $this->assertDatabaseHas('accounting_accounts', [
            'company_id' => $this->branch->company_id,
            'code' => '1100',
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'Ticket',
            'source_id' => $ticket->id,
        ]);
    }

    public function test_admin_can_view_company_wide_cash_sessions_and_detail_activity(): void
    {
        $this->assertTrue($this->admin->hasPermission('branches.view'));

        $otherBranch = Branch::query()->create([
            'company_id' => $this->branch->company_id,
            'code' => 'NORTE01',
            'name' => 'Sucursal Norte',
            'status' => 'ACTIVE',
            'can_sell_online' => true,
            'cash_control_enabled' => true,
            'accounting_enabled' => true,
        ]);

        $cashierRole = Role::query()->where('slug', 'CASHIER')->whereNull('company_id')->firstOrFail();
        $cashier = User::query()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $otherBranch->id,
            'role_id' => $cashierRole->id,
            'name' => 'Cajero Norte',
            'username' => 'cajero-norte',
            'email' => 'cajero-norte@example.com',
            'password' => Hash::make('Password1234'),
            'status' => 'ACTIVE',
            'must_change_password' => false,
        ]);

        $session = app(CashService::class)->open($otherBranch, $cashier, '1500.00');
        app(CashService::class)->recordMovement($session, $cashier, 'SALE', '100.00', 'IN', 'Venta ticket #NORTE-001');

        $ticket = Ticket::query()->create([
            'company_id' => $otherBranch->company_id,
            'branch_id' => $otherBranch->id,
            'user_id' => $cashier->id,
            'cash_session_id' => $session->id,
            'ticket_number' => 'NORTE-001',
            'sale_mode' => 'ONLINE',
            'total_amount' => '100.00',
            'total_possible_prize' => '7200.00',
            'status' => 'ACTIVE',
            'sold_at' => now(),
        ]);

        $this->get(route('admin.cash.index'))
            ->assertOk()
            ->assertSee('Sucursal Norte')
            ->assertSee('Cajero Norte')
            ->assertSee('RD$ 100.00');

        $this->get(route('admin.cash.show', $session))
            ->assertOk()
            ->assertSee('Caja #'.$session->id)
            ->assertSee('Venta ticket #NORTE-001')
            ->assertSee('NORTE-001')
            ->assertSee('Cajero Norte');
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
            'expires_at' => now()->addMonth(),
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
