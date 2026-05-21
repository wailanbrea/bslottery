<?php

namespace Database\Seeders;

use App\Models\AccountingAccount;
use Illuminate\Database\Seeder;

class AccountingAccountSeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, name: string, type: string}>
     */
    private array $accounts = [
        ['code' => '1000', 'name' => 'Caja', 'type' => 'ASSET'],
        ['code' => '1100', 'name' => 'Caja por sucursal', 'type' => 'ASSET'],
        ['code' => '1200', 'name' => 'Banco', 'type' => 'ASSET'],
        ['code' => '1300', 'name' => 'Cuentas por cobrar empleados', 'type' => 'ASSET'],
        ['code' => '2000', 'name' => 'Cuentas por pagar', 'type' => 'LIABILITY'],
        ['code' => '3000', 'name' => 'Capital', 'type' => 'EQUITY'],
        ['code' => '4000', 'name' => 'Ingresos por ventas de lotería', 'type' => 'INCOME'],
        ['code' => '5000', 'name' => 'Premios pagados', 'type' => 'EXPENSE'],
        ['code' => '5100', 'name' => 'Gastos operativos', 'type' => 'EXPENSE'],
        ['code' => '5200', 'name' => 'Nómina', 'type' => 'EXPENSE'],
        ['code' => '5300', 'name' => 'Faltantes de caja', 'type' => 'EXPENSE'],
        ['code' => '5400', 'name' => 'Comisiones', 'type' => 'EXPENSE'],
    ];

    public function run(): void
    {
        $companies = \App\Models\Company::all();

        foreach ($companies as $company) {
            foreach ($this->accounts as $account) {
                AccountingAccount::query()->firstOrCreate(
                    ['company_id' => $company->id, 'code' => $account['code']],
                    [
                        'name' => $account['name'],
                        'type' => $account['type'],
                        'status' => 'ACTIVE',
                    ]
                );
            }
        }
    }
}
