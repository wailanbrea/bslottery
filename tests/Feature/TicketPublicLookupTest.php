<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Draw;
use App\Models\Lottery;
use App\Models\Ticket;
use App\Models\TicketDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TicketPublicLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_lookup_shows_ticket_information_without_auth(): void
    {
        $ticket = $this->seedTicket(status: 'ACTIVE');

        $response = $this->get(route('ticket.public', ['uuid' => $ticket->uuid]));

        $response->assertSuccessful();
        $response->assertSee($ticket->company->name);
        $response->assertSee($ticket->branch->name);
        $response->assertSee('Quiniela');
        $response->assertSee('25');
        $response->assertSee('Lotería Test');
    }

    public function test_invalid_uuid_returns_404_view(): void
    {
        $response = $this->get('/t/00000000-0000-0000-0000-000000000000');
        $response->assertStatus(404);
        $response->assertSee('Ticket no encontrado');
    }

    public function test_malformed_uuid_does_not_reach_controller(): void
    {
        // El where('uuid', 36 hex) en la ruta deberia rechazar antes del controller.
        $response = $this->get('/t/no-es-un-uuid');
        $response->assertStatus(404);
    }

    public function test_cancelled_ticket_shows_cancellation_notice(): void
    {
        $ticket = $this->seedTicket(status: 'CANCELLED', cancelReason: 'Cliente arrepentido');

        $response = $this->get(route('ticket.public', ['uuid' => $ticket->uuid]));

        $response->assertSuccessful();
        $response->assertSee('ANULADO');
        $response->assertSee('Cliente arrepentido');
    }

    public function test_route_is_publicly_accessible_with_uuid_regex_constraint(): void
    {
        $ticket = $this->seedTicket(status: 'ACTIVE');

        // Sin login (no $this->actingAs(...))
        $this->assertSame(0, auth()->id() ?? 0);
        $response = $this->get(route('ticket.public', ['uuid' => $ticket->uuid]));
        $response->assertSuccessful();
    }

    private function seedTicket(string $status, ?string $cancelReason = null): Ticket
    {
        $company = Company::create([
            'name' => 'Banca Pública Test',
            'legal_name' => 'Banca Pública Test SRL',
            'status' => 'ACTIVE',
            'timezone' => 'America/Santo_Domingo',
            'currency' => 'DOP',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'code' => 'PUB01',
            'name' => 'Sucursal Pública',
            'address' => 'Calle Lookup #1',
            'phone' => '809-000-0001',
            'status' => 'ACTIVE',
        ]);

        $user = User::create([
            'name' => 'Cajero Test',
            'username' => 'cajero_public_'.uniqid(),
            'email' => 'cajero_'.uniqid().'@test.local',
            'password' => bcrypt('Password1234'),
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'ACTIVE',
        ]);

        $lottery = Lottery::create([
            'company_id' => $company->id,
            'code' => 'TEST-LOT',
            'name' => 'Lotería Test',
            'country' => 'DO',
            'status' => 'ACTIVE',
        ]);

        $draw = Draw::create([
            'company_id' => $company->id,
            'lottery_id' => $lottery->id,
            'name' => 'Sorteo Test 9PM',
            'draw_date' => Carbon::today(),
            'open_time' => '08:00',
            'scheduled_time' => '21:00',
            'close_time' => '21:00',
            'status' => 'OPEN',
        ]);

        $betType = BetType::create([
            'company_id' => $company->id,
            'name' => 'Quiniela',
            'code' => 'QUINIELA',
            'digits_count' => 2,
            'min_numbers' => 1,
            'max_numbers' => 1,
            'requires_position' => false,
            'status' => 'ACTIVE',
        ]);

        $ticket = Ticket::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'ticket_number' => 'TICK-PUB-001',
            'sale_mode' => 'WEB',
            'total_amount' => '100.00',
            'total_possible_prize' => '7200.00',
            'status' => $status,
            'sold_at' => Carbon::now(),
            'cancelled_at' => $status === 'CANCELLED' ? Carbon::now() : null,
            'cancel_reason' => $cancelReason,
        ]);

        TicketDetail::create([
            'ticket_id' => $ticket->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'draw_id' => $draw->id,
            'lottery_id' => $lottery->id,
            'bet_type_id' => $betType->id,
            'number_value' => '25',
            'normalized_number' => '25',
            'amount' => '100.00',
            'possible_prize' => '7200.00',
            'payout_multiplier' => '72.00',
            'status' => 'ACTIVE',
        ]);

        return $ticket->fresh();
    }
}
