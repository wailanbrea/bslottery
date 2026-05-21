<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('license:validate')->everyThirtyMinutes()->withoutOverlapping();
Schedule::command('monitoring:scan-branches')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('draws:generate-daily')->dailyAt('00:01')->withoutOverlapping();
Schedule::command('backup:run --only-files')->dailyAt('02:00')->withoutOverlapping()->appendOutputTo(storage_path('logs/backup.log'));
Schedule::command('backup:clean')->dailyAt('02:30')->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=168')->weeklyOn(0, '03:00');
