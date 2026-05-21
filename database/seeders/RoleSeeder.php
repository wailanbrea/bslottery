<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, level: int}>
     */
    private array $roles = [
        'SUPER_ADMIN' => ['name' => 'Super Admin', 'level' => 1000],
        'COMPANY_OWNER' => ['name' => 'Company Owner', 'level' => 900],
        'ADMIN' => ['name' => 'Admin', 'level' => 800],
        'SUPERVISOR' => ['name' => 'Supervisor', 'level' => 600],
        'CASHIER' => ['name' => 'Cashier', 'level' => 300],
        'PAYER' => ['name' => 'Payer', 'level' => 300],
        'BRANCH_CASHIER_PAYER' => ['name' => 'Branch Cashier Payer', 'level' => 320],
        'RESULT_CONFIRM_ONLY' => ['name' => 'Result Confirm Only', 'level' => 350],
        'ACCOUNTANT' => ['name' => 'Accountant', 'level' => 500],
        'PAYROLL_MANAGER' => ['name' => 'Payroll Manager', 'level' => 500],
        'AUDITOR' => ['name' => 'Auditor', 'level' => 400],
    ];

    public function run(): void
    {
        $allPermissionIds = Permission::query()->pluck('id')->all();

        foreach ($this->roles as $slug => $definition) {
            $role = Role::query()->updateOrCreate(
                ['company_id' => null, 'slug' => $slug],
                [
                    'name' => $definition['name'],
                    'level' => $definition['level'],
                    'description' => null,
                    'status' => 'ACTIVE',
                ]
            );

            $role->permissions()->sync($this->permissionIdsForRole($slug, $allPermissionIds));
        }
    }

    /**
     * @param array<int, int> $allPermissionIds
     * @return array<int, int>
     */
    private function permissionIdsForRole(string $slug, array $allPermissionIds): array
    {
        if ($slug === 'SUPER_ADMIN') {
            return $allPermissionIds;
        }

        $permissions = match ($slug) {
            'COMPANY_OWNER', 'ADMIN' => [
                'dashboard.view',
                'companies.view',
                'companies.update',
                'branches.view',
                'branches.create',
                'branches.update',
                'branches.suspend',
                'users.view',
                'users.create',
                'users.update',
                'users.block',
                'roles.view',
                'roles.assign_permissions',
                'monitoring.view',
                'monitoring.configure',
                'notifications.view',
                'notifications.manage',
                'employees.view',
                'employees.create',
                'employees.update',
                'lotteries.view',
                'lotteries.create',
                'lotteries.update',
                'draws.view',
                'draws.create',
                'draws.update',
                'draws.close',
                'payout_rules.view',
                'payout_rules.create',
                'payout_rules.update',
                'payout_rules.approve',
                'limit_rules.view',
                'limit_rules.create',
                'limit_rules.update',
                'limit_rules.approve',
                'sales.create',
                'sales.preview',
                'sales.cancel',
                'sales.reprint',
                'tickets.view',
                'tickets.cancel',
                'tickets.reprint',
                'results.view',
                'results.create',
                'results.confirm',
                'results.modify_confirmed',
                'winners.calculate',
                'payments.authorize',
                'prizes.pay',
                'cash.open',
                'cash.view',
                'cash.movement',
                'cash.close',
                'cash.confirm',
                'cash.reopen',
                'cash.transfers.view',
                'cash.transfers.create',
                'cash.transfers.verify',
                'cash.funding.view',
                'cash.funding.create',
                'cash.incidents.view',
                'cash.incidents.resolve',
                'accounting.view',
                'accounting.manage_accounts',
                'accounting.create_entry',
                'accounting.reports',
                'devices.view',
                'devices.authorize',
                'devices.block',
                'printers.view',
                'printers.configure',
                'printers.test',
                'reports.view',
                'audit.view',
                'settings.view',
                'settings.update',
                'license.view',
                'license.validate',
            ],
            'SUPERVISOR' => [
                'dashboard.view',
                'branches.view',
                'users.view',
                'employees.view',
                'devices.view',
                'reports.view',
                'audit.view',
                'monitoring.view',
                'notifications.view',
                'notifications.manage',
                'cash.transfers.view',
                'cash.transfers.verify',
                'cash.funding.view',
                'cash.funding.create',
                'cash.incidents.view',
                'cash.incidents.resolve',
            ],
            'CASHIER' => [
                'dashboard.view',
                'sales.create',
                'sales.preview',
                'sales.cancel',
                'sales.reprint',
                'tickets.view',
                'tickets.cancel',
                'tickets.reprint',
                'cash.open',
                'cash.view',
                'cash.movement',
                'cash.close',
                'cash.transfers.view',
                'cash.transfers.create',
            ],
            'PAYER' => [
                'dashboard.view',
                'tickets.view',
                'prizes.pay',
                'cash.view',
                'cash.movement',
                'cash.transfers.view',
                'cash.transfers.create',
            ],
            'BRANCH_CASHIER_PAYER' => [
                'dashboard.view',
                'sales.create',
                'sales.preview',
                'sales.reprint',
                'tickets.view',
                'tickets.reprint',
                'prizes.pay',
                'cash.open',
                'cash.view',
                'cash.movement',
                'cash.close',
                'cash.transfers.view',
                'cash.transfers.create',
            ],
            'RESULT_CONFIRM_ONLY' => [
                'dashboard.view',
                'results.view',
                'results.confirm',
            ],
            'ACCOUNTANT' => [
                'dashboard.view',
                'accounting.view',
                'accounting.manage_accounts',
                'accounting.create_entry',
                'accounting.reports',
                'reports.view',
                'reports.export',
                'monitoring.view',
                'notifications.view',
                'cash.transfers.view',
                'cash.transfers.verify',
                'cash.funding.view',
                'cash.funding.create',
                'cash.incidents.view',
                'cash.incidents.resolve',
            ],
            'PAYROLL_MANAGER' => [
                'dashboard.view',
                'employees.view',
                'payroll.view',
                'payroll.calculate',
                'payroll.approve',
                'payroll.pay',
                'reports.view',
            ],
            'AUDITOR' => [
                'dashboard.view',
                'reports.view',
                'audit.view',
                'monitoring.view',
                'notifications.view',
                'cash.transfers.view',
                'cash.incidents.view',
            ],
            default => [],
        };

        return Permission::query()
            ->whereIn('slug', $permissions)
            ->pluck('id')
            ->all();
    }
}
