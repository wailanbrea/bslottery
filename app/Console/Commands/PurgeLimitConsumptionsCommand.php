<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LimitConsumption;
use Illuminate\Console\Command;

class PurgeLimitConsumptionsCommand extends Command
{
    protected $signature = 'limits:purge
                            {--days=90 : Borrar consumos cuyo draw cerro hace mas de N dias (default 90)}
                            {--dry-run : Solo mostrar cuantos se borrarian}';

    protected $description = 'Borra consumos de limites historicos (LimitConsumption) cuyo draw esta cerrado/finalizado hace mas de N dias. El reset diario funciona por draw_id, esto solo compacta la tabla.';

    public function handle(): int
    {
        $rawDays = $this->option('days');
        $days = $rawDays === null ? 90 : (int) $rawDays;
        if ($days < 1) {
            $this->error('La opcion --days debe ser >= 1.');

            return self::INVALID;
        }
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);

        // Solo borrar consumos de draws en estados terminales para no romper validacion en vivo
        $terminalStatuses = ['CLOSED', 'CANCELLED', 'FINALIZED', 'RESULT_CONFIRMED', 'WINNERS_CALCULATED', 'PAYMENTS_RELEASED'];

        $query = LimitConsumption::query()
            ->whereHas('draw', function ($q) use ($cutoff, $terminalStatuses): void {
                $q->whereIn('status', $terminalStatuses)
                    ->where(function ($q2) use ($cutoff): void {
                        $q2->where('closed_at', '<', $cutoff)
                            ->orWhere(function ($q3) use ($cutoff): void {
                                // Fallback para draws cerrados sin closed_at: usar draw_date
                                $q3->whereNull('closed_at')
                                    ->whereDate('draw_date', '<', $cutoff->toDateString());
                            });
                    });
            });

        $count = $query->count();

        if ($dryRun) {
            $this->info("[DRY-RUN] Se borrarian {$count} consumo(s) de limite (cutoff: {$cutoff->toDateTimeString()}, dias: {$days}).");

            return self::SUCCESS;
        }

        if ($count === 0) {
            $this->info("Sin consumos para purgar (cutoff: {$cutoff->toDateTimeString()}, dias: {$days}).");

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Purga completada: {$deleted} consumo(s) borrados (cutoff: {$cutoff->toDateTimeString()}, dias: {$days}).");

        return self::SUCCESS;
    }
}
