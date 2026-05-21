<?php

namespace Tests\Feature;

use App\Models\BetType;
use App\Models\Branch;
use App\Models\Draw;
use App\Models\LicenseState;
use App\Models\Lottery;
use App\Models\PayoutRule;
use App\Models\User;
use Database\Seeders\AccountingAccountSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TicketProCalculationTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private Lottery $lottery;
    private Draw $draw;
    private BetType $quiniela;
    private BetType $pale;
    private BetType $tripleta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->createValidLicenseState();

        $this->post(route('setup.initial.store'), [
            'company_name' => 'Banca TicketPro',
            'legal_name' => 'Banca TicketPro SRL',
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

        $this->seed(AccountingAccountSeeder::class);

        $this->branch = Branch::first();
        $companyId = $this->branch->company_id;

        $this->lottery = Lottery::create([
            'company_id' => $companyId,
            'name' => 'Lotería Nacional',
            'code' => 'LOTNAC',
            'country' => 'DO',
            'status' => 'ACTIVE',
        ]);

        // Quiniela: 1 número
        $this->quiniela = BetType::create([
            'company_id' => $companyId,
            'code' => 'QUINIELA',
            'name' => 'Quiniela',
            'digits_count' => 2,
            'min_numbers' => 1,
            'max_numbers' => 1,
            'numbers_count' => 1,
            'requires_position' => false,
            'is_cross_lottery' => false,
            'status' => 'ACTIVE',
        ]);

        foreach (['FIRST' => 72, 'SECOND' => 12, 'THIRD' => 4] as $pos => $mult) {
            PayoutRule::create([
                'company_id' => $companyId, 'bet_type_id' => $this->quiniela->id,
                'position' => $pos, 'match_type' => 'DIRECT',
                'payout_multiplier' => $mult, 'effective_from' => now()->subDay(),
                'status' => 'ACTIVE', 'created_by' => auth()->id(),
            ]);
        }
        PayoutRule::create([
            'company_id' => $companyId, 'bet_type_id' => $this->quiniela->id,
            'match_type' => 'DIRECT', 'payout_multiplier' => 72,
            'effective_from' => now()->subDay(), 'status' => 'ACTIVE',
            'created_by' => auth()->id(),
        ]);

        // Pale: 2 números
        $this->pale = BetType::create([
            'company_id' => $companyId,
            'code' => 'PALE',
            'name' => 'Pale',
            'digits_count' => 2,
            'min_numbers' => 1, 'max_numbers' => 1,
            'numbers_count' => 2,
            'requires_position' => false,
            'is_cross_lottery' => false,
            'status' => 'ACTIVE',
        ]);

        // Pale: FIRST = 1ª-2ª y 1ª-3ª ($1500), SECOND = 2ª-3ª ($100)
        foreach (['FIRST' => 1500, 'SECOND' => 100] as $pos => $mult) {
            PayoutRule::create([
                'company_id' => $companyId, 'bet_type_id' => $this->pale->id,
                'position' => $pos, 'match_type' => 'DIRECT',
                'payout_multiplier' => $mult, 'effective_from' => now()->subDay(),
                'status' => 'ACTIVE', 'created_by' => auth()->id(),
            ]);
        }
        PayoutRule::create([
            'company_id' => $companyId, 'bet_type_id' => $this->pale->id,
            'match_type' => 'DIRECT', 'payout_multiplier' => 1500,
            'effective_from' => now()->subDay(), 'status' => 'ACTIVE',
            'created_by' => auth()->id(),
        ]);

        // Tripleta: 3 números
        $this->tripleta = BetType::create([
            'company_id' => $companyId,
            'code' => 'TRIPLETA',
            'name' => 'Tripleta',
            'digits_count' => 2,
            'min_numbers' => 1, 'max_numbers' => 1,
            'numbers_count' => 3,
            'requires_position' => true,
            'is_cross_lottery' => false,
            'status' => 'ACTIVE',
        ]);

        // Tripleta: EXACT = 3 aciertos ($20000), ANY = 2 aciertos pata ($100)
        foreach (['EXACT' => 20000, 'ANY' => 100] as $pos => $mult) {
            PayoutRule::create([
                'company_id' => $companyId, 'bet_type_id' => $this->tripleta->id,
                'position' => $pos, 'match_type' => 'DIRECT',
                'payout_multiplier' => $mult, 'effective_from' => now()->subDay(),
                'status' => 'ACTIVE', 'created_by' => auth()->id(),
            ]);
        }
        PayoutRule::create([
            'company_id' => $companyId, 'bet_type_id' => $this->tripleta->id,
            'match_type' => 'DIRECT', 'payout_multiplier' => 20000,
            'effective_from' => now()->subDay(), 'status' => 'ACTIVE',
            'created_by' => auth()->id(),
        ]);

        $this->draw = Draw::create([
            'company_id' => $companyId,
            'lottery_id' => $this->lottery->id,
            'name' => 'Sorteo 12:30 PM',
            'draw_date' => now()->toDateString(),
            'scheduled_time' => '12:30',
            'close_time' => '23:59',
            'status' => 'OPEN',
        ]);
    }

    private function createValidLicenseState(): LicenseState
    {
        return LicenseState::create([
            'project_code' => 'BSLOTTERY', 'license_key' => 'LIC-TEST',
            'device_fingerprint' => 'test', 'device_name' => 'Servidor',
            'device_type' => 'web', 'client_location_code' => 'principal',
            'domain' => 'localhost', 'app_version' => '1.0.0',
            'status' => 'active', 'reason_code' => 'LICENSE_ACTIVE',
            'expires_at' => now()->addMonth(), 'last_validation_success' => true,
            'last_validation_at' => now(), 'last_server_time' => now(),
            'last_seen_system_time' => now(), 'offline_grace_expires_at' => now()->addHours(72),
            'features' => ['offline_mode' => true],
            'limits' => ['offline_grace_hours' => 72, 'max_users' => 5],
            'metadata' => ['company_name' => 'Test', 'trade_name' => 'Test', 'branch_name' => 'Test'],
            'client' => ['code' => 'c', 'name' => 'C'],
            'location' => ['code' => 'TEST01', 'name' => 'Test'],
            'is_active' => true,
        ]);
    }

    private function setupResult(string $first, string $second, string $third): void
    {
        $this->post(route('admin.draws.close', $this->draw))->assertRedirect();
        $this->post(route('admin.results.store'), [
            'lottery_id' => $this->lottery->id, 'draw_id' => $this->draw->id,
            'first_number' => $first, 'second_number' => $second, 'third_number' => $third,
        ])->assertRedirect();
        $result = \App\Models\Result::where('draw_id', $this->draw->id)->first();
        $this->confirmResultAsSecondAdmin($result);
    }

    private function confirmResultAsSecondAdmin(\App\Models\Result $result): void
    {
        $admin = auth()->user();
        $confirmer = User::firstOrCreate(
            ['email' => 'confirmador-ticketpro@example.com'],
            [
                'company_id' => $this->branch->company_id,
                'branch_id' => $this->branch->id,
                'name' => 'Admin Confirmador',
                'username' => 'confirmador-ticketpro',
                'password' => Hash::make('Password1234'),
                'role_id' => $admin->role_id,
                'status' => 'ACTIVE',
            ],
        );

        $this->actingAs($confirmer)
            ->post(route('admin.results.confirm', $result))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($admin);
    }

    private function openCashAndSell(array $plays): void
    {
        $this->post(route('admin.cash.open.store'), [
            'branch_id' => $this->branch->id, 'opening_amount' => '5000.00',
        ])->assertRedirect();
        $this->post(route('admin.tickets.store'), [
            'branch_id' => $this->branch->id, 'draw_id' => $this->draw->id, 'plays' => $plays,
        ])->assertSessionHasNoErrors();
    }

    private function calculate(): void
    {
        $this->post(route('admin.results.calculate', $this->draw))->assertRedirect();
    }

    // ==================== QUINIELA ====================

    public function test_quiniela_first_position_72x(): void
    {
        $this->openCashAndSell([['bet_type_id' => $this->quiniela->id, 'number_value' => '64', 'amount' => '100']]);
        $this->setupResult('64', '31', '01');
        $this->calculate();

        $w = \App\Models\WinnerTicket::first();
        $this->assertNotNull($w);
        $this->assertStringContainsString('FIRST', $w->matched_position);
        $this->assertEquals(7200.00, (float) $w->prize_amount);
    }

    public function test_quiniela_second_position_12x(): void
    {
        $this->openCashAndSell([['bet_type_id' => $this->quiniela->id, 'number_value' => '31', 'amount' => '50']]);
        $this->setupResult('64', '31', '01');
        $this->calculate();

        $w = \App\Models\WinnerTicket::first();
        $this->assertNotNull($w);
        $this->assertStringContainsString('SECOND', $w->matched_position);
        $this->assertEquals(600.00, (float) $w->prize_amount);
    }

    public function test_quiniela_third_position_4x(): void
    {
        $this->openCashAndSell([['bet_type_id' => $this->quiniela->id, 'number_value' => '01', 'amount' => '200']]);
        $this->setupResult('64', '31', '01');
        $this->calculate();

        $w = \App\Models\WinnerTicket::first();
        $this->assertNotNull($w);
        $this->assertStringContainsString('THIRD', $w->matched_position);
        $this->assertEquals(800.00, (float) $w->prize_amount);
    }

    public function test_quiniela_non_matching_loses(): void
    {
        $this->openCashAndSell([['bet_type_id' => $this->quiniela->id, 'number_value' => '99', 'amount' => '100']]);
        $this->setupResult('64', '31', '01');
        $this->calculate();

        $this->assertEquals(0, \App\Models\WinnerTicket::count());
        $this->assertDatabaseHas('ticket_details', ['number_value' => '99', 'status' => 'LOSER']);
    }

    // ==================== PALE ====================

    public function test_pale_first_second_1500x(): void
    {
        $this->openCashAndSell([['bet_type_id' => $this->pale->id, 'number_value' => '64-31', 'amount' => '10']]);
        $this->setupResult('64', '31', '01');
        $this->calculate();

        $w = \App\Models\WinnerTicket::first();
        $this->assertNotNull($w);
        $this->assertStringContainsString('pale_primera_segunda', $w->matched_position);
        $this->assertEquals(15000.00, (float) $w->prize_amount);
    }

    public function test_pale_second_third_100x(): void
    {
        $this->openCashAndSell([['bet_type_id' => $this->pale->id, 'number_value' => '31-01', 'amount' => '20']]);
        $this->setupResult('64', '31', '01');
        $this->calculate();

        $w = \App\Models\WinnerTicket::first();
        $this->assertNotNull($w);
        $this->assertStringContainsString('pale_segunda_tercera', $w->matched_position);
        $this->assertEquals(2000.00, (float) $w->prize_amount);
    }

    public function test_pale_first_third_1500x(): void
    {
        // Pale 1ª-3ª paga igual que 1ª-2ª ($1500), solo si 2ª != 3ª
        $this->openCashAndSell([['bet_type_id' => $this->pale->id, 'number_value' => '64-01', 'amount' => '10']]);
        $this->setupResult('64', '31', '01');
        $this->calculate();

        $w = \App\Models\WinnerTicket::first();
        $this->assertNotNull($w, 'Pale 64-01 debe ganar con 1ª-3ª');
        $this->assertStringContainsString('pale_primera_tercera', $w->matched_position);
        $this->assertEquals(15000.00, (float) $w->prize_amount);
    }

    public function test_pale_first_third_not_paid_when_second_equals_third(): void
    {
        // Cuando 2ª == 3ª, 1ª-3ª NO paga (para evitar duplicado con 1ª-2ª)
        // Resultado: 64-31-31
        $this->openCashAndSell([['bet_type_id' => $this->pale->id, 'number_value' => '64-31', 'amount' => '10']]);
        $this->setupResult('64', '31', '31');
        $this->calculate();

        // Solo pale_primera_segunda, NO pale_primera_tercera
        $w = \App\Models\WinnerTicket::first();
        $this->assertNotNull($w);
        $this->assertStringContainsString('pale_primera_segunda', $w->matched_position);
        $this->assertStringNotContainsString('pale_primera_tercera', $w->matched_position);
        $this->assertEquals(15000.00, (float) $w->prize_amount);
    }

    public function test_pale_duplicate_numbers_00_00(): void
    {
        // Pale con números duplicados: "00-00" contra resultado "00-31-01"
        // Necesita 2 ocurrencias de "00" en los números jugados
        $this->openCashAndSell([['bet_type_id' => $this->pale->id, 'number_value' => '00-00', 'amount' => '10']]);
        $this->setupResult('00', '31', '01');
        $this->calculate();

        // 00-00 no contiene "31", así que solo gana 1ª-2ª si 2ª fuera 00
        // 00-00 vs [00, 31, 01]: hasPairMultiset(00, 31) = false (solo hay 0s)
        // El par 00-00 necesita 2x "00" en nums para hasPairMultiset(00,00) = true (a===b)
        // Pero también necesita que 2ª sea 00 para pale. 2ª=31, no coincide.
        // Así que solo verificamos que no explote con números duplicados.
        // Para ganar: necesita que un par coincida. 00-31 no, 31-01 no, 00-01 no.
        $count = \App\Models\WinnerTicket::count();
        // Con 00-00 vs [00, 31, 01]: 1ª-2ª=00-31 no (31 != 00), 2ª-3ª=31-01 no, 1ª-3ª=00-01 no
        // Solo ganaría si el resultado tuviera 00 en 2 posiciones
        $this->assertEquals(0, $count, 'Pale 00-00 no debería ganar contra 00-31-01');
    }

    public function test_pale_duplicate_numbers_win_when_both_match(): void
    {
        // Pale "00-00" contra "00-00-01": 1ª-2ª = 00-00 (pale)
        $this->openCashAndSell([['bet_type_id' => $this->pale->id, 'number_value' => '00-00', 'amount' => '5']]);
        $this->setupResult('00', '00', '01');
        $this->calculate();

        $w = \App\Models\WinnerTicket::first();
        $this->assertNotNull($w, 'Pale 00-00 debe ganar con 1ª-2ª = 00-00');
        $this->assertStringContainsString('pale_primera_segunda', $w->matched_position);
        $this->assertEquals(7500.00, (float) $w->prize_amount);
    }

    public function test_pale_non_matching_loses(): void
    {
        $this->openCashAndSell([['bet_type_id' => $this->pale->id, 'number_value' => '99-88', 'amount' => '10']]);
        $this->setupResult('64', '31', '01');
        $this->calculate();

        $this->assertEquals(0, \App\Models\WinnerTicket::count());
    }

    // ==================== TRIPLETA ====================

    public function test_tripleta_3_matches_20000x(): void
    {
        // "64-31-01" vs [64, 31, 01] → 3 aciertos (multiset)
        $this->openCashAndSell([['bet_type_id' => $this->tripleta->id, 'number_value' => '64-31-01', 'amount' => '5']]);
        $this->setupResult('64', '31', '01');
        $this->calculate();

        $w = \App\Models\WinnerTicket::first();
        $this->assertNotNull($w);
        $this->assertEquals('tripleta', $w->matched_position);
        $this->assertEquals(100000.00, (float) $w->prize_amount);
    }

    public function test_tripleta_any_order_3_matches(): void
    {
        // "31-64-01" vs [64, 31, 01] → 3 aciertos (orden diferente, pero 3 coinciden)
        $this->openCashAndSell([['bet_type_id' => $this->tripleta->id, 'number_value' => '31-64-01', 'amount' => '5']]);
        $this->setupResult('64', '31', '01');
        $this->calculate();

        $w = \App\Models\WinnerTicket::first();
        $this->assertNotNull($w, 'Tripleta 31-64-01 debe ganar con 3 aciertos (multiset)');
        $this->assertEquals('tripleta', $w->matched_position);
        $this->assertEquals(100000.00, (float) $w->prize_amount);
    }

    public function test_tripleta_2_matches_pata_100x(): void
    {
        // "64-31-99" vs [64, 31, 01] → 2 aciertos (64 y 31) → pata
        $this->openCashAndSell([['bet_type_id' => $this->tripleta->id, 'number_value' => '64-31-99', 'amount' => '10']]);
        $this->setupResult('64', '31', '01');
        $this->calculate();

        $w = \App\Models\WinnerTicket::first();
        $this->assertNotNull($w, 'Tripleta 64-31-99 debe ganar con 2 aciertos (pata)');
        $this->assertEquals('tripletas_pata', $w->matched_position);
        $this->assertEquals(1000.00, (float) $w->prize_amount);
        $this->assertEquals(100.00, (float) $w->payout_multiplier);
    }

    public function test_tripleta_duplicate_winner_numbers(): void
    {
        // "64-64-31" vs [64, 64, 01] → count de 64: 2 en nums, 2 en winners = 2 matches → pata
        // countMatchesMultiset: freq={64:2, 01:1}. nums=[64,64,31]
        //   64: freq[64]=2>0, freq[64]=1, c=1
        //   64: freq[64]=1>0, freq[64]=0, c=2
        //   31: freq[31]=0, no match
        //   c=2 → pata
        $this->openCashAndSell([['bet_type_id' => $this->tripleta->id, 'number_value' => '64-64-31', 'amount' => '10']]);
        $this->setupResult('64', '64', '01');
        $this->calculate();

        $w = \App\Models\WinnerTicket::first();
        $this->assertNotNull($w, 'Debe ganar pata con 2 aciertos');
        $this->assertEquals('tripletas_pata', $w->matched_position);
    }

    public function test_tripleta_0_or_1_match_loses(): void
    {
        $this->openCashAndSell([['bet_type_id' => $this->tripleta->id, 'number_value' => '99-88-77', 'amount' => '5']]);
        $this->setupResult('64', '31', '01');
        $this->calculate();

        $this->assertEquals(0, \App\Models\WinnerTicket::count());
    }

    // ==================== INTEGRACIÓN ====================

    public function test_mixed_ticket_winners_and_losers(): void
    {
        $this->openCashAndSell([
            ['bet_type_id' => $this->quiniela->id, 'number_value' => '64', 'amount' => '100'],
            ['bet_type_id' => $this->quiniela->id, 'number_value' => '31', 'amount' => '50'],
            ['bet_type_id' => $this->quiniela->id, 'number_value' => '99', 'amount' => '200'],
        ]);

        $this->setupResult('64', '31', '01');
        $this->calculate();

        $this->assertEquals(2, \App\Models\WinnerTicket::count());

        $this->assertDatabaseHas('winner_tickets', ['number_value' => '64', 'prize_amount' => 7200.00]);
        $this->assertDatabaseHas('winner_tickets', ['number_value' => '31', 'prize_amount' => 600.00]);
        $this->assertDatabaseHas('ticket_details', ['number_value' => '99', 'status' => 'LOSER']);
    }

    public function test_full_ticketpro_spec_example(): void
    {
        // Especificación TicketPro: 64-31-01
        $this->openCashAndSell([
            ['bet_type_id' => $this->quiniela->id, 'number_value' => '64', 'amount' => '1'],
            ['bet_type_id' => $this->quiniela->id, 'number_value' => '31', 'amount' => '1'],
            ['bet_type_id' => $this->quiniela->id, 'number_value' => '01', 'amount' => '1'],
            ['bet_type_id' => $this->pale->id, 'number_value' => '64-31', 'amount' => '1'],
            ['bet_type_id' => $this->pale->id, 'number_value' => '31-01', 'amount' => '1'],
            ['bet_type_id' => $this->tripleta->id, 'number_value' => '64-31-01', 'amount' => '1'],
        ]);

        $this->setupResult('64', '31', '01');
        $this->calculate();

        $winners = \App\Models\WinnerTicket::all();
        $this->assertCount(6, $winners);

        // Verificar cada jugada según especificación TicketPro
        $expected = [
            ['number' => '64', 'prize' => 72.00],
            ['number' => '31', 'prize' => 12.00],
            ['number' => '01', 'prize' => 4.00],
            ['number' => '64-31', 'prize' => 1500.00],
            ['number' => '31-01', 'prize' => 100.00],
            ['number' => '64-31-01', 'prize' => 20000.00],
        ];

        foreach ($expected as $exp) {
            $found = $winners->first(fn ($w) => $w->number_value === $exp['number']);
            $this->assertNotNull($found, "No se encontró ganador: {$exp['number']}");
            if ($found) {
                $this->assertEquals($exp['prize'], (float) $found->prize_amount,
                    "Premio incorrecto para {$exp['number']}");
            }
        }

        // Total: 72+12+4+1500+100+20000 = 21688
        $this->assertDatabaseHas('payment_authorizations', [
            'draw_id' => $this->draw->id,
            'total_winners' => 6,
            'total_prize_amount' => 21688.00,
        ]);
    }
}
