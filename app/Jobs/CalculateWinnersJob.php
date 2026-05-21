<?php

namespace App\Jobs;

use App\Models\Draw;
use App\Models\User;
use App\Services\Results\WinnerCalculationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CalculateWinnersJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $drawId,
        public int $userId,
    ) {}

    public function handle(WinnerCalculationService $winnerCalculationService): void
    {
        $draw = Draw::findOrFail($this->drawId);
        $user = User::findOrFail($this->userId);

        $winnerCalculationService->calculate($draw, $user);
    }
}
