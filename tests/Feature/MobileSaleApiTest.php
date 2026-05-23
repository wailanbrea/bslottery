<?php

namespace Tests\Feature;

use App\Models\BetType;
use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Device;
use App\Models\Draw;
use App\Models\LicenseState;
use App\Models\LimitConsumption;
use App\Models\LimitRule;
use App\Models\Lottery;
use App\Models\PayoutRule;
use App\Models\PrintJob;
use App\Models\PrizePayment;
use App\Models\Ticket;
use App\Models\TicketDetail;
use App\Models\User;
use App\Models\WinnerTicket;
use App\Services\Cash\CashService;
use Database\Seeders\AccountingAccountSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileSaleApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Branch $branch;

    private Draw $draw;

    private BetType $betType;

    private BetType $superPale;

    private string $deviceUuid = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->createValidLicenseState();

        $this->post(route('setup.initial.store'), [
            'company_name' => 'Banca Mobile',
            'legal_name' => 'Banca Mobile SRL',
            'rnc' => '123456789',
            'phone' => '809-555-0000',
            'company_email' => 'mobile@example.com',
            'address' => 'Calle Test #1',
            'branch_code' => 'MOB01',
            'branch_name' => 'Sucursal Mobile',
            'admin_name' => 'Admin Mobile',
            'admin_username' => 'admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'Password1234',
            'admin_password_confirmation' => 'Password1234',
        ]);

        $this->seed(AccountingAccountSeeder::class);

        $this->admin = auth()->user();
        $this->branch = Branch::query()->firstOrFail();
        $this->branch->forceFill([
            'cash_control_enabled' => true,
            'can_sell_online' => true,
        ])->save();

        $lottery = Lottery::query()->create([
            'company_id' => $this->branch->company_id,
            'name' => 'Loteria Mobile',
            'code' => 'MOBILE',
            'country' => 'DO',
            'status' => 'ACTIVE',
        ]);

        $this->draw = Draw::query()->create([
            'company_id' => $this->branch->company_id,
            'lottery_id' => $lottery->id,
            'name' => 'Noche',
            'draw_date' => now()->toDateString(),
            'scheduled_time' => '20:00',
            'close_time' => '23:59',
            'status' => 'OPEN',
        ]);

        $this->betType = BetType::query()->create([
            'company_id' => $this->branch->company_id,
            'code' => 'QUINIELA',
            'name' => 'Quiniela',
            'digits_count' => 2,
            'min_numbers' => 1,
            'max_numbers' => 1,
            'requires_position' => false,
            'status' => 'ACTIVE',
        ]);

        $this->superPale = BetType::query()->create([
            'company_id' => $this->branch->company_id,
            'code' => 'SUPER_PALE',
            'name' => 'Super Pale',
            'digits_count' => 2,
            'min_numbers' => 1,
            'max_numbers' => 1,
            'numbers_count' => 2,
            'is_cross_lottery' => true,
            'requires_position' => false,
            'status' => 'ACTIVE',
        ]);

        PayoutRule::query()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'bet_type_id' => $this->betType->id,
            'match_type' => 'DIRECT',
            'payout_multiplier' => '72.00',
            'effective_from' => now()->subDay(),
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);

        PayoutRule::query()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'bet_type_id' => $this->superPale->id,
            'match_type' => 'DIRECT',
            'position' => 'FIRST',
            'payout_multiplier' => '1500.00',
            'effective_from' => now()->subDay(),
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_mobile_device_must_be_authorized_before_operating(): void
    {
        $token = $this->mobileLogin();

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->getJson('/api/mobile/sync/data')
            ->assertForbidden()
            ->assertJsonPath('code', 'DEVICE_NOT_AUTHORIZED');
    }

    public function test_authorized_mobile_can_sell_and_creates_cash_and_print_artifacts(): void
    {
        $token = $this->mobileLogin();
        Device::query()->where('uuid', $this->deviceUuid)->firstOrFail()->forceFill([
            'status' => 'AUTHORIZED',
            'authorized_by' => $this->admin->id,
            'authorized_at' => now(),
        ])->save();

        CashSession::query()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'opened_by' => $this->admin->id,
            'opening_amount' => '5000.00',
            'expected_cash' => '5000.00',
            'status' => 'OPEN',
            'opened_at' => now(),
        ]);

        $ticketUuid = '22222222-2222-4222-8222-222222222222';

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->postJson('/api/mobile/tickets', [
            'uuid' => $ticketUuid,
            'draw_id' => $this->draw->id,
            'details' => [
                [
                    'number_value' => '25',
                    'bet_type_id' => $this->betType->id,
                    'amount' => '100.00',
                ],
            ],
            'sold_at' => now()->toISOString(),
            'offline' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('uuid', $ticketUuid)
            ->assertJsonPath('details.0.potential_prize', '7200.00');

        $ticket = Ticket::query()->where('uuid', $ticketUuid)->firstOrFail();

        $this->assertSame('MOBILE', $ticket->sale_mode);
        $this->assertNotNull($ticket->cash_session_id);
        $this->assertTrue(CashMovement::query()->where('reference_type', 'Ticket')->where('reference_id', $ticket->id)->exists());
        $this->assertTrue(PrintJob::query()->where('ticket_id', $ticket->id)->where('type', 'TICKET')->exists());
    }

    public function test_mobile_sync_returns_open_draws_and_super_pale_catalog(): void
    {
        $token = $this->mobileLogin();
        Device::query()->where('uuid', $this->deviceUuid)->firstOrFail()->forceFill([
            'status' => 'AUTHORIZED',
            'authorized_by' => $this->admin->id,
            'authorized_at' => now(),
        ])->save();

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->getJson('/api/mobile/sync/data?since='.now()->addDay()->toISOString())
            ->assertOk()
            ->assertJsonPath('draws.0.id', $this->draw->id)
            ->assertJsonFragment(['code' => 'SUPER_PALE']);
    }

    public function test_mobile_sync_generates_missing_today_draws_for_catalog_lotteries(): void
    {
        $token = $this->mobileLogin();
        Device::query()->where('uuid', $this->deviceUuid)->firstOrFail()->forceFill([
            'status' => 'AUTHORIZED',
            'authorized_by' => $this->admin->id,
            'authorized_at' => now(),
        ])->save();

        Draw::query()->delete();
        Lottery::query()->create([
            'company_id' => $this->branch->company_id,
            'name' => 'Loteria Nacional',
            'code' => 'LOTNAC',
            'country' => 'DO',
            'status' => 'ACTIVE',
        ]);

        $this->assertSame(0, Draw::query()->whereDate('draw_date', now()->toDateString())->count());

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->getJson('/api/mobile/sync/data')
            ->assertOk()
            ->assertJsonFragment(['lottery_name' => 'Loteria Nacional']);

        $this->assertTrue(
            Draw::query()
                ->where('company_id', $this->branch->company_id)
                ->whereDate('draw_date', now()->toDateString())
                ->where('scheduled_time', '21:00')
                ->where('status', 'OPEN')
                ->exists()
        );
    }

    public function test_mobile_can_lookup_ticket_by_number_or_qr_token(): void
    {
        $token = $this->mobileLogin();
        Device::query()->where('uuid', $this->deviceUuid)->firstOrFail()->forceFill([
            'status' => 'AUTHORIZED',
            'authorized_by' => $this->admin->id,
            'authorized_at' => now(),
        ])->save();

        CashSession::query()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'opened_by' => $this->admin->id,
            'opening_amount' => '5000.00',
            'expected_cash' => '5000.00',
            'status' => 'OPEN',
            'opened_at' => now(),
        ]);

        $ticketUuid = '33333333-3333-4333-8333-333333333333';

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->postJson('/api/mobile/tickets', [
            'uuid' => $ticketUuid,
            'draw_id' => $this->draw->id,
            'details' => [
                [
                    'number_value' => '64',
                    'bet_type_id' => $this->betType->id,
                    'amount' => '50.00',
                ],
            ],
            'sold_at' => now()->toISOString(),
            'offline' => false,
        ])->assertCreated();

        $ticket = Ticket::query()->where('uuid', $ticketUuid)->firstOrFail();

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->getJson('/api/mobile/tickets/lookup?token='.$ticket->ticket_number)
            ->assertOk()
            ->assertJsonPath('uuid', $ticketUuid);

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->getJson('/api/mobile/tickets/lookup?token=ticket:'.$ticketUuid)
            ->assertOk()
            ->assertJsonPath('ticket_number', $ticket->ticket_number);
    }

    public function test_mobile_cash_status_returns_no_session_when_branch_uses_cash_control(): void
    {
        $token = $this->mobileLogin();
        Device::query()->where('uuid', $this->deviceUuid)->firstOrFail()->forceFill([
            'status' => 'AUTHORIZED',
            'authorized_by' => $this->admin->id,
            'authorized_at' => now(),
        ])->save();

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->getJson('/api/mobile/cash/status')
            ->assertOk()
            ->assertJsonPath('cash_control_enabled', true)
            ->assertJsonPath('session', null)
            ->assertJsonPath('branch.id', $this->branch->id);
    }

    public function test_mobile_can_open_and_close_cash_session(): void
    {
        $token = $this->mobileLogin();
        Device::query()->where('uuid', $this->deviceUuid)->firstOrFail()->forceFill([
            'status' => 'AUTHORIZED',
            'authorized_by' => $this->admin->id,
            'authorized_at' => now(),
        ])->save();

        $open = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->postJson('/api/mobile/cash/open', [
            'opening_amount' => '1500.00',
            'notes' => 'Apertura desde Android',
        ])->assertCreated();

        $sessionId = $open->json('session.id');
        $this->assertNotNull($sessionId);
        $this->assertSame('OPEN', $open->json('session.status'));
        $this->assertSame('1500.00', $open->json('session.opening_amount'));

        // Estado refleja la caja abierta
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->getJson('/api/mobile/cash/status')
            ->assertOk()
            ->assertJsonPath('session.id', $sessionId)
            ->assertJsonPath('session.status', 'OPEN');

        // No se puede abrir dos veces
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->postJson('/api/mobile/cash/open', [
            'opening_amount' => '500.00',
        ])->assertStatus(422);

        // Cerrar con efectivo contado igual al esperado
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->postJson('/api/mobile/cash/close', [
            'counted_cash' => '1500.00',
            'notes' => 'Cierre desde Android',
        ])->assertOk()
            ->assertJsonPath('session.status', 'CLOSED')
            ->assertJsonPath('session.shortage_amount', '0.00')
            ->assertJsonPath('session.surplus_amount', '0.00');

        // Tras cierre, getActiveSession debe ser null
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->getJson('/api/mobile/cash/status')
            ->assertOk()
            ->assertJsonPath('session', null);
    }

    public function test_mobile_cash_status_returns_session_and_recent_movements_opened_from_web(): void
    {
        // Caso real: el cajero abrio caja desde el panel web del sistema y
        // luego se pasa al telefono. La app debe ver la caja y los movimientos.
        $token = $this->mobileLogin();
        Device::query()->where('uuid', $this->deviceUuid)->firstOrFail()->forceFill([
            'status' => 'AUTHORIZED',
            'authorized_by' => $this->admin->id,
            'authorized_at' => now(),
        ])->save();

        // Caja abierta desde el "sistema" (web): creamos la fila directamente
        $session = CashSession::query()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'opened_by' => $this->admin->id,
            'opening_amount' => '5000.00',
            'expected_cash' => '5000.00',
            'sales_total' => '0.00',
            'status' => 'OPEN',
            'opened_at' => now()->subHours(2),
        ]);

        // Movimientos realizados desde el sistema (venta + entrada)
        $cashService = app(CashService::class);
        $cashService->recordMovement($session, $this->admin, 'SALE', '1200.00', 'IN', 'Venta desde sistema');
        $cashService->recordMovement($session, $this->admin, 'CASH_IN', '500.00', 'IN', 'Refuerzo desde sistema');

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->getJson('/api/mobile/cash/status')->assertOk();

        $response->assertJsonPath('cash_control_enabled', true)
            ->assertJsonPath('session.id', $session->id)
            ->assertJsonPath('session.status', 'OPEN')
            ->assertJsonPath('session.opening_amount', '5000.00')
            ->assertJsonPath('session.sales_total', '1200.00')
            ->assertJsonPath('session.cash_in_total', '500.00')
            ->assertJsonPath('session.expected_cash', '6700.00'); // 5000 + 1200 + 500

        // Movimientos retornados (orden desc por created_at)
        $movements = $response->json('movements');
        $this->assertNotEmpty($movements);
        $this->assertGreaterThanOrEqual(2, count($movements));

        $types = collect($movements)->pluck('type')->all();
        $this->assertContains('SALE', $types);
        $this->assertContains('CASH_IN', $types);

        // Server time presente para indicar "ultima actualizacion"
        $this->assertNotEmpty($response->json('server_time'));
    }

    public function test_mobile_cash_close_with_denominations_computes_counted_cash(): void
    {
        $token = $this->mobileLogin();
        Device::query()->where('uuid', $this->deviceUuid)->firstOrFail()->forceFill([
            'status' => 'AUTHORIZED',
            'authorized_by' => $this->admin->id,
            'authorized_at' => now(),
        ])->save();

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->postJson('/api/mobile/cash/open', [
            'opening_amount' => '2000.00',
        ])->assertCreated();

        // 2 billetes de 1000 + 5 billetes de 100 = 2500.00 (sobrante 500 vs esperado 2000)
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->postJson('/api/mobile/cash/close', [
            'denominations' => [
                'bill_1000' => 2,
                'bill_100' => 5,
            ],
            'notes' => 'Cierre con conteo por denominaciones',
        ])->assertOk()
            ->assertJsonPath('session.status', 'CLOSED')
            ->assertJsonPath('session.counted_cash', '2500.00')
            ->assertJsonPath('session.surplus_amount', '500.00')
            ->assertJsonPath('session.shortage_amount', '0.00');
    }

    public function test_mobile_check_limit_returns_correct_available_when_near_cap(): void
    {
        $token = $this->mobileLogin();
        Device::query()->where('uuid', $this->deviceUuid)->firstOrFail()->forceFill([
            'status' => 'AUTHORIZED',
            'authorized_by' => $this->admin->id,
            'authorized_at' => now(),
        ])->save();

        LimitRule::query()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'lottery_id' => $this->draw->lottery_id,
            'draw_id' => $this->draw->id,
            'bet_type_id' => $this->betType->id,
            'rule_type' => 'SINGLE_NUMBER',
            'number_value' => '34',
            'max_amount_per_number' => '3000.00',
            'policy' => 'BLOCK_FULL',
            'effective_from' => now()->subDay(),
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);

        LimitConsumption::query()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'lottery_id' => $this->draw->lottery_id,
            'draw_id' => $this->draw->id,
            'bet_type_id' => $this->betType->id,
            'number_value' => '34',
            'sold_amount' => '2800.00',
        ]);

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->getJson('/api/mobile/tickets/check-limit?'.http_build_query([
            'draw_id' => $this->draw->id,
            'bet_type_id' => $this->betType->id,
            'number_value' => '34',
        ]))->assertOk()->assertJson([
            'available' => 200.0,
            'blocked' => false,
        ]);
    }

    public function test_mobile_check_limit_returns_blocked_when_fully_consumed(): void
    {
        $token = $this->mobileLogin();
        Device::query()->where('uuid', $this->deviceUuid)->firstOrFail()->forceFill([
            'status' => 'AUTHORIZED',
            'authorized_by' => $this->admin->id,
            'authorized_at' => now(),
        ])->save();

        LimitRule::query()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'lottery_id' => $this->draw->lottery_id,
            'draw_id' => $this->draw->id,
            'bet_type_id' => $this->betType->id,
            'rule_type' => 'SINGLE_NUMBER',
            'number_value' => '34',
            'max_amount_per_number' => '500.00',
            'policy' => 'BLOCK_FULL',
            'effective_from' => now()->subDay(),
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);

        LimitConsumption::query()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'lottery_id' => $this->draw->lottery_id,
            'draw_id' => $this->draw->id,
            'bet_type_id' => $this->betType->id,
            'number_value' => '34',
            'sold_amount' => '500.00',
        ]);

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->getJson('/api/mobile/tickets/check-limit?'.http_build_query([
            'draw_id' => $this->draw->id,
            'bet_type_id' => $this->betType->id,
            'number_value' => '34',
        ]))->assertOk()->assertJson([
            'available' => 0,
            'blocked' => true,
        ]);
    }

    public function test_mobile_check_limit_returns_null_available_without_rules(): void
    {
        $token = $this->mobileLogin();
        Device::query()->where('uuid', $this->deviceUuid)->firstOrFail()->forceFill([
            'status' => 'AUTHORIZED',
            'authorized_by' => $this->admin->id,
            'authorized_at' => now(),
        ])->save();

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->getJson('/api/mobile/tickets/check-limit?'.http_build_query([
            'draw_id' => $this->draw->id,
            'bet_type_id' => $this->betType->id,
            'number_value' => '88',
        ]))->assertOk()->assertJson([
            'available' => null,
            'blocked' => false,
        ]);
    }

    public function test_mobile_cash_close_requires_open_session(): void
    {
        $token = $this->mobileLogin();
        Device::query()->where('uuid', $this->deviceUuid)->firstOrFail()->forceFill([
            'status' => 'AUTHORIZED',
            'authorized_by' => $this->admin->id,
            'authorized_at' => now(),
        ])->save();

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->postJson('/api/mobile/cash/close', [
            'counted_cash' => '0.00',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'No hay caja abierta para este usuario.');
    }

    public function test_mobile_cash_status_when_branch_does_not_use_cash_control(): void
    {
        $token = $this->mobileLogin();
        Device::query()->where('uuid', $this->deviceUuid)->firstOrFail()->forceFill([
            'status' => 'AUTHORIZED',
            'authorized_by' => $this->admin->id,
            'authorized_at' => now(),
        ])->save();

        $this->branch->forceFill(['cash_control_enabled' => false])->save();

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->getJson('/api/mobile/cash/status')
            ->assertOk()
            ->assertJsonPath('cash_control_enabled', false)
            ->assertJsonPath('session', null);
    }

    public function test_mobile_can_pay_released_prize_for_ticket(): void
    {
        $token = $this->mobileLogin();
        Device::query()->where('uuid', $this->deviceUuid)->firstOrFail()->forceFill([
            'status' => 'AUTHORIZED',
            'authorized_by' => $this->admin->id,
            'authorized_at' => now(),
        ])->save();

        // Abrir caja con fondo suficiente para pagar el premio
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->postJson('/api/mobile/cash/open', [
            'opening_amount' => '10000.00',
        ])->assertCreated();

        // Crear ticket directamente (mas rapido que recrear el flujo de venta)
        $ticketUuid = '44444444-4444-4444-8444-444444444444';
        $ticket = Ticket::query()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'uuid' => $ticketUuid,
            'ticket_number' => 'MOB01-260520-9999',
            'total_amount' => '100.00',
            'status' => 'ACTIVE',
            'sold_at' => now(),
            'sale_mode' => 'MOBILE',
        ]);

        $detail = TicketDetail::query()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'ticket_id' => $ticket->id,
            'lottery_id' => $this->draw->lottery_id,
            'draw_id' => $this->draw->id,
            'bet_type_id' => $this->betType->id,
            'number_value' => '25',
            'normalized_number' => '25',
            'amount' => '100.00',
            'payout_multiplier' => '72.00',
            'possible_prize' => '7200.00',
            'status' => 'ACTIVE',
        ]);

        WinnerTicket::query()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'ticket_id' => $ticket->id,
            'ticket_detail_id' => $detail->id,
            'lottery_id' => $this->draw->lottery_id,
            'draw_id' => $this->draw->id,
            'bet_type_id' => $this->betType->id,
            'number_value' => '25',
            'matched_position' => 'FIRST',
            'amount_played' => '100.00',
            'payout_multiplier' => '72.00',
            'prize_amount' => '7200.00',
            'status' => 'RELEASED',
        ]);

        // Consultar winners
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->getJson("/api/mobile/tickets/{$ticketUuid}/winners")
            ->assertOk()
            ->assertJsonPath('has_releasable_prizes', true)
            ->assertJsonPath('total_released', '7200.00')
            ->assertJsonPath('winners.0.status', 'RELEASED')
            ->assertJsonPath('winners.0.prize_amount', '7200.00');

        // Pagar premio
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->postJson("/api/mobile/tickets/{$ticketUuid}/pay-prize")
            ->assertCreated()
            ->assertJsonPath('payments_count', 1)
            ->assertJsonPath('total_paid', '7200.00');

        // Verificar persistencia: PrizePayment, CashMovement PRIZE_PAYMENT y winner PAID
        $this->assertTrue(PrizePayment::query()->where('ticket_id', $ticket->id)->where('amount', '7200.00')->exists());
        $this->assertTrue(CashMovement::query()->where('type', 'PRIZE_PAYMENT')->where('amount', '7200.00')->exists());
        $this->assertSame('PAID', WinnerTicket::query()->where('ticket_id', $ticket->id)->value('status'));

        // Segunda llamada debe fallar (no quedan premios liberados)
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->postJson("/api/mobile/tickets/{$ticketUuid}/pay-prize")
            ->assertStatus(422);
    }

    public function test_mobile_pay_prize_requires_open_cash_session(): void
    {
        $token = $this->mobileLogin();
        Device::query()->where('uuid', $this->deviceUuid)->firstOrFail()->forceFill([
            'status' => 'AUTHORIZED',
            'authorized_by' => $this->admin->id,
            'authorized_at' => now(),
        ])->save();

        $ticketUuid = '55555555-5555-5555-8555-555555555555';
        $ticket = Ticket::query()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'uuid' => $ticketUuid,
            'ticket_number' => 'MOB01-260520-9998',
            'total_amount' => '50.00',
            'status' => 'ACTIVE',
            'sold_at' => now(),
            'sale_mode' => 'MOBILE',
        ]);

        $detail = TicketDetail::query()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'ticket_id' => $ticket->id,
            'lottery_id' => $this->draw->lottery_id,
            'draw_id' => $this->draw->id,
            'bet_type_id' => $this->betType->id,
            'number_value' => '34',
            'normalized_number' => '34',
            'amount' => '50.00',
            'payout_multiplier' => '72.00',
            'possible_prize' => '3600.00',
            'status' => 'ACTIVE',
        ]);

        WinnerTicket::query()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'ticket_id' => $ticket->id,
            'ticket_detail_id' => $detail->id,
            'lottery_id' => $this->draw->lottery_id,
            'draw_id' => $this->draw->id,
            'bet_type_id' => $this->betType->id,
            'number_value' => '34',
            'matched_position' => 'FIRST',
            'amount_played' => '50.00',
            'payout_multiplier' => '72.00',
            'prize_amount' => '3600.00',
            'status' => 'RELEASED',
        ]);

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Requested-With' => 'BSLoteria-Android',
            'X-Device-UUID' => $this->deviceUuid,
        ])->postJson("/api/mobile/tickets/{$ticketUuid}/pay-prize")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Debe abrir caja antes de pagar premios.');
    }

    private function mobileLogin(): string
    {
        $response = $this->postJson('/api/mobile/login', [
            'login' => 'admin',
            'password' => 'Password1234',
            'device_uuid' => $this->deviceUuid,
            'device_name' => 'Pixel Test',
            'platform' => 'ANDROID',
            'app_version' => '1.0.0',
        ])->assertOk();

        return $response->json('token');
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
