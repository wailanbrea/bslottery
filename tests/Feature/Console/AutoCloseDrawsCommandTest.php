<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Draw;
use App\Models\Lottery;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AutoCloseDrawsCommandTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Lottery $lottery;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->company = Company::query()->create([
            'name' => 'Empresa Auto Close',
            'status' => 'ACTIVE',
            'timezone' => 'America/Santo_Domingo',
        ]);

        $branch = Branch::query()->create([
            'company_id' => $this->company->id,
            'code' => 'AC01',
            'name' => 'Sucursal Auto',
            'status' => 'ACTIVE',
        ]);

        $ownerRole = Role::query()->where('slug', 'COMPANY_OWNER')->firstOrFail();
        $this->owner = User::query()->create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Owner Auto',
            'username' => 'owner_auto',
            'password' => Hash::make('Password1234'),
            'role_id' => $ownerRole->id,
            'status' => 'ACTIVE',
        ]);

        $this->lottery = Lottery::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Nacional Tarde',
            'code' => 'NAC_TARDE',
            'country' => 'DO',
            'status' => 'ACTIVE',
        ]);
    }

    private function makeDraw(string $drawDate, string $closeTime, string $status = 'OPEN'): Draw
    {
        return Draw::query()->create([
            'company_id' => $this->company->id,
            'lottery_id' => $this->lottery->id,
            'name' => 'Sorteo test',
            'draw_date' => $drawDate,
            'open_time' => '08:00',
            'scheduled_time' => $closeTime,
            'close_time' => $closeTime,
            'status' => $status,
        ]);
    }

    public function test_auto_close_closes_draw_whose_close_time_already_passed_today(): void
    {
        $tz = $this->company->timezone;
        $now = now($tz);
        // Sorteo cuyo cierre fue hace 1 hora
        $pastTime = $now->copy()->subHour()->format('H:i:s');
        $draw = $this->makeDraw($now->toDateString(), $pastTime);

        $this->artisan('draws:auto-close')->assertSuccessful();

        $this->assertSame('CLOSED', $draw->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Draw',
            'action' => 'closed',
        ]);
    }

    public function test_auto_close_keeps_open_draw_whose_close_time_is_in_the_future(): void
    {
        $tz = $this->company->timezone;
        $now = now($tz);
        $futureTime = $now->copy()->addHours(2)->format('H:i:s');
        $draw = $this->makeDraw($now->toDateString(), $futureTime);

        $this->artisan('draws:auto-close')->assertSuccessful();

        $this->assertSame('OPEN', $draw->fresh()->status);
    }

    public function test_auto_close_closes_draws_from_previous_days_even_if_close_time_was_late(): void
    {
        $yesterday = now($this->company->timezone)->subDay()->toDateString();
        $draw = $this->makeDraw($yesterday, '23:59:00');

        $this->artisan('draws:auto-close')->assertSuccessful();

        $this->assertSame('CLOSED', $draw->fresh()->status);
    }

    public function test_auto_close_dry_run_does_not_modify_draws(): void
    {
        $tz = $this->company->timezone;
        $pastTime = now($tz)->subHour()->format('H:i:s');
        $draw = $this->makeDraw(now($tz)->toDateString(), $pastTime);

        $this->artisan('draws:auto-close', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame('OPEN', $draw->fresh()->status);
    }

    public function test_auto_close_skips_company_without_admin_operator(): void
    {
        // Crear segunda empresa sin owner
        $otherCompany = Company::query()->create([
            'name' => 'Sin Owner',
            'status' => 'ACTIVE',
            'timezone' => 'America/Santo_Domingo',
        ]);
        $otherLottery = Lottery::query()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Otra',
            'code' => 'OTRA',
            'country' => 'DO',
            'status' => 'ACTIVE',
        ]);
        $orphanDraw = Draw::query()->create([
            'company_id' => $otherCompany->id,
            'lottery_id' => $otherLottery->id,
            'name' => 'Sorteo huerfano',
            'draw_date' => now($otherCompany->timezone)->toDateString(),
            'open_time' => '08:00',
            'scheduled_time' => now($otherCompany->timezone)->subHour()->format('H:i:s'),
            'close_time' => now($otherCompany->timezone)->subHour()->format('H:i:s'),
            'status' => 'OPEN',
        ]);

        $this->artisan('draws:auto-close')->assertSuccessful();

        // El sorteo huerfano queda OPEN (skip), el de la empresa con owner se cierra
        $this->assertSame('OPEN', $orphanDraw->fresh()->status);
    }
}
