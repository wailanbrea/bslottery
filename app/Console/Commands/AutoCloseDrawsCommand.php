<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Draw;
use App\Models\User;
use App\Services\Lottery\DrawLifecycleService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class AutoCloseDrawsCommand extends Command
{
    protected $signature = 'draws:auto-close
                            {--dry-run : Solo mostrar que se cerraria, sin cerrar}';

    protected $description = 'Cierra automaticamente los sorteos cuyo close_time ya paso en la zona horaria de cada empresa. Politica: KEEP_CURRENT (mantiene tickets activos esperando resultado).';

    public function handle(DrawLifecycleService $lifecycle): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $totalClosed = 0;
        $totalFailed = 0;
        $totalSkipped = 0;

        $companies = Company::query()->where('status', 'ACTIVE')->get();

        foreach ($companies as $company) {
            $tz = $company->timezone ?: 'America/Santo_Domingo';
            $nowInTz = CarbonImmutable::now($tz);

            // Sorteos OPEN cuyo cierre (draw_date + close_time) ya paso en la TZ de la empresa
            $candidates = Draw::query()
                ->where('company_id', $company->id)
                ->where('status', 'OPEN')
                ->whereNotNull('close_time')
                ->where(function ($q) use ($nowInTz): void {
                    // Sorteos anteriores a hoy: cualquier close_time vencido
                    $q->whereDate('draw_date', '<', $nowInTz->toDateString())
                        // Sorteos de hoy: close_time <= ahora
                        ->orWhere(function ($q2) use ($nowInTz): void {
                            $q2->whereDate('draw_date', $nowInTz->toDateString())
                                ->where('close_time', '<=', $nowInTz->format('H:i:s'));
                        });
                })
                ->orderBy('draw_date')
                ->orderBy('close_time')
                ->get();

            if ($candidates->isEmpty()) {
                continue;
            }

            // Operador del sistema: COMPANY_OWNER o cualquier admin activo de la empresa
            $operator = $this->resolveSystemOperator($company->id);
            if (! $operator) {
                $this->warn("Empresa {$company->id} ({$company->name}): sin operador admin para cerrar. Skip.");
                $totalSkipped += $candidates->count();

                continue;
            }

            foreach ($candidates as $draw) {
                $label = "Empresa {$company->id} / Draw #{$draw->id} ({$draw->name} {$draw->draw_date->format('Y-m-d')} {$draw->close_time})";

                if ($dryRun) {
                    $this->line("[DRY-RUN] Cerraria: {$label}");
                    $totalClosed++;

                    continue;
                }

                try {
                    $lifecycle->close(
                        draw: $draw,
                        user: $operator,
                        ticketPolicy: DrawLifecycleService::POLICY_KEEP_CURRENT,
                        reason: 'Cierre automatico por scheduler (close_time vencido).',
                    );
                    $this->info("CERRADO: {$label}");
                    $totalClosed++;
                } catch (\Throwable $e) {
                    $this->error("FALLO al cerrar {$label}: {$e->getMessage()}");
                    $totalFailed++;
                }
            }
        }

        $this->table(
            ['Cerrados', 'Fallidos', 'Skip (sin operador)', 'Modo'],
            [[$totalClosed, $totalFailed, $totalSkipped, $dryRun ? 'DRY-RUN' : 'EJECUTADO']],
        );

        return $totalFailed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Resuelve un usuario admin activo de la empresa para registrar el audit log.
     * Prioriza COMPANY_OWNER, luego cualquier admin con permiso `draws.close`.
     */
    private function resolveSystemOperator(int $companyId): ?User
    {
        $owner = User::query()
            ->where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->whereHas('role', fn ($q) => $q->where('slug', 'COMPANY_OWNER'))
            ->first();

        if ($owner) {
            return $owner;
        }

        return User::query()
            ->where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->whereHas('role.permissions', fn ($q) => $q->where('slug', 'draws.close'))
            ->first();
    }
}
