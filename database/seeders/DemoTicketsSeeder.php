<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Draw;
use App\Models\Lottery;
use App\Models\Result;
use App\Models\Ticket;
use App\Models\TicketDetail;
use App\Models\User;
use App\Models\WinnerTicket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Seeder de tickets demo con varios estados y dias.
 *
 * Idempotente: prefija `ticket_number` con `DEMO-{fecha}-{n}`. Si ya existe
 * lo salta. Para regenerar limpio: `Ticket::where('ticket_number','like','DEMO-%')->delete()`
 * (cascada borra TicketDetail + WinnerTicket).
 *
 * Genera escenarios cubriendo los estados visibles en /admin/tickets,
 * /admin/prizes/pending, /admin/reports/*:
 *   - HOY:        2 ACTIVE (sorteo aun no resuelto).
 *   - AYER:       1 WINNER (PENDING_RELEASE), 1 WINNER (RELEASED listo pagar),
 *                 1 PAID, 2 LOSER, 1 CANCELLED + Result CONFIRMED del draw.
 *   - HACE 2 DIAS: 1 PARTIALLY_PAID, 1 PAID, 1 LOSER + Result CONFIRMED.
 *   - HACE 5 DIAS: 2 PAID, 1 LOSER, 1 CANCELLED + Result CONFIRMED.
 *   - HACE 7 DIAS: 1 PAID, 2 LOSER + Result CONFIRMED.
 */
class DemoTicketsSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->orderBy('id')->first();
        if (! $company) {
            $this->command?->warn('DemoTicketsSeeder: sin Company, skip.');

            return;
        }

        $branch = Branch::query()->where('company_id', $company->id)->orderBy('id')->first();
        $user = User::query()->where('company_id', $company->id)->orderBy('id')->first();
        if (! $branch || ! $user) {
            $this->command?->warn('DemoTicketsSeeder: falta Branch o User, skip.');

            return;
        }

        // Loteria principal y bet types
        $lottery = Lottery::query()
            ->where('company_id', $company->id)
            ->where('code', 'LOTNAC')
            ->first();
        if (! $lottery) {
            $this->command?->warn('DemoTicketsSeeder: Lotería Nacional (LOTNAC) no encontrada. Corre DemoDataSeeder primero.');

            return;
        }

        $quiniela = BetType::query()->where('company_id', $company->id)->where('code', 'QUINIELA')->first();
        $pale = BetType::query()->where('company_id', $company->id)->where('code', 'PALE')->first();
        $tripleta = BetType::query()->where('company_id', $company->id)->where('code', 'TRIPLETA')->first();

        if (! $quiniela || ! $pale || ! $tripleta) {
            $this->command?->warn('DemoTicketsSeeder: faltan BetTypes (Q/PALE/TRIPLETA). Corre DemoDataSeeder primero.');

            return;
        }

        $ctx = [
            'company' => $company,
            'branch' => $branch,
            'user' => $user,
            'lottery' => $lottery,
            'quiniela' => $quiniela,
            'pale' => $pale,
            'tripleta' => $tripleta,
        ];

        // Escenarios por dia. Cada uno crea o reusa el draw, su Result, y
        // los tickets demo. winningNumbers = [primero, segundo, tercero].
        $this->buildDay($ctx, daysAgo: 0, winningNumbers: [null, null, null], scenarios: [
            ['status' => 'ACTIVE', 'type' => 'Q', 'number' => '42', 'amount' => '50.00'],
            ['status' => 'ACTIVE', 'type' => 'PALE', 'number' => '12-34', 'amount' => '20.00'],
        ]);

        $this->buildDay($ctx, daysAgo: 1, winningNumbers: ['34', '88', '12'], scenarios: [
            ['status' => 'WINNER', 'type' => 'Q', 'number' => '34', 'amount' => '100.00', 'winnerStatus' => 'PENDING_RELEASE'],
            ['status' => 'WINNER', 'type' => 'Q', 'number' => '88', 'amount' => '50.00', 'winnerStatus' => 'RELEASED'],
            ['status' => 'PAID', 'type' => 'Q', 'number' => '12', 'amount' => '30.00', 'winnerStatus' => 'PAID'],
            ['status' => 'LOSER', 'type' => 'Q', 'number' => '07', 'amount' => '25.00'],
            ['status' => 'LOSER', 'type' => 'PALE', 'number' => '21-77', 'amount' => '10.00'],
            ['status' => 'CANCELLED', 'type' => 'Q', 'number' => '55', 'amount' => '40.00', 'cancelReason' => 'Cliente cambio de idea'],
        ]);

        $this->buildDay($ctx, daysAgo: 2, winningNumbers: ['77', '21', '03'], scenarios: [
            ['status' => 'PARTIALLY_PAID', 'type' => 'Q', 'number' => '77', 'amount' => '60.00', 'winnerStatus' => 'PAID'],
            ['status' => 'PAID', 'type' => 'Q', 'number' => '21', 'amount' => '20.00', 'winnerStatus' => 'PAID'],
            ['status' => 'LOSER', 'type' => 'TRIPLETA', 'number' => '01-02-03', 'amount' => '15.00'],
        ]);

        $this->buildDay($ctx, daysAgo: 5, winningNumbers: ['25', '63', '47'], scenarios: [
            ['status' => 'PAID', 'type' => 'Q', 'number' => '25', 'amount' => '80.00', 'winnerStatus' => 'PAID'],
            ['status' => 'PAID', 'type' => 'Q', 'number' => '63', 'amount' => '50.00', 'winnerStatus' => 'PAID'],
            ['status' => 'LOSER', 'type' => 'Q', 'number' => '11', 'amount' => '30.00'],
            ['status' => 'CANCELLED', 'type' => 'PALE', 'number' => '15-30', 'amount' => '20.00', 'cancelReason' => 'Numero equivocado'],
        ]);

        $this->buildDay($ctx, daysAgo: 7, winningNumbers: ['99', '04', '50'], scenarios: [
            ['status' => 'PAID', 'type' => 'Q', 'number' => '99', 'amount' => '40.00', 'winnerStatus' => 'PAID'],
            ['status' => 'LOSER', 'type' => 'Q', 'number' => '13', 'amount' => '25.00'],
            ['status' => 'LOSER', 'type' => 'PALE', 'number' => '40-50', 'amount' => '15.00'],
        ]);

        $this->command?->info('DemoTicketsSeeder: tickets demo creados/verificados (prefijo DEMO-).');
    }

    /**
     * @param  array{company: Company, branch: Branch, user: User, lottery: Lottery, quiniela: BetType, pale: BetType, tripleta: BetType}  $ctx
     * @param  array<int, ?string>  $winningNumbers  [first, second, third] o [null, null, null] si todavia no hay resultado
     * @param  array<int, array{status: string, type: string, number: string, amount: string, winnerStatus?: string, cancelReason?: string}>  $scenarios
     */
    private function buildDay(array $ctx, int $daysAgo, array $winningNumbers, array $scenarios): void
    {
        $date = Carbon::today()->subDays($daysAgo);
        $dateStr = $date->toDateString();
        $hasResult = $winningNumbers[0] !== null;

        // Reusa el draw nacional 9pm del dia, o lo crea
        $draw = Draw::query()
            ->where('company_id', $ctx['company']->id)
            ->where('lottery_id', $ctx['lottery']->id)
            ->whereDate('draw_date', $dateStr)
            ->where('scheduled_time', '21:00')
            ->first();

        if (! $draw) {
            $draw = Draw::create([
                'company_id' => $ctx['company']->id,
                'lottery_id' => $ctx['lottery']->id,
                'name' => 'Nacional 9:00 PM',
                'draw_date' => $dateStr,
                'open_time' => '08:00',
                'scheduled_time' => '21:00',
                'close_time' => '21:00',
                'status' => $hasResult ? 'FINALIZED' : 'OPEN',
                'closed_at' => $hasResult ? $date->copy()->setTime(21, 5) : null,
            ]);
        } elseif ($hasResult && $draw->status === 'OPEN') {
            $draw->update([
                'status' => 'FINALIZED',
                'closed_at' => $date->copy()->setTime(21, 5),
            ]);
        }

        // Resultado del draw si aplica
        if ($hasResult) {
            Result::firstOrCreate(
                [
                    'company_id' => $ctx['company']->id,
                    'draw_id' => $draw->id,
                ],
                [
                    'lottery_id' => $ctx['lottery']->id,
                    'first_number' => $winningNumbers[0],
                    'second_number' => $winningNumbers[1],
                    'third_number' => $winningNumbers[2],
                    'status' => 'CONFIRMED',
                    'registered_by' => $ctx['user']->id,
                    'registered_at' => $date->copy()->setTime(21, 10),
                    'confirmed_by' => $ctx['user']->id,
                    'confirmed_at' => $date->copy()->setTime(21, 15),
                ],
            );
        }

        foreach ($scenarios as $i => $scenario) {
            $this->buildTicket(
                ctx: $ctx,
                draw: $draw,
                date: $date,
                scenario: $scenario,
                seq: $i + 1,
            );
        }
    }

    /**
     * @param  array{company: Company, branch: Branch, user: User, lottery: Lottery, quiniela: BetType, pale: BetType, tripleta: BetType}  $ctx
     * @param  array{status: string, type: string, number: string, amount: string, winnerStatus?: string, cancelReason?: string}  $scenario
     */
    private function buildTicket(array $ctx, Draw $draw, Carbon $date, array $scenario, int $seq): void
    {
        $dateCode = $date->format('ymd');
        $ticketNumber = sprintf('DEMO-%s-%03d', $dateCode, $seq);

        if (Ticket::query()->where('ticket_number', $ticketNumber)->exists()) {
            return; // idempotente
        }

        // Resolver bet type y multiplicador segun tipo
        $betType = match ($scenario['type']) {
            'Q' => $ctx['quiniela'],
            'PALE' => $ctx['pale'],
            'TRIPLETA' => $ctx['tripleta'],
            default => $ctx['quiniela'],
        };

        $multiplier = match ($scenario['type']) {
            'Q' => 72.00,
            'PALE' => 1500.00,
            'TRIPLETA' => 20000.00,
            default => 72.00,
        };

        $amount = (float) $scenario['amount'];
        $possiblePrize = round($amount * $multiplier, 2);
        $soldAt = $date->copy()->setTime(rand(9, 18), rand(0, 59));

        $ticket = Ticket::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $ctx['company']->id,
            'branch_id' => $ctx['branch']->id,
            'user_id' => $ctx['user']->id,
            'ticket_number' => $ticketNumber,
            'sale_mode' => 'ONLINE',
            'total_amount' => number_format($amount, 2, '.', ''),
            'total_possible_prize' => number_format($possiblePrize, 2, '.', ''),
            'status' => $scenario['status'],
            'sold_at' => $soldAt,
            'cancelled_at' => $scenario['status'] === 'CANCELLED' ? $soldAt->copy()->addMinutes(5) : null,
            'cancelled_by' => $scenario['status'] === 'CANCELLED' ? $ctx['user']->id : null,
            'cancel_reason' => $scenario['cancelReason'] ?? null,
            'paid_at' => $scenario['status'] === 'PAID' ? $date->copy()->setTime(22, 0) : null,
        ]);

        $detail = TicketDetail::create([
            'ticket_id' => $ticket->id,
            'company_id' => $ctx['company']->id,
            'branch_id' => $ctx['branch']->id,
            'lottery_id' => $ctx['lottery']->id,
            'draw_id' => $draw->id,
            'bet_type_id' => $betType->id,
            'number_value' => $scenario['number'],
            'normalized_number' => str_replace('-', '', $scenario['number']),
            'amount' => number_format($amount, 2, '.', ''),
            'payout_multiplier' => number_format($multiplier, 2, '.', ''),
            'possible_prize' => number_format($possiblePrize, 2, '.', ''),
            'result_position' => $this->resolveResultPosition($scenario['status']),
            'status' => match ($scenario['status']) {
                'WINNER', 'PARTIALLY_PAID', 'PAID' => 'WINNER',
                'LOSER' => 'LOSER',
                'CANCELLED' => 'CANCELLED',
                default => 'ACTIVE',
            },
        ]);

        // WinnerTicket si aplica
        if (isset($scenario['winnerStatus'])) {
            WinnerTicket::create([
                'company_id' => $ctx['company']->id,
                'branch_id' => $ctx['branch']->id,
                'ticket_id' => $ticket->id,
                'ticket_detail_id' => $detail->id,
                'lottery_id' => $ctx['lottery']->id,
                'draw_id' => $draw->id,
                'bet_type_id' => $betType->id,
                'number_value' => $scenario['number'],
                'matched_position' => $this->resolveResultPosition($scenario['status']) ?? 'FIRST',
                'amount_played' => number_format($amount, 2, '.', ''),
                'payout_multiplier' => number_format($multiplier, 2, '.', ''),
                'prize_amount' => number_format($possiblePrize, 2, '.', ''),
                'status' => $scenario['winnerStatus'],
                'paid_at' => $scenario['winnerStatus'] === 'PAID' ? $date->copy()->setTime(22, 0) : null,
                'paid_by' => $scenario['winnerStatus'] === 'PAID' ? $ctx['user']->id : null,
            ]);
        }
    }

    private function resolveResultPosition(string $status): ?string
    {
        return match ($status) {
            'WINNER', 'PARTIALLY_PAID', 'PAID' => 'FIRST',
            default => null,
        };
    }
}
