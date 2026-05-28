<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'dashboard.view',
        'companies.view',
        'companies.create',
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
        'roles.create',
        'roles.update',
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
        'lotteries.toggle',
        'draws.view',
        'draws.create',
        'draws.update',
        'draws.close',
        'draws.reopen',
        'payout_rules.view',
        'payout_rules.create',
        'payout_rules.update',
        'payout_rules.approve',
        'limit_rules.view',
        'limit_rules.create',
        'limit_rules.update',
        'limit_rules.approve',
        'limit_rules.import',
        'sales.create',
        'sales.preview',
        'sales.offline',
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
        'payroll.view',
        'payroll.manage',
        'payroll.approve',
        'payroll.pay',
        'devices.view',
        'devices.authorize',
        'devices.block',
        'printers.view',
        'printers.configure',
        'printers.test',
        'reports.view',
        'reports.export',
        'audit.view',
        'settings.view',
        'settings.update',
        'license.view',
        'license.activate',
        'license.validate',
    ];

    public function run(): void
    {
        foreach ($this->permissions as $slug) {
            [$module, $action] = explode('.', $slug, 2);

            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'module' => $module,
                    'action' => $action,
                    'name' => Str::headline(str_replace('.', ' ', $slug)),
                    'description' => null,
                ]
            );
        }
    }
}
