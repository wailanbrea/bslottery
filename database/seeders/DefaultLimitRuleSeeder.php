<?php

namespace Database\Seeders;

use App\Models\BetType;
use App\Models\Company;
use App\Models\LimitRule;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultLimitRuleSeeder extends Seeder
{
    private const DEFAULT_LIMITS = [
        'QUINIELA' => [
            'name' => 'Quiniela',
            'numbers_count' => 1,
            'is_cross_lottery' => false,
            'max_amount_per_number' => '3000.00',
        ],
        'PALE' => [
            'name' => 'Pale',
            'numbers_count' => 2,
            'is_cross_lottery' => false,
            'max_amount_per_number' => '200.00',
        ],
        'TRIPLETA' => [
            'name' => 'Tripleta',
            'numbers_count' => 3,
            'is_cross_lottery' => false,
            'max_amount_per_number' => '50.00',
        ],
        'SUPER_PALE' => [
            'name' => 'Super Pale',
            'numbers_count' => 2,
            'is_cross_lottery' => true,
            'max_amount_per_number' => '100.00',
        ],
    ];

    public function run(): void
    {
        Company::query()->select('id')->chunkById(100, function ($companies): void {
            foreach ($companies as $company) {
                $userId = User::query()
                    ->where('company_id', $company->id)
                    ->orderBy('id')
                    ->value('id');

                if (! $userId) {
                    continue;
                }

                foreach (self::DEFAULT_LIMITS as $code => $config) {
                    $betType = BetType::updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'code' => $code,
                        ],
                        [
                            'name' => $config['name'],
                            'digits_count' => 2,
                            'min_numbers' => 1,
                            'max_numbers' => 1,
                            'numbers_count' => $config['numbers_count'],
                            'is_cross_lottery' => $config['is_cross_lottery'],
                            'requires_position' => $code === 'TRIPLETA',
                            'status' => 'ACTIVE',
                        ],
                    );

                    LimitRule::updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'branch_id' => null,
                            'lottery_id' => null,
                            'draw_id' => null,
                            'bet_type_id' => $betType->id,
                            'rule_type' => 'GLOBAL',
                        ],
                        [
                            'number_value' => null,
                            'number_from' => null,
                            'number_to' => null,
                            'numbers_json' => null,
                            'max_amount_per_number' => $config['max_amount_per_number'],
                            'max_total_amount' => null,
                            'policy' => 'BLOCK_FULL',
                            'effective_from' => now()->startOfDay(),
                            'effective_to' => null,
                            'status' => 'ACTIVE',
                            'created_by' => $userId,
                        ],
                    );
                }
            }
        });
    }
}
