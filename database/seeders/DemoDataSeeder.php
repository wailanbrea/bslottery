<?php

namespace Database\Seeders;

use App\Models\BetType;
use App\Models\Draw;
use App\Models\Lottery;
use App\Models\PayoutRule;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = \App\Models\Company::first()?->id;
        if (! $companyId) {
            return;
        }

        $user = User::where('company_id', $companyId)->first();
        if (! $user) {
            return;
        }

        // Lotería
        $lottery = Lottery::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'LOTNAC'],
            ['name' => 'Lotería Nacional', 'country' => 'DO', 'status' => 'ACTIVE']
        );

        // Tipos de jugada con numbers_count
        $quiniela = BetType::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'QUINIELA'],
            [
                'name' => 'Quiniela',
                'digits_count' => 2,
                'min_numbers' => 1, 'max_numbers' => 1,
                'numbers_count' => 1,
                'requires_position' => false,
                'is_cross_lottery' => false,
                'status' => 'ACTIVE',
            ]
        );

        $pale = BetType::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'PALE'],
            [
                'name' => 'Pale',
                'digits_count' => 2,
                'min_numbers' => 1, 'max_numbers' => 1,
                'numbers_count' => 2,
                'requires_position' => false,
                'is_cross_lottery' => false,
                'status' => 'ACTIVE',
            ]
        );

        $tripleta = BetType::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'TRIPLETA'],
            [
                'name' => 'Tripleta',
                'digits_count' => 2,
                'min_numbers' => 1, 'max_numbers' => 1,
                'numbers_count' => 3,
                'requires_position' => true,
                'is_cross_lottery' => false,
                'status' => 'ACTIVE',
            ]
        );

        $superPale = BetType::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'SUPER_PALE'],
            [
                'name' => 'Super Pale',
                'digits_count' => 2,
                'min_numbers' => 1, 'max_numbers' => 1,
                'numbers_count' => 2,
                'requires_position' => false,
                'is_cross_lottery' => true,
                'status' => 'ACTIVE',
            ]
        );

        // Reglas de pago Quiniela: 1ª=72, 2ª=12, 3ª=4
        $quinielaPayouts = ['FIRST' => 72, 'SECOND' => 12, 'THIRD' => 4];
        foreach ($quinielaPayouts as $pos => $mult) {
            PayoutRule::firstOrCreate(
                ['company_id' => $companyId, 'bet_type_id' => $quiniela->id, 'position' => $pos],
                ['match_type' => 'DIRECT', 'payout_multiplier' => $mult, 'effective_from' => now()->subYear(), 'status' => 'ACTIVE', 'created_by' => $user->id]
            );
        }

        // Reglas de pago Pale: 1ª-2ª/1ª-3ª=1500, 2ª-3ª=100
        $palePayouts = ['FIRST' => 1500, 'SECOND' => 100];
        foreach ($palePayouts as $pos => $mult) {
            PayoutRule::firstOrCreate(
                ['company_id' => $companyId, 'bet_type_id' => $pale->id, 'position' => $pos],
                ['match_type' => 'DIRECT', 'payout_multiplier' => $mult, 'effective_from' => now()->subYear(), 'status' => 'ACTIVE', 'created_by' => $user->id]
            );
        }

        // Reglas de pago Tripleta: exacta=20000, pata=100
        $tripletaPayouts = ['EXACT' => 20000, 'ANY' => 100];
        foreach ($tripletaPayouts as $pos => $mult) {
            PayoutRule::firstOrCreate(
                ['company_id' => $companyId, 'bet_type_id' => $tripleta->id, 'position' => $pos],
                ['match_type' => 'DIRECT', 'payout_multiplier' => $mult, 'effective_from' => now()->subYear(), 'status' => 'ACTIVE', 'created_by' => $user->id]
            );
        }

        PayoutRule::firstOrCreate(
            ['company_id' => $companyId, 'bet_type_id' => $superPale->id, 'position' => 'FIRST'],
            ['match_type' => 'DIRECT', 'payout_multiplier' => 1500, 'effective_from' => now()->subYear(), 'status' => 'ACTIVE', 'created_by' => $user->id]
        );

        // Sorteo de hoy (12:30 PM) si no existe
        Draw::firstOrCreate(
            ['company_id' => $companyId, 'lottery_id' => $lottery->id, 'draw_date' => now()->toDateString(), 'scheduled_time' => '12:30'],
            ['name' => 'Sorteo 12:30 PM', 'open_time' => '08:00', 'close_time' => '23:59', 'status' => 'OPEN']
        );

        // Sorteo de la tarde
        Draw::firstOrCreate(
            ['company_id' => $companyId, 'lottery_id' => $lottery->id, 'draw_date' => now()->toDateString(), 'scheduled_time' => '18:00'],
            ['name' => 'Sorteo 6:00 PM', 'open_time' => '08:00', 'close_time' => '23:59', 'status' => 'OPEN']
        );

        echo " Datos demo creados:\n";
        echo "  Lotería: {$lottery->name}\n";
        echo "  Quiniela: 1ª=72x, 2ª=12x, 3ª=4x\n";
        echo "  Pale: 1ª-2ª/1ª-3ª=1500x, 2ª-3ª=100x\n";
        echo "  Tripleta: exacta=20000x, pata=100x\n";
        echo "  Sorteos abiertos: 2\n";
    }
}
