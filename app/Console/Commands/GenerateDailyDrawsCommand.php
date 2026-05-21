<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Lottery\DrawGenerationService;
use Illuminate\Console\Command;

class GenerateDailyDrawsCommand extends Command
{
    protected $signature = 'draws:generate-daily
                            {--date= : Fecha base YYYY-MM-DD. Si se omite, usa "hoy" en la TZ de cada empresa.}
                            {--days=1 : Cantidad de dias a generar (incluye el dia base).}';

    protected $description = 'Genera los sorteos diarios para todas las empresas activas, respetando su zona horaria.';

    public function handle(DrawGenerationService $service): int
    {
        $date = $this->option('date') ?: null;
        $days = (int) ($this->option('days') ?: 1);

        if ($days < 1) {
            $this->error('La opcion --days debe ser >= 1.');

            return self::INVALID;
        }

        $label = $date ? "fecha base {$date}" : 'TZ de cada empresa';
        $this->info("Generando sorteos ({$days} dia/s) para {$label}...");

        $stats = $service->generate($date, $days);

        $this->table(
            ['Empresas procesadas', 'Sorteos creados', 'Sorteos ya existentes', 'Dias cubiertos'],
            [[
                $stats['companies_processed'],
                $stats['draws_created'],
                $stats['draws_skipped'],
                $stats['days_covered'],
            ]],
        );

        return self::SUCCESS;
    }
}
