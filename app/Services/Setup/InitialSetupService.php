<?php

declare(strict_types=1);

namespace App\Services\Setup;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LicenseState;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Licensing\LicenseLimitManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InitialSetupService
{
    public function __construct(
        private readonly InitialBusinessProfileService $profileService,
        private readonly LicenseLimitManager $limits,
        private readonly AuditService $audit,
    ) {
    }

    public function isCompleted(): bool
    {
        return Company::query()->exists() && User::query()->exists();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createFromLicense(LicenseState $license, array $data): User
    {
        if ($this->isCompleted()) {
            throw ValidationException::withMessages([
                'setup' => 'El setup inicial ya fue completado.',
            ]);
        }

        $maxUsers = $this->limits->integer('max_users', 0, $license);

        if ($maxUsers > 0 && $maxUsers < 1) {
            throw ValidationException::withMessages([
                'admin_username' => 'La licencia no permite crear usuarios.',
            ]);
        }

        $profile = $this->profileService->fromLicense($license);

        return DB::transaction(function () use ($data, $profile): User {
            $company = Company::query()->create([
                'external_code' => $profile['company']['external_code'],
                'name' => $data['company_name'] ?: $profile['company']['name'],
                'legal_name' => $data['legal_name'] ?: $profile['company']['legal_name'],
                'rnc' => $data['rnc'] ?: $profile['company']['rnc'],
                'phone' => $data['phone'] ?: $profile['company']['phone'],
                'email' => $data['company_email'] ?: null,
                'address' => $data['address'] ?: $profile['company']['address'],
                'status' => 'ACTIVE',
            ]);

            $branch = Branch::query()->create([
                'company_id' => $company->id,
                'external_code' => $profile['branch']['external_code'],
                'code' => $data['branch_code'] ?: $profile['branch']['code'],
                'name' => $data['branch_name'] ?: $profile['branch']['name'],
                'phone' => $data['phone'] ?: $profile['branch']['phone'],
                'address' => $data['address'] ?: $profile['branch']['address'],
                'status' => 'ACTIVE',
            ]);

            $role = Role::query()
                ->whereNull('company_id')
                ->where('slug', 'COMPANY_OWNER')
                ->firstOrFail();

            $employee = Employee::query()->create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'name' => $data['admin_name'],
                'position' => 'Administrador',
                'status' => 'ACTIVE',
            ]);

            $user = User::query()->create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'name' => $data['admin_name'],
                'username' => $data['admin_username'],
                'email' => $data['admin_email'] ?: null,
                'password' => $data['admin_password'],
                'role_id' => $role->id,
                'employee_id' => $employee->id,
                'status' => 'ACTIVE',
            ]);

            $employee->forceFill(['user_id' => $user->id])->save();

            $this->audit->record(
                module: 'setup',
                action: 'completed',
                description: 'Setup inicial completado desde metadata de licencia.',
                auditable: $company,
                user: $user,
                newValues: [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'user_id' => $user->id,
                ],
            );

            return $user;
        });
    }
}
