<?php

namespace Tests\Feature;

use App\Models\BetType;
use App\Models\Branch;
use App\Models\BranchMonitoringSetting;
use App\Models\CashSession;
use App\Models\Draw;
use App\Models\LicenseState;
use App\Models\Lottery;
use App\Models\PrizePayment;
use App\Models\SystemNotification;
use App\Models\Ticket;
use App\Models\TicketDetail;
use App\Models\User;
use App\Models\WinnerTicket;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BranchMonitoringTest extends TestCase
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
            'company_name' => 'Banca Monitor',
            'legal_name' => 'Banca Monitor SRL',
            'rnc' => '123456789',
            'phone' => '809-555-0000',
            'company_email' => 'monitor@example.com',
            'address' => 'Calle Monitor #1',
            'branch_code' => 'MON01',
            'branch_name' => 'Sucursal Monitor',
            'admin_name' => 'Admin Monitor',
            'admin_username' => 'admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'Password1234',
            'admin_password_confirmation' => 'Password1234',
        ]);

        $this->admin = auth()->user();
        $this->branch = Branch::firstOrFail();
    }

    public function test_monitoring_flags_branch_loss_and_creates_notification(): void
    {
        $ticket = Ticket::create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'cash_session_id' => null,
            'ticket_number' => 'MON-001',
            'sale_mode' => 'ONLINE',
            'total_amount' => '100.00',
            'total_possible_prize' => '8000.00',
            'status' => 'PAID',
            'sold_at' => now(),
        ]);

        $lottery = Lottery::create([
            'company_id' => $this->branch->company_id,
            'name' => 'Loteria Monitor',
            'code' => 'MON',
            'country' => 'DO',
            'status' => 'ACTIVE',
        ]);

        $draw = Draw::create([
            'company_id' => $this->branch->company_id,
            'lottery_id' => $lottery->id,
            'name' => 'Sorteo Monitor',
            'draw_date' => now()->toDateString(),
            'scheduled_time' => '12:00',
            'close_time' => '23:59',
            'status' => 'PAYMENTS_RELEASED',
        ]);

        $betType = BetType::create([
            'company_id' => $this->branch->company_id,
            'code' => 'QUINIELA',
            'name' => 'Quiniela',
            'digits_count' => 2,
            'min_numbers' => 1,
            'max_numbers' => 1,
            'requires_position' => false,
            'status' => 'ACTIVE',
        ]);

        $detail = TicketDetail::create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'ticket_id' => $ticket->id,
            'lottery_id' => $lottery->id,
            'draw_id' => $draw->id,
            'bet_type_id' => $betType->id,
            'number_value' => '34',
            'normalized_number' => '34',
            'amount' => '100.00',
            'payout_multiplier' => '80.00',
            'possible_prize' => '8000.00',
            'status' => 'PAID',
        ]);

        $winner = WinnerTicket::create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'ticket_id' => $ticket->id,
            'ticket_detail_id' => $detail->id,
            'lottery_id' => $lottery->id,
            'draw_id' => $draw->id,
            'bet_type_id' => $betType->id,
            'number_value' => '34',
            'matched_position' => 'FIRST',
            'amount_played' => '100.00',
            'payout_multiplier' => '80.00',
            'prize_amount' => '8000.00',
            'status' => 'PAID',
            'paid_at' => now(),
            'paid_by' => $this->admin->id,
        ]);

        PrizePayment::create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'ticket_id' => $ticket->id,
            'winner_ticket_id' => $winner->id,
            'cash_session_id' => null,
            'amount' => '8000.00',
            'paid_by' => $this->admin->id,
            'paid_at' => now(),
            'status' => 'PAID',
        ]);

        CashSession::create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'opened_by' => $this->admin->id,
            'opening_amount' => '0.00',
            'expected_cash' => '-7900.00',
            'status' => 'OPEN',
            'opened_at' => now(),
        ]);

        $this->get(route('admin.monitoring.index'))
            ->assertOk()
            ->assertSee('Sucursal Monitor')
            ->assertSee('Requiere efectivo')
            ->assertSee('34');

        $this->assertDatabaseHas('system_notifications', [
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'type' => 'BRANCH_LOSS',
            'severity' => 'CRITICAL',
            'status' => 'UNREAD',
        ]);

        $notification = SystemNotification::firstOrFail();

        $this->post(route('admin.notifications.read', $notification))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $notification->refresh();
        $this->assertEquals('READ', $notification->status);
        $this->assertEquals($this->admin->id, $notification->read_by);
    }

    public function test_monitoring_command_creates_low_cash_notification_for_configured_branch_only(): void
    {
        $secondBranch = Branch::create([
            'company_id' => $this->branch->company_id,
            'code' => 'MON02',
            'name' => 'Sucursal Sin Alerta',
            'status' => 'ACTIVE',
        ]);

        BranchMonitoringSetting::create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'alert_enabled' => true,
            'loss_threshold' => '0.00',
            'minimum_expected_cash' => '1000.00',
            'top_play_alert_amount' => null,
        ]);

        CashSession::create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'opened_by' => $this->admin->id,
            'opening_amount' => '0.00',
            'expected_cash' => '100.00',
            'status' => 'OPEN',
            'opened_at' => now(),
        ]);

        CashSession::create([
            'company_id' => $secondBranch->company_id,
            'branch_id' => $secondBranch->id,
            'user_id' => $this->admin->id,
            'opened_by' => $this->admin->id,
            'opening_amount' => '0.00',
            'expected_cash' => '100.00',
            'status' => 'OPEN',
            'opened_at' => now(),
        ]);

        $this->artisan('monitoring:scan-branches', [
            '--company_id' => $this->branch->company_id,
            '--date' => now()->toDateString(),
        ])->assertExitCode(0);

        $this->assertDatabaseHas('system_notifications', [
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'type' => 'BRANCH_LOW_CASH',
            'severity' => 'WARNING',
            'status' => 'UNREAD',
        ]);

        $this->assertDatabaseMissing('system_notifications', [
            'company_id' => $secondBranch->company_id,
            'branch_id' => $secondBranch->id,
            'type' => 'BRANCH_LOW_CASH',
        ]);
    }

    public function test_admin_can_update_monitoring_thresholds(): void
    {
        $this->put(route('admin.monitoring.settings.update'), [
            'settings' => [
                [
                    'branch_id' => null,
                    'alert_enabled' => '1',
                    'loss_threshold' => '500.00',
                    'minimum_expected_cash' => '2500.00',
                    'top_play_alert_amount' => '3000.00',
                ],
                [
                    'branch_id' => $this->branch->id,
                    'alert_enabled' => '1',
                    'loss_threshold' => '100.00',
                    'minimum_expected_cash' => '1500.00',
                    'top_play_alert_amount' => '2000.00',
                ],
            ],
        ])->assertRedirect(route('admin.monitoring.settings'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('branch_monitoring_settings', [
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'loss_threshold' => '100.00',
            'minimum_expected_cash' => '1500.00',
            'top_play_alert_amount' => '2000.00',
        ]);
    }

    public function test_admin_can_fund_open_cash_session_and_expected_cash_increases(): void
    {
        $session = CashSession::create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'opened_by' => $this->admin->id,
            'opening_amount' => '100.00',
            'expected_cash' => '100.00',
            'status' => 'OPEN',
            'opened_at' => now(),
        ]);

        $this->post(route('admin.cash.funding.store'), [
            'cash_session_id' => $session->id,
            'amount' => '2500.00',
            'source' => 'Administracion',
            'reference' => 'REF-001',
            'notes' => 'Refuerzo para pago de premio.',
        ])->assertRedirect(route('admin.cash.funding.index'))
            ->assertSessionHasNoErrors();

        $session->refresh();

        $this->assertEquals('2600.00', $session->expected_cash);
        $this->assertEquals('2500.00', $session->cash_in_total);
        $this->assertDatabaseHas('cash_funding_transfers', [
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'cash_session_id' => $session->id,
            'amount' => '2500.00',
            'reference' => 'REF-001',
            'status' => 'COMPLETED',
        ]);
        $this->assertDatabaseHas('cash_movements', [
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'cash_session_id' => $session->id,
            'type' => 'CASH_IN',
            'amount' => '2500.00',
            'direction' => 'IN',
            'payment_method' => 'CASH',
        ]);
    }

    private function createValidLicenseState(): LicenseState
    {
        return LicenseState::create([
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
                'company_name' => 'Banca Monitor SRL',
                'trade_name' => 'Banca Monitor',
                'branch_name' => 'Sucursal Monitor',
                'rnc' => '123456789',
                'phone' => '809-555-0000',
                'address' => 'Calle Monitor #1',
                'company_id' => 'banca-monitor',
                'branch_id' => 'MON01',
            ],
            'client' => ['code' => 'cliente-demo', 'name' => 'Cliente Demo'],
            'location' => ['code' => 'MON01', 'name' => 'Sucursal Monitor'],
            'is_active' => true,
        ]);
    }
}
