<?php

namespace Tests\Feature;

use App\Models\BetType;
use App\Models\BankTransfer;
use App\Models\Branch;
use App\Models\CashCountDenomination;
use App\Models\CashIncident;
use App\Models\CashMovement;
use App\Models\CashReconciliation;
use App\Models\CashSession;
use App\Models\Draw;
use App\Models\LicenseState;
use App\Models\LimitRule;
use App\Models\Lottery;
use App\Models\PayoutRule;
use App\Models\PrinterConfig;
use App\Models\PrintJob;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccountingAccountSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SaleAndPrizeCycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Branch $branch;
    private Lottery $lottery;
    private Draw $draw;
    private BetType $betType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->createValidLicenseState();

        // Setup inicial
        $this->post(route('setup.initial.store'), [
            'company_name' => 'Banca Test',
            'legal_name' => 'Banca Test SRL',
            'rnc' => '123456789',
            'phone' => '809-555-0000',
            'company_email' => 'test@example.com',
            'address' => 'Calle Test #1',
            'branch_code' => 'TEST01',
            'branch_name' => 'Sucursal Test',
            'admin_name' => 'Admin Test',
            'admin_username' => 'admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'Password1234',
            'admin_password_confirmation' => 'Password1234',
        ]);

        // Seed contable después de crear la empresa
        $this->seed(AccountingAccountSeeder::class);

        $this->admin = auth()->user();
        $this->branch = Branch::first();

        // Lotería
        $this->lottery = Lottery::create([
            'company_id' => $this->branch->company_id,
            'name' => 'Lotería Nacional',
            'code' => 'LOTNAC',
            'country' => 'DO',
            'status' => 'ACTIVE',
        ]);

        // Tipo de jugada: Quiniela (2 dígitos)
        $this->betType = BetType::create([
            'company_id' => $this->branch->company_id,
            'code' => 'QUINIELA',
            'name' => 'Quiniela',
            'digits_count' => 2,
            'min_numbers' => 1,
            'max_numbers' => 1,
            'requires_position' => false,
            'status' => 'ACTIVE',
        ]);

        // Sorteo abierto
        $this->draw = Draw::create([
            'company_id' => $this->branch->company_id,
            'lottery_id' => $this->lottery->id,
            'name' => 'Sorteo 12:30 PM',
            'draw_date' => now()->toDateString(),
            'scheduled_time' => '12:30',
            'close_time' => '23:59',
            'status' => 'OPEN',
        ]);

        // Regla de pago: 80x1 para Quiniela
        PayoutRule::create([
            'company_id' => $this->branch->company_id,
            'bet_type_id' => $this->betType->id,
            'match_type' => 'DIRECT',
            'payout_multiplier' => 80,
            'effective_from' => now()->subDay(),
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
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
                'company_name' => 'Banca Test SRL',
                'trade_name' => 'Banca Test',
                'branch_name' => 'Sucursal Test',
                'rnc' => '123456789',
                'phone' => '809-555-0000',
                'address' => 'Calle Test #1',
                'company_id' => 'banca-test',
                'branch_id' => 'TEST01',
            ],
            'client' => ['code' => 'cliente-demo', 'name' => 'Cliente Demo'],
            'location' => ['code' => 'TEST01', 'name' => 'Sucursal Test'],
            'is_active' => true,
        ]);
    }

    private function openCash(string $openingAmount = '5000.00'): void
    {
        $this->post(route('admin.cash.open.store'), [
            'branch_id' => $this->branch->id,
            'opening_amount' => $openingAmount,
        ])->assertRedirect();
    }

    /** @test */
    public function cash_close_form_authorizes_without_policy_argument_error(): void
    {
        $this->openCash();

        $this->get(route('admin.cash.close'))
            ->assertOk()
            ->assertSee('Cerrar caja');
    }

    /** @test */
    public function cash_close_persists_counted_cash_and_shortage_summary(): void
    {
        $this->openCash('5000.00');

        $this->post(route('admin.cash.close.store'), [
            'counted_cash' => '4500.00',
            'notes' => 'Conteo físico de prueba.',
        ])
            ->assertRedirect(route('admin.cash.index'))
            ->assertSessionHas('cash_close_summary');

        $this->assertDatabaseHas('cash_sessions', [
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'status' => 'CLOSED',
            'expected_cash' => '5000.00',
            'counted_cash' => '4500.00',
            'shortage_amount' => '500.00',
            'surplus_amount' => '0.00',
        ]);

        $this->get(route('admin.cash.index'))
            ->assertOk()
            ->assertSee('RD$ 4,500.00')
            ->assertSee('-RD$ 500.00')
            ->assertSee('Faltante RD$ 500.00');
    }

    /** @test */
    public function cash_shortage_incident_is_assigned_to_cashier_not_to_admin_who_closed_session(): void
    {
        // El responsable de la incidencia debe ser el dueño de la sesión, no quien
        // ejecuta el cierre. Si un admin cierra la caja de un cajero (caso programático
        // o futuro flujo de supervisión), el descuento posterior en nómina (ver
        // PayrollService::buildEmployeeDetail) debe imputarse al cajero, no al admin.
        $cashierRole = Role::where('slug', 'CASHIER')->whereNull('company_id')->firstOrFail();
        $cashier = User::create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'role_id' => $cashierRole->id,
            'name' => 'Cajero Test',
            'username' => 'cajero-test-'.uniqid(),
            'email' => 'cajero-'.uniqid().'@test.local',
            'password' => Hash::make('Password1234'),
            'status' => 'ACTIVE',
            'must_change_password' => false,
        ]);

        $cashService = app(\App\Services\Cash\CashService::class);
        $session = $cashService->open($this->branch, $cashier, '5000.00');
        $this->assertEquals($cashier->id, $session->user_id);

        // Admin cierra la sesion del cajero con faltante de 500.
        $cashService->close($session, $this->admin, '4500.00', 'Cierre administrativo.', []);

        $incident = \App\Models\CashIncident::where('type', 'CASH_SHORTAGE')
            ->where('cash_session_id', $session->id)
            ->firstOrFail();

        $this->assertEquals($cashier->id, $incident->user_id, 'La incidencia debe asignarse al cajero, no al admin que cierra la caja.');
        $this->assertNotEquals($this->admin->id, $incident->user_id);
        $this->assertEquals('500.00', $incident->amount);
    }

    /** @test */
    public function cash_close_by_denominations_creates_reconciliation_and_incident(): void
    {
        $this->openCash('5000.00');

        $this->post(route('admin.cash.close.store'), [
            'notes' => 'Arqueo por denominaciones.',
            'denominations' => [
                'bill_2000' => 2,
                'bill_500' => 1,
            ],
        ])
            ->assertRedirect(route('admin.cash.index'))
            ->assertSessionHas('cash_close_summary');

        $session = CashSession::where('branch_id', $this->branch->id)->firstOrFail();

        $this->assertEquals('CLOSED', $session->status);
        $this->assertEquals('4500.00', $session->counted_cash);
        $this->assertEquals('500.00', $session->shortage_amount);

        $reconciliation = CashReconciliation::where('cash_session_id', $session->id)->firstOrFail();

        $this->assertEquals('5000.00', $reconciliation->expected_cash);
        $this->assertEquals('4500.00', $reconciliation->counted_cash);
        $this->assertEquals('-500.00', $reconciliation->difference_amount);
        $this->assertEquals('PENDING_REVIEW', $reconciliation->status);

        $this->assertDatabaseHas('cash_count_denominations', [
            'cash_reconciliation_id' => $reconciliation->id,
            'type' => 'BILL',
            'denomination' => '2000.00',
            'quantity' => 2,
            'subtotal' => '4000.00',
        ]);
        $this->assertEquals(10, CashCountDenomination::where('cash_reconciliation_id', $reconciliation->id)->count());

        $this->assertDatabaseHas('cash_incidents', [
            'cash_session_id' => $session->id,
            'cash_reconciliation_id' => $reconciliation->id,
            'type' => 'CASH_SHORTAGE',
            'severity' => 'CRITICAL',
            'amount' => '500.00',
            'status' => 'OPEN',
        ]);
    }

    /** @test */
    public function transfer_movements_do_not_increase_physical_expected_cash(): void
    {
        $this->openCash('1000.00');

        $session = CashSession::where('branch_id', $this->branch->id)->where('status', 'OPEN')->firstOrFail();

        app(\App\Services\Cash\CashService::class)->recordMovement(
            session: $session,
            user: $this->admin,
            type: 'SALE',
            amount: '500.00',
            direction: 'IN',
            description: 'Venta por transferencia confirmada.',
            paymentMethod: 'BANK_TRANSFER',
        );

        $session->refresh();
        $this->assertEquals('1000.00', $session->expected_cash);
        $this->assertEquals('0.00', $session->sales_total);

        $this->post(route('admin.cash.close.store'), [
            'counted_cash' => '1000.00',
            'notes' => 'Cierre con transferencia.',
        ])->assertRedirect(route('admin.cash.index'));

        $reconciliation = CashReconciliation::where('cash_session_id', $session->id)->firstOrFail();

        $this->assertEquals('0.00', $reconciliation->cash_sales_total);
        $this->assertEquals('500.00', $reconciliation->transfer_sales_total);
        $this->assertEquals('1000.00', $reconciliation->expected_cash);
        $this->assertEquals('MATCHED', $reconciliation->status);

        $this->assertDatabaseHas('cash_movements', [
            'cash_session_id' => $session->id,
            'type' => 'SALE',
            'payment_method' => 'BANK_TRANSFER',
            'amount' => '500.00',
        ]);
        $this->assertEquals(0, CashIncident::where('cash_session_id', $session->id)->count());
        $this->assertEquals(1, CashMovement::where('cash_session_id', $session->id)->count());
    }

    /** @test */
    public function pending_bank_transfer_blocks_cash_close_until_verified(): void
    {
        $this->openCash('5000.00');

        $this->post(route('admin.cash.transfers.store'), [
            'movement_type' => 'SALE',
            'bank_name' => 'Banco Popular',
            'reference' => 'TX-12345',
            'amount' => '750.00',
            'notes' => 'Venta por transferencia pendiente.',
        ])->assertRedirect(route('admin.cash.transfers.index'))->assertSessionHasNoErrors();

        $transfer = BankTransfer::firstOrFail();
        $this->assertEquals('PENDING', $transfer->status);
        $this->assertEquals(0, CashMovement::where('bank_transfer_id', $transfer->id)->count());

        $this->post(route('admin.cash.close.store'), [
            'counted_cash' => '5000.00',
        ])->assertRedirect()->assertSessionHasErrors();

        $this->post(route('admin.cash.transfers.confirm', $transfer))
            ->assertRedirect(route('admin.cash.transfers.index'))
            ->assertSessionHasNoErrors();

        $transfer->refresh();
        $this->assertEquals('CONFIRMED', $transfer->status);
        $this->assertNotNull($transfer->verified_at);

        $this->assertDatabaseHas('cash_movements', [
            'bank_transfer_id' => $transfer->id,
            'type' => 'SALE',
            'direction' => 'IN',
            'payment_method' => 'BANK_TRANSFER',
            'amount' => '750.00',
        ]);

        $session = CashSession::where('branch_id', $this->branch->id)->firstOrFail();
        $session->refresh();
        $this->assertEquals('5000.00', $session->expected_cash);

        $this->post(route('admin.cash.close.store'), [
            'counted_cash' => '5000.00',
        ])->assertRedirect(route('admin.cash.index'))->assertSessionHasNoErrors();

        $reconciliation = CashReconciliation::where('cash_session_id', $session->id)->firstOrFail();
        $this->assertEquals('750.00', $reconciliation->transfer_sales_total);
        $this->assertEquals('MATCHED', $reconciliation->status);
    }

    /** @test */
    public function cash_transfer_and_incident_pages_are_accessible_to_authorized_admin(): void
    {
        $this->openCash('5000.00');

        $this->get(route('admin.cash.transfers.index'))
            ->assertOk()
            ->assertSee('Transferencias');

        $this->get(route('admin.cash.transfers.create'))
            ->assertOk()
            ->assertSee('Nueva transferencia');

        $this->get(route('admin.cash.incidents.index'))
            ->assertOk()
            ->assertSee('Incidencias de caja');
    }

    /** @test */
    public function cash_incident_can_be_resolved_with_audit_trail(): void
    {
        $this->openCash('5000.00');

        $this->post(route('admin.cash.close.store'), [
            'counted_cash' => '4500.00',
        ])->assertRedirect(route('admin.cash.index'));

        $incident = CashIncident::where('type', 'CASH_SHORTAGE')->firstOrFail();

        $this->post(route('admin.cash.incidents.resolve', $incident), [
            'resolution_notes' => 'Supervisor verifico el faltante y documento el caso.',
        ])->assertRedirect(route('admin.cash.incidents.index'))->assertSessionHasNoErrors();

        $incident->refresh();
        $this->assertEquals('RESOLVED', $incident->status);
        $this->assertEquals($this->admin->id, $incident->resolved_by);
        $this->assertNotNull($incident->resolved_at);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Cash',
            'action' => 'incident_resolved',
        ]);
    }

    /** @test */
    public function cash_incident_can_be_dismissed_without_payroll_charge(): void
    {
        $this->openCash('5000.00');

        $this->post(route('admin.cash.close.store'), [
            'counted_cash' => '4500.00',
        ])->assertRedirect(route('admin.cash.index'));

        $incident = CashIncident::where('type', 'CASH_SHORTAGE')->firstOrFail();
        $this->assertEquals('OPEN', $incident->status);

        $this->post(route('admin.cash.incidents.dismiss', $incident), [
            'dismiss_reason' => 'Faltante por error administrativo, no imputable al cajero.',
        ])->assertRedirect(route('admin.cash.incidents.index'))->assertSessionHasNoErrors();

        $incident->refresh();
        $this->assertEquals('DISMISSED', $incident->status);
        $this->assertEquals($this->admin->id, $incident->resolved_by);
        $this->assertNotNull($incident->resolved_at);
        $this->assertStringContainsString('error administrativo', $incident->resolution_notes);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Cash',
            'action' => 'incident_dismissed',
        ]);
    }

    /** @test */
    public function dismissing_a_non_open_incident_returns_validation_error(): void
    {
        $this->openCash('5000.00');

        $this->post(route('admin.cash.close.store'), [
            'counted_cash' => '4500.00',
        ])->assertRedirect(route('admin.cash.index'));

        $incident = CashIncident::where('type', 'CASH_SHORTAGE')->firstOrFail();

        // Primer dismiss: OK
        $this->post(route('admin.cash.incidents.dismiss', $incident), [
            'dismiss_reason' => 'Motivo inicial registrado.',
        ])->assertRedirect(route('admin.cash.incidents.index'));

        // Segundo intento sobre incidencia ya DISMISSED debe fallar
        $this->post(route('admin.cash.incidents.dismiss', $incident), [
            'dismiss_reason' => 'Intento sobre incidencia ya cerrada.',
        ])->assertSessionHasErrors();

        $incident->refresh();
        $this->assertEquals('DISMISSED', $incident->status);
    }

    private function sellQuiniela(string $number, string $amount = '100.00'): void
    {
        $this->post(route('admin.tickets.store'), [
            'branch_id' => $this->branch->id,
            'draw_id' => $this->draw->id,
            'plays' => [
                ['bet_type_id' => $this->betType->id, 'number_value' => $number, 'amount' => $amount],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();
    }

    private function registerConfirmedWinningResult(string $firstNumber = '34'): void
    {
        $this->post(route('admin.draws.close', $this->draw))->assertRedirect();
        $this->post(route('admin.results.store'), [
            'lottery_id' => $this->lottery->id,
            'draw_id' => $this->draw->id,
            'first_number' => $firstNumber,
            'second_number' => '12',
            'third_number' => '56',
        ])->assertRedirect();

        $result = \App\Models\Result::where('draw_id', $this->draw->id)->first();
        $this->confirmResultAsSecondAdmin($result);
    }

    private function confirmResultAsSecondAdmin(\App\Models\Result $result): void
    {
        $confirmer = User::firstOrCreate(
            ['email' => 'confirmador@example.com'],
            [
                'company_id' => $this->branch->company_id,
                'branch_id' => $this->branch->id,
                'name' => 'Admin Confirmador',
                'username' => 'confirmador',
                'password' => Hash::make('Password1234'),
                'role_id' => $this->admin->role_id,
                'status' => 'ACTIVE',
            ],
        );

        $this->actingAs($confirmer)
            ->post(route('admin.results.confirm', $result))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin);
    }

    /** @test */
    public function full_sale_to_prize_cycle(): void
    {
        // 1. Abrir caja
        $this->post(route('admin.cash.open.store'), [
            'branch_id' => $this->branch->id,
            'opening_amount' => '10000.00',
            'notes' => 'Caja inicial de prueba',
        ])->assertRedirect();

        $this->assertDatabaseHas('cash_sessions', [
            'branch_id' => $this->branch->id,
            'status' => 'OPEN',
            'opening_amount' => 10000.00,
        ]);

        $session = CashSession::where('branch_id', $this->branch->id)->where('status', 'OPEN')->first();
        $this->assertNotNull($session, 'Debe existir una sesión de caja abierta');

        // 2. Vender ticket: jugar 34 por RD$100 a Quiniela
        $response = $this->post(route('admin.tickets.store'), [
            'branch_id' => $this->branch->id,
            'draw_id' => $this->draw->id,
            'plays' => [
                [
                    'bet_type_id' => $this->betType->id,
                    'number_value' => '34',
                    'amount' => '100.00',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Verificar ticket creado
        $this->assertDatabaseHas('tickets', [
            'branch_id' => $this->branch->id,
            'sale_mode' => 'ONLINE',
            'status' => 'ACTIVE',
            'total_amount' => 100.00,
            'total_possible_prize' => 8000.00, // 100 x 80
        ]);

        $this->assertDatabaseHas('ticket_details', [
            'number_value' => '34',
            'amount' => 100.00,
            'payout_multiplier' => 80.00,
            'possible_prize' => 8000.00,
            'status' => 'ACTIVE',
        ]);

        // Verificar límite consumido
        $this->assertDatabaseHas('limit_consumptions', [
            'branch_id' => $this->branch->id,
            'number_value' => '34',
            'sold_amount' => 100.00,
        ]);

        // Verificar movimiento de caja
        $session->refresh();
        $this->assertEquals(100.00, (float) $session->sales_total);

        // Verificar asiento contable
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'Ticket',
            'description' => 'Venta de tickets — RD$ 100.00',
        ]);

        // Verificar print job
        $this->assertDatabaseHas('print_jobs', [
            'type' => 'TICKET',
            'status' => 'PENDING',
        ]);

        // Verificar auditoría
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Sales',
            'action' => 'sale',
        ]);

        // 3. Cerrar sorteo
        $this->post(route('admin.draws.close', $this->draw))
            ->assertRedirect();

        $this->draw->refresh();
        $this->assertEquals('CLOSED', $this->draw->status);

        // 4. Registrar resultado: 34 en primer lugar
        $this->post(route('admin.results.store'), [
            'lottery_id' => $this->lottery->id,
            'draw_id' => $this->draw->id,
            'first_number' => '34',
            'second_number' => '12',
            'third_number' => '56',
        ])->assertRedirect();

        $this->assertDatabaseHas('results', [
            'draw_id' => $this->draw->id,
            'first_number' => '34',
            'second_number' => '12',
            'third_number' => '56',
            'status' => 'REGISTERED',
        ]);

        $this->draw->refresh();
        $this->assertEquals('RESULT_REGISTERED', $this->draw->status);

        // 5. Confirmar resultado (admin registró y confirma — tiene permiso modify_confirmed implícito como COMPANY_OWNER con todos los permisos)
        $result = \App\Models\Result::where('draw_id', $this->draw->id)->first();
        $this->confirmResultAsSecondAdmin($result);

        $result->refresh();
        $this->assertEquals('CONFIRMED', $result->status);
        $this->assertNotEquals($result->registered_by, $result->confirmed_by);
        $this->assertNotNull($result->registered_at);
        $this->assertNotNull($result->confirmed_at);

        $this->draw->refresh();
        $this->assertEquals('RESULT_CONFIRMED', $this->draw->status);

        // 6. Calcular ganadores
        $this->post(route('admin.results.calculate', $this->draw))
            ->assertRedirect();

        $this->draw->refresh();
        $this->assertEquals('WINNERS_CALCULATED', $this->draw->status);

        // Verificar winner_ticket creado
        $this->assertDatabaseHas('winner_tickets', [
            'draw_id' => $this->draw->id,
            'number_value' => '34',
            'amount_played' => 100.00,
            'payout_multiplier' => 80.00,
            'prize_amount' => 8000.00,
            'status' => 'PENDING_RELEASE',
        ]);

        // Verificar ticket_detail marcado como WINNER
        $this->assertDatabaseHas('ticket_details', [
            'number_value' => '34',
            'status' => 'WINNER',
            'result_position' => 'FIRST',
        ]);

        // Verificar ticket marcado como WINNER
        $this->assertDatabaseHas('tickets', [
            'status' => 'WINNER',
        ]);

        // Verificar payment_authorization creada
        $this->assertDatabaseHas('payment_authorizations', [
            'draw_id' => $this->draw->id,
            'status' => 'PENDING',
            'total_winners' => 1,
            'total_prize_amount' => 8000.00,
        ]);

        // 7. Autorizar pagos
        $this->post(route('admin.results.authorize', $this->draw))
            ->assertRedirect();

        $this->draw->refresh();
        $this->assertEquals('PAYMENTS_RELEASED', $this->draw->status);

        // Verificar winner_ticket liberado
        $this->assertDatabaseHas('winner_tickets', [
            'draw_id' => $this->draw->id,
            'status' => 'RELEASED',
        ]);

        $this->assertDatabaseHas('payment_authorizations', [
            'draw_id' => $this->draw->id,
            'status' => 'AUTHORIZED',
        ]);

        // 8. Pagar premio
        $winner = \App\Models\WinnerTicket::where('draw_id', $this->draw->id)->first();
        $this->post(route('admin.prizes.pay', $winner))
            ->assertRedirect();

        // Verificar premio pagado
        $this->assertDatabaseHas('prize_payments', [
            'winner_ticket_id' => $winner->id,
            'amount' => 8000.00,
            'status' => 'PAID',
        ]);

        $winner->refresh();
        $this->assertEquals('PAID', $winner->status);
        $this->assertNotNull($winner->paid_at);

        // Verificar ticket_detail PAID
        $this->assertDatabaseHas('ticket_details', [
            'number_value' => '34',
            'status' => 'PAID',
        ]);

        // Verificar ticket PAID
        $this->assertDatabaseHas('tickets', [
            'status' => 'PAID',
        ]);

        // Verificar movimiento de caja (premio pagado descuenta)
        $session->refresh();
        $this->assertEquals(8000.00, (float) $session->prizes_paid_total);

        // Verificar asiento contable de premio
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'PrizePayment',
            'description' => 'Pago de premio — RD$ 8,000.00',
        ]);

        // Verificar auditoría
        $this->assertDatabaseHas('audit_logs', ['module' => 'Prizes', 'action' => 'paid']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Results', 'action' => 'winners_calculated']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Results', 'action' => 'payments_authorized']);

        // Verificar efectivo esperado final
        // Formula: 10000 + 100 - 8000 = 2100
        $session->refresh();
        $session->recalculateExpectedCash();
        $this->assertEquals(2100.00, (float) $session->expected_cash);
    }

    /** @test */
    public function sale_multiple_plays_picks_correct_winners(): void
    {
        // PALE: jugada de 2 números
        $pale = BetType::create([
            'company_id' => $this->branch->company_id,
            'code' => 'PALE',
            'name' => 'Pale',
            'digits_count' => 2,
            'min_numbers' => 1,
            'max_numbers' => 1,
            'numbers_count' => 2,
            'requires_position' => false,
            'is_cross_lottery' => false,
            'status' => 'ACTIVE',
        ]);

        // Pago Pale: 1ª-2ª (FIRST) = 60x
        PayoutRule::create([
            'company_id' => $this->branch->company_id,
            'bet_type_id' => $pale->id,
            'match_type' => 'DIRECT',
            'payout_multiplier' => 60,
            'effective_from' => now()->subDay(),
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);

        PayoutRule::create([
            'company_id' => $this->branch->company_id,
            'bet_type_id' => $pale->id,
            'position' => 'FIRST',
            'match_type' => 'DIRECT',
            'payout_multiplier' => 60,
            'effective_from' => now()->subDay(),
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);

        // Abrir caja
        $this->post(route('admin.cash.open.store'), [
            'branch_id' => $this->branch->id,
            'opening_amount' => '10000.00',
        ])->assertRedirect();

        // Vender: Quiniela 34, Pale 34-12 (gana 1ª-2ª), Quiniela 99 (pierde)
        $this->post(route('admin.tickets.store'), [
            'branch_id' => $this->branch->id,
            'draw_id' => $this->draw->id,
            'plays' => [
                ['bet_type_id' => $this->betType->id, 'number_value' => '34', 'amount' => '100.00'],
                ['bet_type_id' => $pale->id, 'number_value' => '34-12', 'amount' => '50.00'],
                ['bet_type_id' => $this->betType->id, 'number_value' => '99', 'amount' => '200.00'],
            ],
        ])->assertSessionHasNoErrors();

        $ticket = \App\Models\Ticket::first();

        // Cerrar sorteo
        $this->post(route('admin.draws.close', $this->draw))->assertRedirect();

        // Registrar resultado: 34 (1ro), 12 (2do), 56 (3ro)
        $this->post(route('admin.results.store'), [
            'lottery_id' => $this->lottery->id,
            'draw_id' => $this->draw->id,
            'first_number' => '34',
            'second_number' => '12',
            'third_number' => '56',
        ])->assertRedirect();

        $result = \App\Models\Result::where('draw_id', $this->draw->id)->first();
        $this->confirmResultAsSecondAdmin($result);

        // Calcular ganadores
        $this->post(route('admin.results.calculate', $this->draw))->assertRedirect();

        // Verificar: 2 ganadores (34 quiniela y 34-12 pale)
        $this->assertEquals(2, \App\Models\WinnerTicket::where('draw_id', $this->draw->id)->count());

        // 34: Quiniela, FIRST, 100 x 80 = 8000
        $this->assertDatabaseHas('winner_tickets', [
            'number_value' => '34',
            'bet_type_id' => $this->betType->id,
            'matched_position' => 'FIRST',
            'amount_played' => 100.00,
            'payout_multiplier' => 80.00,
            'prize_amount' => 8000.00,
        ]);

        // 34-12: Pale, 1ª-2ª, 50 x 60 = 3000
        $this->assertDatabaseHas('winner_tickets', [
            'number_value' => '34-12',
            'bet_type_id' => $pale->id,
            'matched_position' => 'pale_primera_segunda',
            'amount_played' => 50.00,
            'payout_multiplier' => 60.00,
            'prize_amount' => 3000.00,
        ]);

        // 99: LOSER
        $this->assertDatabaseHas('ticket_details', [
            'number_value' => '99',
            'status' => 'LOSER',
        ]);

        // Verificar total premio en payment_authorization
        $this->assertDatabaseHas('payment_authorizations', [
            'draw_id' => $this->draw->id,
            'total_winners' => 2,
            'total_prize_amount' => 11000.00, // 8000 + 3000
        ]);
    }

    /** @test */
    public function prize_cannot_be_paid_before_authorization(): void
    {
        // Abrir caja y vender
        $this->post(route('admin.cash.open.store'), [
            'branch_id' => $this->branch->id,
            'opening_amount' => '5000.00',
        ])->assertRedirect();

        $this->post(route('admin.tickets.store'), [
            'branch_id' => $this->branch->id,
            'draw_id' => $this->draw->id,
            'plays' => [['bet_type_id' => $this->betType->id, 'number_value' => '34', 'amount' => '100.00']],
        ])->assertRedirect();

        // Registrar y confirmar resultado
        $this->post(route('admin.draws.close', $this->draw))->assertRedirect();
        $this->post(route('admin.results.store'), [
            'lottery_id' => $this->lottery->id,
            'draw_id' => $this->draw->id,
            'first_number' => '34',
        ])->assertRedirect();
        $result = \App\Models\Result::first();
        $this->confirmResultAsSecondAdmin($result);

        // Calcular ganadores
        $this->post(route('admin.results.calculate', $this->draw))->assertRedirect();

        // Intentar pagar antes de autorizar — debe fallar
        $winner = \App\Models\WinnerTicket::first();
        $this->assertEquals('PENDING_RELEASE', $winner->status);

        $this->post(route('admin.prizes.pay', $winner))
            ->assertRedirect()
            ->assertSessionHasErrors();

        // Verificar que el premio sigue sin pagar
        $winner->refresh();
        $this->assertEquals('PENDING_RELEASE', $winner->status);
        $this->assertDatabaseCount('prize_payments', 0);
    }

    /** @test */
    public function same_user_cannot_register_and_confirm_result(): void
    {
        $cajeroRole = Role::where('slug', 'CASHIER')->first();
        // Dar permisos de resultados al cajero para esta prueba
        $permCreate = \App\Models\Permission::where('slug', 'results.create')->first();
        $permConfirm = \App\Models\Permission::where('slug', 'results.confirm')->first();
        $cajeroRole->permissions()->syncWithoutDetaching([$permCreate->id, $permConfirm->id]);

        $cajero = User::create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'name' => 'Cajero Test',
            'username' => 'cajero',
            'email' => 'cajero@example.com',
            'password' => Hash::make('Password1234'),
            'role_id' => $cajeroRole->id,
            'status' => 'ACTIVE',
        ]);

        // Cerrar sorteo
        $this->post(route('admin.draws.close', $this->draw))->assertRedirect();

        // Cajero registra resultado
        $this->actingAs($cajero);

        $this->post(route('admin.results.store'), [
            'lottery_id' => $this->lottery->id,
            'draw_id' => $this->draw->id,
            'first_number' => '34',
        ])->assertRedirect();

        $result = \App\Models\Result::first();
        $this->assertEquals('REGISTERED', $result->status);

        // El mismo cajero intenta confirmar — debe fallar
        $this->post(route('admin.results.confirm', $result))
            ->assertRedirect()
            ->assertSessionHasErrors();

        $result->refresh();
        $this->assertEquals('REGISTERED', $result->status);
    }

    /** @test */
    public function result_can_be_auto_confirmed_when_company_setting_disables_confirmation(): void
    {
        \App\Models\SystemSetting::setBoolean($this->branch->company_id, 'results.require_confirmation', false);

        $this->post(route('admin.draws.close', $this->draw))->assertRedirect();

        $this->post(route('admin.results.store'), [
            'lottery_id' => $this->lottery->id,
            'draw_id' => $this->draw->id,
            'first_number' => '34',
            'second_number' => '12',
            'third_number' => '56',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $result = \App\Models\Result::first();

        $this->assertEquals('CONFIRMED', $result->status);
        $this->assertEquals($this->admin->id, $result->registered_by);
        $this->assertEquals($this->admin->id, $result->confirmed_by);
        $this->assertNotNull($result->registered_at);
        $this->assertNotNull($result->confirmed_at);

        $this->draw->refresh();
        $this->assertEquals('RESULT_CONFIRMED', $this->draw->status);
    }

    /** @test */
    public function ticket_cancellation_reverts_limits_and_records_movement(): void
    {
        // Abrir caja y vender 2 jugadas
        $this->post(route('admin.cash.open.store'), [
            'branch_id' => $this->branch->id,
            'opening_amount' => '5000.00',
        ])->assertRedirect();

        $this->post(route('admin.tickets.store'), [
            'branch_id' => $this->branch->id,
            'draw_id' => $this->draw->id,
            'plays' => [
                ['bet_type_id' => $this->betType->id, 'number_value' => '34', 'amount' => '100.00'],
                ['bet_type_id' => $this->betType->id, 'number_value' => '56', 'amount' => '50.00'],
            ],
        ])->assertRedirect();

        $ticket = \App\Models\Ticket::first();
        $this->assertNotNull($ticket);

        // Verificar límites consumidos
        $this->assertDatabaseHas('limit_consumptions', [
            'number_value' => '34',
            'sold_amount' => 100.00,
        ]);
        $this->assertDatabaseHas('limit_consumptions', [
            'number_value' => '56',
            'sold_amount' => 50.00,
        ]);

        // Anular ticket
        $this->post(route('admin.tickets.cancel', $ticket), [
            'cancel_reason' => 'Cliente se arrepintió',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertEquals('CANCELLED', $ticket->status);
        $this->assertEquals('Cliente se arrepintió', $ticket->cancel_reason);

        // Verificar detalles cancelados
        $this->assertDatabaseHas('ticket_details', ['status' => 'CANCELLED']);

        // Verificar cancelled_amount incrementado
        $this->assertDatabaseHas('limit_consumptions', [
            'number_value' => '34',
            'cancelled_amount' => 100.00,
        ]);
        $this->assertDatabaseHas('limit_consumptions', [
            'number_value' => '56',
            'cancelled_amount' => 50.00,
        ]);

        // Verificar movimiento de caja por anulación
        $this->assertDatabaseHas('cash_movements', [
            'type' => 'CANCELLATION',
            'direction' => 'OUT',
            'amount' => 150.00,
        ]);

        // Verificar auditoría
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Sales',
            'action' => 'cancel',
        ]);
    }

    /** @test */
    public function closing_draw_can_transfer_active_tickets_to_next_draw(): void
    {
        $this->post(route('admin.cash.open.store'), [
            'branch_id' => $this->branch->id,
            'opening_amount' => '5000.00',
        ])->assertRedirect();

        $nextDraw = Draw::create([
            'company_id' => $this->branch->company_id,
            'lottery_id' => $this->lottery->id,
            'name' => 'Sorteo siguiente',
            'draw_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '12:30',
            'close_time' => '23:59',
            'status' => 'OPEN',
        ]);

        $this->post(route('admin.tickets.store'), [
            'branch_id' => $this->branch->id,
            'draw_id' => $this->draw->id,
            'plays' => [
                ['bet_type_id' => $this->betType->id, 'number_value' => '34', 'amount' => '100.00'],
            ],
        ])->assertRedirect();

        $ticket = \App\Models\Ticket::first();
        $this->assertNotNull($ticket);

        $this->post(route('admin.draws.close', $this->draw), [
            'ticket_resolution_policy' => 'TRANSFER_NEXT',
            'reason' => 'Lluvia fuerte',
        ])->assertRedirect();

        $this->draw->refresh();
        $ticket->refresh();

        $this->assertEquals('CLOSED', $this->draw->status);
        $this->assertEquals('ACTIVE', $ticket->status);
        $this->assertDatabaseHas('ticket_details', [
            'ticket_id' => $ticket->id,
            'draw_id' => $nextDraw->id,
            'transferred_from_draw_id' => $this->draw->id,
            'status' => 'ACTIVE',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Draw',
            'action' => 'closed',
        ]);
    }

    /** @test */
    public function cancelling_draw_can_cancel_active_tickets(): void
    {
        $this->post(route('admin.cash.open.store'), [
            'branch_id' => $this->branch->id,
            'opening_amount' => '5000.00',
        ])->assertRedirect();

        $this->post(route('admin.tickets.store'), [
            'branch_id' => $this->branch->id,
            'draw_id' => $this->draw->id,
            'plays' => [
                ['bet_type_id' => $this->betType->id, 'number_value' => '34', 'amount' => '100.00'],
            ],
        ])->assertRedirect();

        $ticket = \App\Models\Ticket::first();
        $this->assertNotNull($ticket);

        $this->post(route('admin.draws.cancel', $this->draw), [
            'ticket_resolution_policy' => 'CANCEL_TICKETS',
            'reason' => 'Sorteo suspendido por operador',
        ])->assertRedirect();

        $this->draw->refresh();
        $ticket->refresh();

        $this->assertEquals('CANCELLED', $this->draw->status);
        $this->assertEquals('CANCELLED', $ticket->status);
        $this->assertEquals('Sorteo suspendido por operador', $this->draw->cancel_reason);
        $this->assertDatabaseHas('ticket_details', [
            'ticket_id' => $ticket->id,
            'draw_id' => $this->draw->id,
            'status' => 'CANCELLED',
        ]);
        $this->assertDatabaseHas('cash_movements', [
            'type' => 'CANCELLATION',
            'direction' => 'OUT',
            'amount' => 100.00,
        ]);
    }

    /** @test */
    public function sale_persists_payout_limit_cash_accounting_and_print_artifacts(): void
    {
        $limitRule = LimitRule::create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'lottery_id' => $this->lottery->id,
            'draw_id' => $this->draw->id,
            'bet_type_id' => $this->betType->id,
            'rule_type' => 'SINGLE_NUMBER',
            'number_value' => '34',
            'max_amount_per_number' => '1000.00',
            'policy' => 'BLOCK_FULL',
            'effective_from' => now()->subDay(),
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);

        $this->openCash();
        $this->sellQuiniela('34', '100.00');

        $ticket = \App\Models\Ticket::first();
        $detail = $ticket->details()->first();
        $payoutRule = PayoutRule::first();

        $this->assertEquals($payoutRule->id, $detail->payout_rule_id);
        $this->assertEquals('80.00', $detail->payout_multiplier);
        $this->assertEquals('8000.00', $detail->possible_prize);
        $this->assertEquals($limitRule->id, $detail->limit_rule_id);

        $this->assertDatabaseHas('limit_consumptions', [
            'number_value' => '34',
            'sold_amount' => 100.00,
        ]);
        $this->assertDatabaseHas('cash_movements', [
            'type' => 'SALE',
            'direction' => 'IN',
            'amount' => 100.00,
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'Ticket',
            'source_id' => $ticket->id,
        ]);
        $this->assertDatabaseHas('print_jobs', [
            'ticket_id' => $ticket->id,
            'type' => 'TICKET',
        ]);
    }

    /** @test */
    public function sale_without_position_uses_primary_payout_position(): void
    {
        PayoutRule::query()->delete();

        $payoutRule = PayoutRule::create([
            'company_id' => $this->branch->company_id,
            'bet_type_id' => $this->betType->id,
            'position' => 'FIRST',
            'match_type' => 'DIRECT',
            'payout_multiplier' => 72,
            'effective_from' => now()->subDay(),
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);

        $this->openCash();

        $this->post(route('admin.tickets.store'), [
            'branch_id' => $this->branch->id,
            'draw_id' => $this->draw->id,
            'plays' => [
                ['bet_type_id' => $this->betType->id, 'number_value' => '25', 'amount' => '10.00'],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $ticket = \App\Models\Ticket::first();
        $detail = $ticket->details()->first();

        $this->assertEquals($payoutRule->id, $detail->payout_rule_id);
        $this->assertEquals('72.00', $detail->payout_multiplier);
        $this->assertEquals('720.00', $detail->possible_prize);
    }

    /** @test */
    public function sale_and_reprint_use_configured_ticket_print_formatter(): void
    {
        $printer = PrinterConfig::create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'name' => 'Caja 88MM',
            'printer_type' => 'THERMAL',
            'connection_type' => 'WINDOWS_SHARED',
            'paper_width' => '88MM',
            'printer_identifier' => '\\\\localhost\\Caja88',
            'status' => 'ACTIVE',
        ]);

        $this->openCash();
        $this->sellQuiniela('34', '100.00');

        $ticket = \App\Models\Ticket::first();
        $job = PrintJob::where('ticket_id', $ticket->id)->where('type', 'TICKET')->first();

        $this->assertNotNull($job);
        $this->assertEquals($printer->id, $job->printer_config_id);
        $this->assertStringContainsString('Loteria    Sorteo   Tipo', $job->content);
        $this->assertStringContainsString('RD$100', $job->content);
        $this->assertStringContainsString('RD$8,000', $job->content);
        // El marcador [[QR:<url>]] codifica la URL publica del ticket; el print agent
        // lo reemplaza por bytes ESC/POS de QR real al imprimir.
        $this->assertStringContainsString('[[QR:', $job->content);
        $this->assertStringContainsString('/t/'.$ticket->uuid, $job->content);
        $this->assertStringContainsString('VAL:', $job->content);

        $this->post(route('admin.tickets.reprint', $ticket))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $reprintJob = PrintJob::where('ticket_id', $ticket->id)->where('type', 'REPRINT')->first();

        $this->assertNotNull($reprintJob);
        $this->assertEquals($printer->id, $reprintJob->printer_config_id);
        $this->assertStringContainsString('REIMPRESION', $reprintJob->content);
        $this->assertStringContainsString('Loteria    Sorteo   Tipo', $reprintJob->content);
    }

    /** @test */
    public function range_limit_is_consumed_independently_per_number(): void
    {
        LimitRule::create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'lottery_id' => $this->lottery->id,
            'draw_id' => $this->draw->id,
            'bet_type_id' => $this->betType->id,
            'rule_type' => 'NUMBER_RANGE',
            'number_from' => '00',
            'number_to' => '50',
            'max_amount_per_number' => '1000.00',
            'policy' => 'BLOCK_FULL',
            'effective_from' => now()->subDay(),
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);

        $this->openCash();
        $this->sellQuiniela('25', '1000.00');
        $this->sellQuiniela('30', '1000.00');

        $this->post(route('admin.tickets.store'), [
            'branch_id' => $this->branch->id,
            'draw_id' => $this->draw->id,
            'plays' => [
                ['bet_type_id' => $this->betType->id, 'number_value' => '25', 'amount' => '1.00'],
            ],
        ])->assertRedirect()->assertSessionHasErrors();

        $this->assertDatabaseHas('limit_consumptions', ['number_value' => '25', 'sold_amount' => 1000.00]);
        $this->assertDatabaseHas('limit_consumptions', ['number_value' => '30', 'sold_amount' => 1000.00]);
        $this->assertEquals(2, \App\Models\Ticket::count());
    }

    /** @test */
    public function limit_validation_blocks_second_transaction_that_would_exceed_same_number(): void
    {
        LimitRule::create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'lottery_id' => $this->lottery->id,
            'draw_id' => $this->draw->id,
            'bet_type_id' => $this->betType->id,
            'rule_type' => 'SINGLE_NUMBER',
            'number_value' => '34',
            'max_amount_per_number' => '100.00',
            'policy' => 'BLOCK_FULL',
            'effective_from' => now()->subDay(),
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);

        $this->openCash();
        $this->sellQuiniela('34', '60.00');

        $this->post(route('admin.tickets.store'), [
            'branch_id' => $this->branch->id,
            'draw_id' => $this->draw->id,
            'plays' => [
                ['bet_type_id' => $this->betType->id, 'number_value' => '34', 'amount' => '50.00'],
            ],
        ])->assertRedirect()->assertSessionHasErrors();

        $this->assertEquals(1, \App\Models\Ticket::count());
        $this->assertDatabaseHas('limit_consumptions', ['number_value' => '34', 'sold_amount' => 60.00]);
    }

    /** @test */
    public function check_limit_endpoint_returns_correct_available_when_near_cap(): void
    {
        LimitRule::create([
            'company_id'             => $this->branch->company_id,
            'branch_id'              => $this->branch->id,
            'lottery_id'             => $this->lottery->id,
            'draw_id'                => $this->draw->id,
            'bet_type_id'            => $this->betType->id,
            'rule_type'              => 'SINGLE_NUMBER',
            'number_value'           => '34',
            'max_amount_per_number'  => '3000.00',
            'policy'                 => 'BLOCK_FULL',
            'effective_from'         => now()->subDay(),
            'status'                 => 'ACTIVE',
            'created_by'             => $this->admin->id,
        ]);

        $this->openCash();
        $this->sellQuiniela('34', '2800.00');

        $response = $this->getJson(route('admin.tickets.check-limit', [
            'draw_id'      => $this->draw->id,
            'bet_type_id'  => $this->betType->id,
            'number_value' => '34',
        ]));

        $response->assertOk()->assertJson([
            'available' => 200.0, // 3000 - 2800
            'blocked'   => false,
        ]);
    }

    /** @test */
    public function check_limit_endpoint_marks_blocked_when_fully_consumed(): void
    {
        LimitRule::create([
            'company_id'             => $this->branch->company_id,
            'branch_id'              => $this->branch->id,
            'lottery_id'             => $this->lottery->id,
            'draw_id'                => $this->draw->id,
            'bet_type_id'            => $this->betType->id,
            'rule_type'              => 'SINGLE_NUMBER',
            'number_value'           => '34',
            'max_amount_per_number'  => '500.00',
            'policy'                 => 'BLOCK_FULL',
            'effective_from'         => now()->subDay(),
            'status'                 => 'ACTIVE',
            'created_by'             => $this->admin->id,
        ]);

        $this->openCash();
        $this->sellQuiniela('34', '500.00');

        $response = $this->getJson(route('admin.tickets.check-limit', [
            'draw_id'      => $this->draw->id,
            'bet_type_id'  => $this->betType->id,
            'number_value' => '34',
        ]));

        $response->assertOk()->assertJson([
            'available' => 0,
            'blocked'   => true,
        ]);
    }

    /** @test */
    public function check_limit_endpoint_returns_null_available_without_rules(): void
    {
        $this->openCash();

        $response = $this->getJson(route('admin.tickets.check-limit', [
            'draw_id'      => $this->draw->id,
            'bet_type_id'  => $this->betType->id,
            'number_value' => '88',
        ]));

        $response->assertOk()->assertJson([
            'available' => null,
            'blocked'   => false,
        ]);
    }

    /** @test */
    public function sale_fails_when_no_active_payout_rule_exists(): void
    {
        PayoutRule::query()->delete();

        $this->openCash();

        $this->post(route('admin.tickets.store'), [
            'branch_id' => $this->branch->id,
            'draw_id' => $this->draw->id,
            'plays' => [
                ['bet_type_id' => $this->betType->id, 'number_value' => '34', 'amount' => '100.00'],
            ],
        ])->assertRedirect()->assertSessionHasErrors();

        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('ticket_details', 0);
    }

    /** @test */
    public function released_prize_cannot_be_paid_without_open_cash_session(): void
    {
        $this->openCash();
        $this->sellQuiniela('34', '100.00');
        $this->registerConfirmedWinningResult();
        $this->post(route('admin.results.calculate', $this->draw))->assertRedirect();
        $this->post(route('admin.results.authorize', $this->draw))->assertRedirect();

        CashSession::where('branch_id', $this->branch->id)->update(['status' => 'CLOSED']);

        $winner = \App\Models\WinnerTicket::first();
        $this->post(route('admin.prizes.pay', $winner))
            ->assertRedirect()
            ->assertSessionHasErrors();

        $winner->refresh();
        $this->assertEquals('RELEASED', $winner->status);
        $this->assertDatabaseCount('prize_payments', 0);
    }

    /** @test */
    public function released_ticket_prize_cannot_be_paid_when_cash_is_insufficient_until_admin_funds_cash(): void
    {
        $this->openCash('0.00');
        $this->sellQuiniela('34', '100.00');
        $this->registerConfirmedWinningResult();
        $this->post(route('admin.results.calculate', $this->draw))->assertRedirect();
        $this->post(route('admin.results.authorize', $this->draw))->assertRedirect();

        $ticket = \App\Models\Ticket::firstOrFail();

        $this->getJson(route('admin.tickets.lookup', ['token' => $ticket->ticket_number]))
            ->assertOk()
            ->assertJsonPath('ticket.can_pay_released_prizes', true)
            ->assertJsonPath('ticket.released_prize_total', '8000.00');

        $this->post(route('admin.tickets.pay-released-prizes', $ticket))
            ->assertRedirect()
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('prize_payments', 0);

        $session = CashSession::where('branch_id', $this->branch->id)->where('status', 'OPEN')->firstOrFail();
        app(\App\Services\Cash\CashService::class)->recordCashIn(
            session: $session,
            user: $this->admin,
            amount: '8000.00',
            description: 'Refuerzo de caja autorizado por administrador.',
        );

        $this->post(route('admin.tickets.pay-released-prizes', $ticket))
            ->assertRedirect(route('admin.tickets.show', $ticket))
            ->assertSessionHasNoErrors();

        $ticket->refresh();
        $this->assertEquals('PAID', $ticket->status);
        $this->assertNotNull($ticket->paid_at);

        $this->assertDatabaseHas('prize_payments', [
            'ticket_id' => $ticket->id,
            'amount' => '8000.00',
            'status' => 'PAID',
        ]);
        $this->assertDatabaseHas('cash_movements', [
            'cash_session_id' => $session->id,
            'type' => 'CASH_IN',
            'direction' => 'IN',
            'amount' => '8000.00',
        ]);
        $this->assertDatabaseHas('cash_movements', [
            'cash_session_id' => $session->id,
            'type' => 'PRIZE_PAYMENT',
            'direction' => 'OUT',
            'amount' => '8000.00',
        ]);
    }

    /** @test */
    public function big_prize_is_held_and_not_released_for_direct_payment(): void
    {
        $this->branch->company->update(['big_prize_threshold' => '5000.00']);

        $this->openCash();
        $this->sellQuiniela('34', '100.00');
        $this->registerConfirmedWinningResult();
        $this->post(route('admin.results.calculate', $this->draw))->assertRedirect();

        $winner = \App\Models\WinnerTicket::first();
        $this->assertEquals('HELD', $winner->status);

        $this->post(route('admin.results.authorize', $this->draw))->assertRedirect();

        $winner->refresh();
        $this->assertEquals('HELD', $winner->status);

        $this->post(route('admin.prizes.pay', $winner))
            ->assertRedirect()
            ->assertSessionHasErrors();
        $this->assertDatabaseCount('prize_payments', 0);
    }
}
