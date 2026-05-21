<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Company;
use App\Models\Draw;
use App\Models\Lottery;
use App\Support\DominicanLotteryCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GenerateDailyDrawsCommandTest extends TestCase
{
    use RefreshDatabase;

    private const CATALOG_COUNT = 32;

    public function test_creates_one_draw_per_active_lottery_for_active_company(): void
    {
        $company = $this->makeCompanyWithCatalog('ACTIVE', 'America/Santo_Domingo');

        $this->artisan('draws:generate-daily', ['--date' => '2026-05-20'])
            ->expectsOutputToContain('Generando sorteos')
            ->assertSuccessful();

        $this->assertSame(
            self::CATALOG_COUNT,
            Draw::where('company_id', $company->id)->whereDate('draw_date', '2026-05-20')->count(),
        );
    }

    public function test_is_idempotent_when_run_twice_for_same_day(): void
    {
        $company = $this->makeCompanyWithCatalog('ACTIVE', 'America/Santo_Domingo');

        $this->artisan('draws:generate-daily', ['--date' => '2026-05-20'])->assertSuccessful();
        $this->artisan('draws:generate-daily', ['--date' => '2026-05-20'])->assertSuccessful();

        $this->assertSame(
            self::CATALOG_COUNT,
            Draw::where('company_id', $company->id)->whereDate('draw_date', '2026-05-20')->count(),
        );
    }

    public function test_skips_suspended_companies(): void
    {
        $active = $this->makeCompanyWithCatalog('ACTIVE', 'America/Santo_Domingo');
        $suspended = $this->makeCompanyWithCatalog('SUSPENDED', 'America/Santo_Domingo');

        $this->artisan('draws:generate-daily', ['--date' => '2026-05-20'])->assertSuccessful();

        $this->assertSame(self::CATALOG_COUNT, Draw::where('company_id', $active->id)->count());
        $this->assertSame(0, Draw::where('company_id', $suspended->id)->count());
    }

    public function test_respects_company_timezone_for_today_when_no_date_given(): void
    {
        // Servidor en UTC; empresa en RD. A las 02:00 UTC son las 22:00 RD del dia anterior.
        // Si tomaramos "hoy" del servidor, generariamos sorteos del 2026-05-21;
        // pero en RD aun es 2026-05-20, asi que deben crearse para el 20.
        Carbon::setTestNow(Carbon::create(2026, 5, 21, 2, 0, 0, 'UTC'));

        try {
            $company = $this->makeCompanyWithCatalog('ACTIVE', 'America/Santo_Domingo');

            $this->artisan('draws:generate-daily')->assertSuccessful();

            $this->assertSame(
                self::CATALOG_COUNT,
                Draw::where('company_id', $company->id)->whereDate('draw_date', '2026-05-20')->count(),
                'Los sorteos deben crearse para la fecha local RD (20), no UTC (21).',
            );
            $this->assertSame(
                0,
                Draw::where('company_id', $company->id)->whereDate('draw_date', '2026-05-21')->count(),
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_days_option_generates_multiple_days(): void
    {
        $company = $this->makeCompanyWithCatalog('ACTIVE', 'America/Santo_Domingo');

        $this->artisan('draws:generate-daily', ['--date' => '2026-05-20', '--days' => 3])
            ->assertSuccessful();

        foreach (['2026-05-20', '2026-05-21', '2026-05-22'] as $date) {
            $this->assertSame(
                self::CATALOG_COUNT,
                Draw::where('company_id', $company->id)->whereDate('draw_date', $date)->count(),
                "Faltan sorteos para {$date}.",
            );
        }
    }

    public function test_ignores_inactive_lotteries(): void
    {
        $company = $this->makeCompanyWithCatalog('ACTIVE', 'America/Santo_Domingo');

        // Desactivamos una loteria especifica
        Lottery::query()
            ->where('company_id', $company->id)
            ->where('code', 'POWERBALL')
            ->update(['status' => 'INACTIVE']);

        $this->artisan('draws:generate-daily', ['--date' => '2026-05-20'])->assertSuccessful();

        $this->assertSame(
            self::CATALOG_COUNT - 1,
            Draw::where('company_id', $company->id)->whereDate('draw_date', '2026-05-20')->count(),
        );
    }

    private function makeCompanyWithCatalog(string $status, string $timezone): Company
    {
        $company = Company::create([
            'name' => 'Empresa '.uniqid(),
            'status' => $status,
            'timezone' => $timezone,
            'currency' => 'DOP',
        ]);

        foreach (DominicanLotteryCatalog::entries() as $entry) {
            Lottery::create([
                'company_id' => $company->id,
                'code' => $entry['code'],
                'name' => $entry['name'],
                'country' => $entry['country'],
                'status' => 'ACTIVE',
            ]);
        }

        return $company;
    }
}
