<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\LicenseState;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->createValidLicenseState();

        $company = Company::query()->create([
            'uuid' => '11111111-1111-1111-1111-111111111111',
            'name' => 'Banca Test',
            'status' => 'ACTIVE',
        ]);

        $branch = Branch::query()->create([
            'uuid' => '22222222-2222-2222-2222-222222222222',
            'company_id' => $company->id,
            'code' => 'TEST',
            'name' => 'Sucursal Test',
            'status' => 'ACTIVE',
        ]);

        $role = Role::query()->where('slug', 'COMPANY_OWNER')->firstOrFail();

        $this->user = User::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Usuario Test',
            'username' => 'cajero-test',
            'email' => 'cajero@test.com',
            'password' => Hash::make('ClaveCorrecta99'),
            'role_id' => $role->id,
            'status' => 'ACTIVE',
            'must_change_password' => false,
            'failed_login_attempts' => 0,
        ]);
    }

    public function test_failed_attempts_are_counted(): void
    {
        $this->post(route('login.store'), [
            'username' => 'cajero-test',
            'password' => 'ClaveIncorrecta',
        ]);

        $this->assertEquals(1, $this->user->fresh()->failed_login_attempts);
    }

    public function test_user_is_locked_after_five_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), [
                'username' => 'cajero-test',
                'password' => 'ClaveIncorrecta',
            ]);
        }

        $fresh = $this->user->fresh();
        $this->assertEquals(5, $fresh->failed_login_attempts);
        $this->assertNotNull($fresh->locked_until);
        $this->assertTrue($fresh->locked_until->isFuture());
    }

    public function test_locked_user_cannot_login_with_correct_password(): void
    {
        $this->user->forceFill([
            'locked_until' => now()->addMinutes(10),
        ])->save();

        $this->post(route('login.store'), [
            'username' => 'cajero-test',
            'password' => 'ClaveCorrecta99',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_successful_login_resets_failed_attempts_and_clears_rate_limiter(): void
    {
        $this->user->forceFill(['failed_login_attempts' => 3])->save();

        $this->post(route('login.store'), [
            'username' => 'cajero-test',
            'password' => 'ClaveCorrecta99',
        ])->assertRedirect(route('dashboard'));

        $fresh = $this->user->fresh();
        $this->assertEquals(0, $fresh->failed_login_attempts);
        $this->assertNull($fresh->locked_until);
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_rate_limiter_blocks_after_ten_attempts(): void
    {
        RateLimiter::clear('cajero-test|127.0.0.1');

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('login.store'), [
                'username' => 'cajero-test',
                'password' => 'ClaveIncorrecta',
            ]);
        }

        $this->post(route('login.store'), [
            'username' => 'cajero-test',
            'password' => 'ClaveIncorrecta',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_must_change_password_redirects_after_login(): void
    {
        $this->user->forceFill(['must_change_password' => true])->save();

        $this->post(route('login.store'), [
            'username' => 'cajero-test',
            'password' => 'ClaveCorrecta99',
        ])->assertRedirect(route('password.edit'));
    }

    private function createValidLicenseState(): LicenseState
    {
        return LicenseState::query()->create([
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
            'expires_at' => now()->addYear(),
            'last_validation_success' => true,
            'last_validation_at' => now(),
            'last_server_time' => now(),
            'last_seen_system_time' => now(),
            'features' => ['offline_mode' => true],
            'limits' => ['offline_grace_hours' => 72],
            'metadata' => [],
            'client' => [],
            'location' => [],
            'is_active' => true,
        ]);
    }
}
