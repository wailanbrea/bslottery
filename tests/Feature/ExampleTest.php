<?php

namespace Tests\Feature;

use App\Models\LicenseState;
use App\Models\AuditLog;
use App\Models\BetType;
use App\Models\Branch;
use App\Models\Device;
use App\Models\Draw;
use App\Models\Lottery;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketDetail;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_unlicensed_application_redirects_to_activation(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('license.activate'));
    }

    public function test_licensed_application_without_setup_redirects_to_initial_setup(): void
    {
        $this->createValidLicenseState();

        $response = $this->get('/');

        $response->assertRedirect(route('setup.initial'));
    }

    public function test_initial_setup_creates_company_branch_admin_and_logs_in(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->createValidLicenseState();

        $response = $this->post(route('setup.initial.store'), [
            'company_name' => 'Demo Market',
            'legal_name' => 'Comercial Demo SRL',
            'rnc' => '101010101',
            'phone' => '809-555-0000',
            'company_email' => 'admin@example.com',
            'address' => 'Av. Principal #1',
            'branch_code' => 'principal',
            'branch_name' => 'Sucursal Principal',
            'admin_name' => 'Administrador',
            'admin_username' => 'admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'Password1234',
            'admin_password_confirmation' => 'Password1234',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('branches', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'setup',
            'action' => 'completed',
        ]);
    }

    public function test_created_admin_can_login_and_logout(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->createValidLicenseState();

        $this->post(route('setup.initial.store'), [
            'company_name' => 'Demo Market',
            'legal_name' => 'Comercial Demo SRL',
            'rnc' => '101010101',
            'phone' => '809-555-0000',
            'company_email' => 'admin@example.com',
            'address' => 'Av. Principal #1',
            'branch_code' => 'principal',
            'branch_name' => 'Sucursal Principal',
            'admin_name' => 'Administrador',
            'admin_username' => 'admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'Password1234',
            'admin_password_confirmation' => 'Password1234',
        ]);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post(route('login.store'), [
            'username' => 'admin',
            'password' => 'Password1234',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'auth',
            'action' => 'login',
        ]);
    }

    public function test_company_owner_can_use_initial_admin_cruds(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->createValidLicenseState();

        $this->post(route('setup.initial.store'), [
            'company_name' => 'Demo Market',
            'legal_name' => 'Comercial Demo SRL',
            'rnc' => '101010101',
            'phone' => '809-555-0000',
            'company_email' => 'admin@example.com',
            'address' => 'Av. Principal #1',
            'branch_code' => 'principal',
            'branch_name' => 'Sucursal Principal',
            'admin_name' => 'Administrador',
            'admin_username' => 'admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'Password1234',
            'admin_password_confirmation' => 'Password1234',
        ]);

        $this->get(route('admin.companies.index'))->assertOk();
        $this->get(route('admin.branches.index'))->assertOk();
        $this->get(route('admin.users.index'))->assertOk();
        $this->get(route('admin.roles.index'))->assertOk();

        $this->post(route('admin.branches.store'), [
            'code' => 'sucursal-2',
            'name' => 'Sucursal 2',
            'phone' => '809-555-0001',
            'address' => 'Calle 2',
            'manager_name' => 'Encargado',
            'status' => 'ACTIVE',
            'can_sell_online' => '1',
            'offline_max_minutes' => 120,
            'offline_total_limit' => '1000.00',
            'cash_control_enabled' => '1',
            'accounting_enabled' => '1',
            'payroll_enabled' => '1',
        ])->assertRedirect(route('admin.branches.index'));

        $cashierRole = Role::query()->where('slug', 'CASHIER')->firstOrFail();

        $this->post(route('admin.users.store'), [
            'branch_id' => 1,
            'name' => 'Cajero Uno',
            'username' => 'cajero1',
            'email' => 'cajero1@example.com',
            'password' => 'Password1234',
            'password_confirmation' => 'Password1234',
            'role_id' => $cashierRole->id,
            'status' => 'ACTIVE',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('branches', ['code' => 'sucursal-2']);
        $this->assertDatabaseHas('users', ['username' => 'cajero1']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'branches', 'action' => 'create']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'users', 'action' => 'create']);
    }

    public function test_company_owner_can_view_audit_and_manage_devices(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->createValidLicenseState();

        $this->post(route('setup.initial.store'), [
            'company_name' => 'Demo Market',
            'legal_name' => 'Comercial Demo SRL',
            'rnc' => '101010101',
            'phone' => '809-555-0000',
            'company_email' => 'admin@example.com',
            'address' => 'Av. Principal #1',
            'branch_code' => 'principal',
            'branch_name' => 'Sucursal Principal',
            'admin_name' => 'Administrador',
            'admin_username' => 'admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'Password1234',
            'admin_password_confirmation' => 'Password1234',
        ]);

        $device = Device::query()->create([
            'company_id' => 1,
            'branch_id' => 1,
            'user_id' => auth()->id(),
            'name' => 'Android Caja 1',
            'device_type' => 'ANDROID',
            'platform' => 'Android',
            'device_fingerprint' => 'android-test-1',
            'app_version' => '1.0.0',
            'status' => 'PENDING',
        ]);

        $this->get(route('admin.audit.index'))->assertOk();
        $this->get(route('admin.audit.show', AuditLog::query()->firstOrFail()))->assertOk();
        $this->get(route('admin.devices.index'))->assertOk();

        $this->post(route('admin.devices.authorize', $device))->assertRedirect();
        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => 'AUTHORIZED',
        ]);

        $this->post(route('admin.devices.block', $device->fresh()))->assertRedirect();
        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => 'BLOCKED',
        ]);

        $this->assertDatabaseHas('audit_logs', ['module' => 'devices', 'action' => 'authorize']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'devices', 'action' => 'block']);
    }

    public function test_branch_scoped_user_only_sees_own_branch_data(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->createValidLicenseState();

        $this->post(route('setup.initial.store'), [
            'company_name' => 'Demo Market',
            'legal_name' => 'Comercial Demo SRL',
            'rnc' => '101010101',
            'phone' => '809-555-0000',
            'company_email' => 'admin@example.com',
            'address' => 'Av. Principal #1',
            'branch_code' => 'principal',
            'branch_name' => 'Sucursal Principal',
            'admin_name' => 'Administrador',
            'admin_username' => 'admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'Password1234',
            'admin_password_confirmation' => 'Password1234',
        ]);

        $mainBranch = Branch::query()->where('code', 'principal')->firstOrFail();
        $otherBranch = Branch::query()->create([
            'company_id' => $mainBranch->company_id,
            'code' => 'sucursal-2',
            'name' => 'Sucursal 2',
            'status' => 'ACTIVE',
            'can_sell_online' => true,
            'can_sell_offline' => false,
            'offline_max_minutes' => 120,
            'offline_total_limit' => '0.00',
            'cash_control_enabled' => true,
            'accounting_enabled' => true,
            'payroll_enabled' => true,
        ]);

        $supervisor = User::query()->create([
            'company_id' => $mainBranch->company_id,
            'branch_id' => $mainBranch->id,
            'role_id' => Role::query()->where('slug', 'SUPERVISOR')->firstOrFail()->id,
            'name' => 'Supervisor Principal',
            'username' => 'supervisor1',
            'email' => 'supervisor1@example.com',
            'password' => Hash::make('Password1234'),
            'status' => 'ACTIVE',
        ]);

        User::query()->create([
            'company_id' => $mainBranch->company_id,
            'branch_id' => $otherBranch->id,
            'role_id' => Role::query()->where('slug', 'CASHIER')->firstOrFail()->id,
            'name' => 'Cajero Sucursal 2',
            'username' => 'cajero2',
            'email' => 'cajero2@example.com',
            'password' => Hash::make('Password1234'),
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($supervisor);

        $this->get(route('admin.branches.index'))
            ->assertOk()
            ->assertSee('Sucursal Principal')
            ->assertDontSee('Sucursal 2');

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('supervisor1')
            ->assertDontSee('cajero2');
    }

    public function test_sales_screen_can_lookup_ticket_for_copy_or_payment_flow(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->createValidLicenseState();

        $this->post(route('setup.initial.store'), [
            'company_name' => 'Demo Market',
            'legal_name' => 'Comercial Demo SRL',
            'rnc' => '101010101',
            'phone' => '809-555-0000',
            'company_email' => 'admin@example.com',
            'address' => 'Av. Principal #1',
            'branch_code' => 'principal',
            'branch_name' => 'Sucursal Principal',
            'admin_name' => 'Administrador',
            'admin_username' => 'admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'Password1234',
            'admin_password_confirmation' => 'Password1234',
        ]);

        $branch = Branch::query()->firstOrFail();
        $lottery = Lottery::query()->create([
            'company_id' => $branch->company_id,
            'code' => 'NAC',
            'name' => 'Nacional',
            'country' => 'DO',
            'timezone' => 'America/Santo_Domingo',
            'status' => 'ACTIVE',
        ]);
        $draw = Draw::query()->create([
            'company_id' => $branch->company_id,
            'lottery_id' => $lottery->id,
            'name' => 'Mediodía',
            'draw_date' => now()->toDateString(),
            'scheduled_time' => now()->addHour()->format('H:i'),
            'close_time' => now()->addHour()->format('H:i'),
            'status' => 'OPEN',
        ]);
        $betType = BetType::query()->create([
            'company_id' => $branch->company_id,
            'code' => 'QUINIELA',
            'name' => 'Quiniela',
            'digits_count' => 2,
            'numbers_count' => 1,
            'requires_position' => false,
            'is_cross_lottery' => false,
            'status' => 'ACTIVE',
        ]);
        $ticket = Ticket::query()->create([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'user_id' => auth()->id(),
            'ticket_number' => 'principal-260517-0001',
            'sale_mode' => 'ONLINE',
            'total_amount' => '100.00',
            'total_possible_prize' => '7200.00',
            'status' => 'ACTIVE',
            'sold_at' => now(),
        ]);
        TicketDetail::query()->create([
            'ticket_id' => $ticket->id,
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'lottery_id' => $lottery->id,
            'draw_id' => $draw->id,
            'bet_type_id' => $betType->id,
            'number_value' => '20',
            'normalized_number' => '20',
            'amount' => '100.00',
            'payout_multiplier' => '72.00',
            'possible_prize' => '7200.00',
            'status' => 'ACTIVE',
        ]);

        $this->get(route('admin.tickets.create'))
            ->assertOk()
            ->assertSee('Buscar, copiar o pagar');

        $this->getJson(route('admin.tickets.lookup', ['token' => $ticket->ticket_number]))
            ->assertOk()
            ->assertJsonPath('ticket.ticket_number', 'principal-260517-0001')
            ->assertJsonPath('copy_plays.0.number_value', '20');

        $this->getJson(route('admin.tickets.lookup', ['token' => 'ticket:' . $ticket->uuid]))
            ->assertOk()
            ->assertJsonPath('ticket.ticket_number', 'principal-260517-0001');
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
            'offline_grace_expires_at' => now()->addHours(72),
            'features' => ['offline_mode' => true],
            'limits' => ['offline_grace_hours' => 72, 'max_users' => 5],
            'metadata' => [
                'company_name' => 'Comercial Demo SRL',
                'trade_name' => 'Demo Market',
                'branch_name' => 'Sucursal Principal',
                'rnc' => '101010101',
                'phone' => '809-555-0000',
                'address' => 'Av. Principal #1',
                'company_id' => 'demo-market',
                'branch_id' => 'principal',
            ],
            'client' => ['code' => 'cliente-demo', 'name' => 'Cliente Demo'],
            'location' => ['code' => 'principal', 'name' => 'Sucursal Principal'],
            'is_active' => true,
        ]);
    }
}
