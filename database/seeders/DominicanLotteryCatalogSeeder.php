<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Draw;
use App\Models\Lottery;
use App\Support\DominicanLotteryCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DominicanLotteryCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->runForDate(Carbon::today()->toDateString());
    }

    public function runForDate(string $date): void
    {
        Company::query()->select('id')->chunkById(100, function (Collection $companies) use ($date): void {
            foreach ($companies as $company) {
                $this->seedCompanyCatalog((int) $company->id, $date);
                $this->closeLegacyDemoDraws((int) $company->id, $date);
                $this->retireObsoleteLotteryCodes((int) $company->id, DominicanLotteryCatalog::retiredCodes());
                $this->closeDuplicateOpenDraws((int) $company->id, $date);
            }
        });
    }

    private function seedCompanyCatalog(int $companyId, string $drawDate): void
    {
        foreach (DominicanLotteryCatalog::entries() as $item) {
            $lottery = Lottery::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'code' => $item['code'],
                ],
                [
                    'name' => $item['name'],
                    'country' => $item['country'],
                    'status' => 'ACTIVE',
                ],
            );

            $this->firstOrCreateDraw(
                companyId: $companyId,
                lotteryId: (int) $lottery->id,
                drawDate: $drawDate,
                scheduledTime: $item['time'],
                name: $item['draw'],
            );
        }
    }

    private function firstOrCreateDraw(
        int $companyId,
        int $lotteryId,
        string $drawDate,
        string $scheduledTime,
        string $name,
    ): Draw {
        $existing = Draw::query()
            ->where('company_id', $companyId)
            ->where('lottery_id', $lotteryId)
            ->whereDate('draw_date', $drawDate)
            ->where('scheduled_time', $scheduledTime)
            ->first();

        if ($existing) {
            return $existing;
        }

        return Draw::create([
            'company_id' => $companyId,
            'lottery_id' => $lotteryId,
            'draw_date' => $drawDate,
            'open_time' => '08:00',
            'scheduled_time' => $scheduledTime,
            'name' => $name,
            'close_time' => $scheduledTime,
            'status' => 'OPEN',
        ]);
    }

    private function closeDuplicateOpenDraws(int $companyId, string $drawDate): void
    {
        Draw::query()
            ->where('company_id', $companyId)
            ->whereDate('draw_date', $drawDate)
            ->where('status', 'OPEN')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Draw $draw): string => $draw->lottery_id.'|'.$draw->draw_date->toDateString().'|'.$draw->scheduled_time)
            ->each(function (Collection $draws): void {
                $draws->skip(1)->each(function (Draw $draw): void {
                    $draw->update([
                        'status' => 'CLOSED',
                        'closed_at' => now(),
                    ]);
                });
            });
    }

    private function closeLegacyDemoDraws(int $companyId, string $drawDate): void
    {
        $legacyLottery = Lottery::query()
            ->where('company_id', $companyId)
            ->where('code', 'LOTNAC')
            ->first();

        if (! $legacyLottery) {
            return;
        }

        Draw::query()
            ->where('company_id', $companyId)
            ->where('lottery_id', $legacyLottery->id)
            ->whereDate('draw_date', $drawDate)
            ->where('status', 'OPEN')
            ->where('close_time', '23:59')
            ->whereIn('name', ['Sorteo 12:30 PM', 'Sorteo 6:00 PM'])
            ->update([
                'status' => 'CLOSED',
                'closed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Previous local catalog drafts are retired without physical deletes.
     *
     * @param  array<int, string>  $codes
     */
    private function retireObsoleteLotteryCodes(int $companyId, array $codes): void
    {
        Lottery::query()
            ->where('company_id', $companyId)
            ->whereIn('code', $codes)
            ->get()
            ->each(function (Lottery $lottery): void {
                $lottery->draws()
                    ->where('status', 'OPEN')
                    ->update([
                        'status' => 'CLOSED',
                        'closed_at' => now(),
                        'updated_at' => now(),
                    ]);

                if (! $lottery->ticketDetails()->exists()) {
                    $lottery->update(['status' => 'INACTIVE']);
                }
            });
    }
}
