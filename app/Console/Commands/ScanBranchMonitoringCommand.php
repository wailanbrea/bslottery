<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Monitoring\BranchMonitoringService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ScanBranchMonitoringCommand extends Command
{
    protected $signature = 'monitoring:scan-branches
        {--company_id= : Limitar el escaneo a una empresa}
        {--branch_id= : Limitar el escaneo a una sucursal}
        {--date= : Fecha operativa YYYY-MM-DD}';

    protected $description = 'Escanea sucursales y genera notificaciones operativas por perdidas, caja baja o jugadas altas.';

    public function handle(BranchMonitoringService $monitoring): int
    {
        $date = $this->option('date')
            ? CarbonImmutable::parse((string) $this->option('date'))
            : CarbonImmutable::now();

        $companyId = $this->option('company_id') ? (int) $this->option('company_id') : null;
        $branchId = $this->option('branch_id') ? (int) $this->option('branch_id') : null;

        $companies = Company::query()
            ->when($companyId, fn ($query) => $query->whereKey($companyId))
            ->where('status', 'ACTIVE')
            ->orderBy('id')
            ->get(['id', 'name']);

        $branchesScanned = 0;
        $alertsDetected = 0;

        foreach ($companies as $company) {
            $snapshot = $monitoring->snapshot(
                companyId: $company->id,
                branchId: $branchId,
                from: $date->startOfDay(),
                to: $date->endOfDay(),
            );

            $branchesScanned += (int) $snapshot['totals']['branches_count'];
            foreach ($snapshot['rows'] as $row) {
                $alertsDetected += count($row['alerts']);
            }
        }

        $this->info("Monitoreo completado. Sucursales: {$branchesScanned}. Alertas detectadas: {$alertsDetected}.");

        return self::SUCCESS;
    }
}
