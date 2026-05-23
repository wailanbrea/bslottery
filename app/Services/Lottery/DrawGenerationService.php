<?php

declare(strict_types=1);

namespace App\Services\Lottery;

use App\Models\Company;
use App\Models\Draw;
use App\Models\Lottery;
use App\Support\DominicanLotteryCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DrawGenerationService
{
    /**
     * Genera los sorteos faltantes para empresas activas.
     *
     * Por cada empresa ACTIVE, calcula la fecha "hoy" en su propia zona horaria
     * y crea (si no existen) los sorteos de cada loteria activa para los proximos
     * $days dias inclusive. La operacion es idempotente.
     *
     * @param  string|null  $forDate  YYYY-MM-DD para forzar la fecha base (ignora TZ).
     *                                Si es null, usa la fecha "hoy" en la TZ de cada empresa.
     * @param  int  $days  Numero de dias hacia adelante a generar (>=1).
     * @return array{companies_processed: int, draws_created: int, draws_skipped: int, days_covered: int}
     */
    public function generate(?string $forDate = null, int $days = 1): array
    {
        $days = max(1, $days);
        $stats = [
            'companies_processed' => 0,
            'draws_created' => 0,
            'draws_skipped' => 0,
            'days_covered' => $days,
        ];

        Company::query()
            ->where('status', 'ACTIVE')
            ->select(['id', 'timezone'])
            ->chunkById(100, function (Collection $companies) use (&$stats, $forDate, $days): void {
                foreach ($companies as $company) {
                    $stats['companies_processed']++;
                    $baseDate = $this->resolveBaseDate($forDate, (string) ($company->timezone ?: 'America/Santo_Domingo'));
                    $this->generateForCompany((int) $company->id, $baseDate->toDateString(), $days, $stats);
                }
            });

        return $stats;
    }

    /**
     * Genera sorteos faltantes para una empresa especifica. Es idempotente y
     * esta pensado para pantallas operativas cuando el scheduler no corrio.
     *
     * @param  array{companies_processed: int, draws_created: int, draws_skipped: int, days_covered: int}|null  $stats
     * @return array{companies_processed: int, draws_created: int, draws_skipped: int, days_covered: int}
     */
    public function generateForCompany(int $companyId, ?string $forDate = null, int $days = 1, ?array &$stats = null): array
    {
        $days = max(1, $days);
        $localStats = $stats ?? [
            'companies_processed' => 1,
            'draws_created' => 0,
            'draws_skipped' => 0,
            'days_covered' => $days,
        ];

        $company = Company::query()->select(['id', 'timezone'])->findOrFail($companyId);
        $baseDate = $this->resolveBaseDate($forDate, (string) ($company->timezone ?: 'America/Santo_Domingo'));

        for ($offset = 0; $offset < $days; $offset++) {
            $targetDate = $baseDate->copy()->addDays($offset)->toDateString();
            $this->generateForCompanyDate($companyId, $targetDate, $localStats);
        }

        if ($stats !== null) {
            $stats = $localStats;
        }

        return $localStats;
    }

    /**
     * @param  array{companies_processed: int, draws_created: int, draws_skipped: int, days_covered: int}  $stats
     */
    private function generateForCompanyDate(int $companyId, string $drawDate, array &$stats): void
    {
        $lotteries = Lottery::query()
            ->where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->get(['id', 'code'])
            ->keyBy('code');

        foreach (DominicanLotteryCatalog::entries() as $item) {
            $lottery = $lotteries->get($item['code']);

            if (! $lottery) {
                continue;
            }

            $existed = Draw::query()
                ->where('company_id', $companyId)
                ->where('lottery_id', $lottery->id)
                ->whereDate('draw_date', $drawDate)
                ->where('scheduled_time', $item['time'])
                ->exists();

            if ($existed) {
                $stats['draws_skipped']++;

                continue;
            }

            Draw::create([
                'company_id' => $companyId,
                'lottery_id' => $lottery->id,
                'draw_date' => $drawDate,
                'open_time' => '08:00',
                'scheduled_time' => $item['time'],
                'name' => $item['draw'],
                'close_time' => $item['time'],
                'status' => 'OPEN',
            ]);

            $stats['draws_created']++;
        }
    }

    private function resolveBaseDate(?string $forDate, string $timezone): Carbon
    {
        if ($forDate !== null) {
            return Carbon::parse($forDate)->startOfDay();
        }

        return Carbon::now($timezone)->startOfDay();
    }
}
