<?php

namespace Database\Seeders;

use App\Models\AccountingAccount;
use App\Support\Accounting\DefaultChartOfAccounts;
use Illuminate\Database\Seeder;

class AccountingAccountSeeder extends Seeder
{
    public function run(): void
    {
        $companies = \App\Models\Company::all();

        foreach ($companies as $company) {
            foreach (DefaultChartOfAccounts::definitions() as $account) {
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
