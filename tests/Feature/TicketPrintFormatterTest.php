<?php

namespace Tests\Feature;

use App\Models\BetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Draw;
use App\Models\Lottery;
use App\Models\Ticket;
use App\Models\TicketDetail;
use App\Models\User;
use App\Services\Printing\TicketPrintFormatterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TicketPrintFormatterTest extends TestCase
{
    use RefreshDatabase;

    private TicketPrintFormatterService $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = app(TicketPrintFormatterService::class);
    }

    public function test_58mm_ticket_contains_core_data_grouped_plays_and_validation(): void
    {
        $ticket = $this->createTicketFixture();

        $content = $this->formatter->format($ticket, '58MM');

        $this->assertStringContainsString('BSLottery Demo', $content);
        $this->assertStringContainsString('Ticket: TEST-0001', $content);
        $this->assertStringContainsString('LOTERIA: Loteka', $content);
        $this->assertStringContainsString('Sorteo: Noche', $content);
        $this->assertStringContainsString('Q', $content);
        $this->assertStringContainsString('P', $content);
        $this->assertStringContainsString('T', $content);
        $this->assertStringContainsString('SP', $content);
        $this->assertStringContainsString('TOTAL', $content);
        $this->assertStringContainsString('VAL:', $content);
        // El marcador [[QR:<url>]] lo reemplaza el print agent / BluetoothPrinterManager
        // por bytes ESC/POS de QR real. La URL es la ruta publica de consulta del ticket.
        $this->assertStringContainsString('[[QR:', $content);
        $this->assertStringContainsString('/t/'.$ticket->uuid, $content);
    }

    public function test_88mm_ticket_contains_table_amounts_prizes_and_totals(): void
    {
        $ticket = $this->createTicketFixture();

        $content = $this->formatter->format($ticket, '88MM');

        $this->assertStringContainsString('Loteria    Sorteo   Tipo', $content);
        $this->assertStringContainsString('Loteka', $content);
        $this->assertStringContainsString('RD$35', $content);
        $this->assertStringContainsString('RD$2,520', $content);
        $this->assertStringContainsString('Total Apostado:', $content);
        $this->assertStringContainsString('Premio Posible Total:', $content);
        $this->assertStringContainsString('RD$595.00', $content);
        $this->assertStringContainsString('RD$378,420.00', $content);
    }

    public function test_formatter_uses_ticket_data_and_saved_possible_prize_without_hardcoded_values(): void
    {
        $ticket = $this->createTicketFixture(companyName: 'Comercial Real SRL');

        $content = $this->formatter->format($ticket, '88MM');

        $this->assertStringContainsString('Comercial Real SRL', $content);
        $this->assertStringContainsString('RD$123,456.78', $content);
        $this->assertStringNotContainsString('Banca La Suerte', $content);
        $this->assertStringNotContainsString('Maria', $content);
    }

    public function test_reprint_includes_reprint_header(): void
    {
        $ticket = $this->createTicketFixture();

        $content = $this->formatter->format($ticket, '58MM', true);

        $this->assertStringContainsString('REIMPRESION', $content);
    }

    public function test_cancelled_ticket_includes_cancelled_header_and_reason(): void
    {
        $ticket = $this->createTicketFixture();
        $ticket->update([
            'status' => 'CANCELLED',
            'cancel_reason' => 'Error del cajero',
        ]);

        $content = $this->formatter->format($ticket, '88MM');

        $this->assertStringContainsString('ANULADO', $content);
        $this->assertStringContainsString('Motivo: Error del cajero', $content);
    }

    private function createTicketFixture(string $companyName = 'BSLottery Demo'): Ticket
    {
        $company = Company::create([
            'name' => $companyName,
            'legal_name' => $companyName,
            'rnc' => '123-45678-9',
            'phone' => '809-555-0101',
            'address' => 'Av. Duarte 123',
            'status' => 'ACTIVE',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'code' => 'TEST',
            'name' => 'Sucursal Central',
            'phone' => '809-555-0202',
            'address' => 'Los Mina',
            'status' => 'ACTIVE',
        ]);

        $user = User::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Cajero Demo',
            'username' => 'cajero',
            'email' => 'cajero@example.com',
            'password' => Hash::make('Password1234'),
            'status' => 'ACTIVE',
        ]);

        $lottery = Lottery::create([
            'company_id' => $company->id,
            'name' => 'Loteka',
            'code' => 'Loteka',
            'country' => 'DO',
            'status' => 'ACTIVE',
        ]);

        $draw = Draw::create([
            'company_id' => $company->id,
            'lottery_id' => $lottery->id,
            'name' => 'Noche',
            'draw_date' => now()->toDateString(),
            'scheduled_time' => '20:00',
            'close_time' => '19:55',
            'status' => 'OPEN',
        ]);

        $betTypes = collect([
            'QUINIELA' => ['Quiniela', 1],
            'PALE' => ['Pale', 2],
            'TRIPLETA' => ['Tripleta', 3],
            'SUPER_PALE' => ['Super Pale', 2],
        ])->map(fn (array $data, string $code): BetType => BetType::create([
            'company_id' => $company->id,
            'code' => $code,
            'name' => $data[0],
            'digits_count' => 2,
            'min_numbers' => 1,
            'max_numbers' => 1,
            'numbers_count' => $data[1],
            'requires_position' => false,
            'status' => 'ACTIVE',
        ]));

        $ticket = Ticket::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'ticket_number' => 'TEST-0001',
            'sale_mode' => 'ONLINE',
            'total_amount' => '595.00',
            'total_possible_prize' => '378420.00',
            'status' => 'ACTIVE',
            'sold_at' => now(),
        ]);

        $details = [
            ['QUINIELA', '25', '35.00', '72.00', '2520.00'],
            ['PALE', '10-20', '50.00', '1500.00', '75000.00'],
            ['TRIPLETA', '12-13-14', '500.00', '246.91356', '123456.78'],
            ['SUPER_PALE', '12-34', '10.00', '17744.322', '177443.22'],
        ];

        foreach ($details as [$type, $number, $amount, $multiplier, $possiblePrize]) {
            TicketDetail::create([
                'ticket_id' => $ticket->id,
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'lottery_id' => $lottery->id,
                'draw_id' => $draw->id,
                'bet_type_id' => $betTypes[$type]->id,
                'number_value' => $number,
                'normalized_number' => $number,
                'amount' => $amount,
                'payout_multiplier' => $multiplier,
                'possible_prize' => $possiblePrize,
                'status' => 'ACTIVE',
            ]);
        }

        return $ticket->fresh();
    }
}
