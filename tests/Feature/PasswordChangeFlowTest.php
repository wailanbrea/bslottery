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
use Tests\TestCase;

class PasswordChangeFlowTest extends TestCase
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
            'username' => 'usuario-test',
            'email' => 'usuario@example.com',
            'password' => Hash::make('Password1234'),
            'role_id' => $role->id,
            'status' => 'ACTIVE',
            'must_change_password' => true,
        ]);
    }

    public function test_user_with_temporary_password_is_redirected_to_change_password(): void
    {
        $this->actingAs($this->user)
            ->get(route('admin.users.index'))
            ->assertRedirect(route('password.edit'));
    }

    public function test_user_can_change_temporary_password_and_continue(): void
    {
        $this->actingAs($this->user)
            ->put(route('password.update'), [
                'current_password' => 'Password1234',
                'password' => 'NuevaClave2026',
                'password_confirmation' => 'NuevaClave2026',
            ])
            ->assertRedirect(route('dashboard'));

        $this->user->refresh();

        $this->assertFalse($this->user->must_change_password);
        $this->assertNotNull($this->user->password_changed_at);
        $this->assertTrue(Hash::check('NuevaClave2026', $this->user->password));
    }

    public function test_current_password_is_required_to_change_password(): void
    {
        $this->actingAs($this->user)
            ->from(route('password.edit'))
            ->put(route('password.update'), [
                'current_password' => 'ClaveIncorrecta',
                'password' => 'NuevaClave2026',
                'password_confirmation' => 'NuevaClave2026',
            ])
            ->assertRedirect(route('password.edit'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue($this->user->fresh()->must_change_password);
    }

    public function test_authenticated_user_can_access_voluntary_password_change_from_layout(): void
    {
        $this->user->forceFill(['must_change_password' => false])->save();

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('password.edit'), false)
            ->assertSee('Clave');

        $this->actingAs($this->user)
            ->get(route('password.edit'))
            ->assertOk()
            ->assertSee('Actualiza tu clave de acceso cuando lo necesites.')
            ->assertSee('Volver al dashboard');
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
