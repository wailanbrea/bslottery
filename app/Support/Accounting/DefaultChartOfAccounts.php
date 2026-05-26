<?php

namespace App\Support\Accounting;

final class DefaultChartOfAccounts
{
    /**
     * @return array<int, array{code: string, name: string, type: string}>
     */
    public static function definitions(): array
    {
        return [
            ['code' => '1000', 'name' => 'Caja', 'type' => 'ASSET'],
            ['code' => '1100', 'name' => 'Caja por sucursal', 'type' => 'ASSET'],
            ['code' => '1200', 'name' => 'Banco', 'type' => 'ASSET'],
            ['code' => '1300', 'name' => 'Cuentas por cobrar empleados', 'type' => 'ASSET'],
            ['code' => '2000', 'name' => 'Cuentas por pagar', 'type' => 'LIABILITY'],
            ['code' => '3000', 'name' => 'Capital', 'type' => 'EQUITY'],
            ['code' => '4000', 'name' => 'Ingresos por ventas de loteria', 'type' => 'INCOME'],
            ['code' => '5000', 'name' => 'Premios pagados', 'type' => 'EXPENSE'],
            ['code' => '5100', 'name' => 'Gastos operativos', 'type' => 'EXPENSE'],
            ['code' => '5200', 'name' => 'Nomina', 'type' => 'EXPENSE'],
            ['code' => '5300', 'name' => 'Faltantes de caja', 'type' => 'EXPENSE'],
            ['code' => '5400', 'name' => 'Comisiones', 'type' => 'EXPENSE'],
        ];
    }
}
