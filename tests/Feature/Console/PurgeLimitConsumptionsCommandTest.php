<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\BetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Draw;
use App\Models\LimitConsumption;
use App\Models\Lottery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeLimitConsumptionsCommandTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Branch $branch;

    private Lottery $lottery;

    private BetType $betType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::query()->create([
            'name' => 'Purge Test', 'status' => 'ACTIVE', 'timezone' => 'America/Santo_Domingo',
        ]);
        $this->branch = Branch::query()->create([
            'company_id' => $this->company->id, 'code' => 'PG01', 'name' => 'Purge Branch', 'status' => 'ACTIVE',
        ]);
        $this->lottery = Lottery::query()->create([
            'company_id' => $this->company->id, 'name' => 'Lot Purge', 'code' => 'PURGE', 'country' => 'DO', 'status' => 'ACTIVE',
        ]);
        $this->betType = BetType::query()->create([
            'company_id' => $this->company->id, 'code' => 'QUINIELA', 'name' => 'Quiniela',
            'numbers_count' => 1, 'digits_count' => 2, 'status' => 'ACTIVE',
        ]);
    }

    private function makeDrawWithConsumption(string $drawDate, string $status, mixed $closedAt, string $numberValue = '12'): LimitConsumption
    {
        $draw = Draw::query()->create([
            'company_id' => $this->company->id,
            'lottery_id' => $this->lottery->id,
            'name' => "Sorteo {$drawDate}",
            'draw_date' => $drawDate,
            'open_time' => '08:00',
            'scheduled_time' => '20:00',
            'close_time' => '20:00',
            'status' => $status,
            'closed_at' => $closedAt,
        ]);

        return LimitConsumption::query()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'lottery_id' => $this->lottery->id,
            'draw_id' => $draw->id,
            'bet_type_id' => $this->betType->id,
            'number_value' => $numberValue,
            'sold_amount' => '100.00',
        ]);
    }

    public function test_purge_deletes_consumptions_from_closed_draws_older_than_threshold(): void
    {
        $old = $this->makeDrawWithConsumption(
            drawDate: now()->subDays(100)->toDateString(),
            status: 'CLOSED',
            closedAt: now()->subDays(100),
        );
        $recent = $this->makeDrawWithConsumption(
            drawDate: now()->subDays(10)->toDateString(),
            status: 'CLOSED',
            closedAt: now()->subDays(10),
            numberValue: '34',
        );

        $this->artisan('limits:purge', ['--days' => 90])->assertSuccessful();

        $this->assertDatabaseMissing('limit_consumptions', ['id' => $old->id]);
        $this->assertDatabaseHas('limit_consumptions', ['id' => $recent->id]);
    }

    public function test_purge_keeps_consumptions_from_open_draws_regardless_of_age(): void
    {
        $openOld = $this->makeDrawWithConsumption(
            drawDate: now()->subDays(200)->toDateString(),
            status: 'OPEN',
            closedAt: null,
        );

        $this->artisan('limits:purge', ['--days' => 90])->assertSuccessful();

        $this->assertDatabaseHas('limit_consumptions', ['id' => $openOld->id]);
    }

    public function test_purge_uses_draw_date_as_fallback_when_closed_at_is_null(): void
    {
        // Draw cerrado sin closed_at registrado (caso legacy)
        $legacy = $this->makeDrawWithConsumption(
            drawDate: now()->subDays(120)->toDateString(),
            status: 'FINALIZED',
            closedAt: null,
        );

        $this->artisan('limits:purge', ['--days' => 90])->assertSuccessful();

        $this->assertDatabaseMissing('limit_consumptions', ['id' => $legacy->id]);
    }

    public function test_purge_dry_run_does_not_delete_anything(): void
    {
        $old = $this->makeDrawWithConsumption(
            drawDate: now()->subDays(100)->toDateString(),
            status: 'CLOSED',
            closedAt: now()->subDays(100),
        );

        $this->artisan('limits:purge', ['--days' => 90, '--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseHas('limit_consumptions', ['id' => $old->id]);
    }

    public function test_purge_validates_days_parameter(): void
    {
        $this->artisan('limits:purge', ['--days' => 0])->assertExitCode(2);
    }
}
