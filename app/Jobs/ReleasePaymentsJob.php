<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Draw;
use App\Models\User;
use App\Services\Results\WinnerCalculationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Autoriza y libera los pagos de un sorteo en cola. Procesa la actualizacion
 * masiva de WinnerTicket (PENDING_RELEASE -> RELEASED) sin bloquear la peticion
 * del admin que aprueba.
 */
class ReleasePaymentsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $drawId,
        public int $userId,
        public ?string $notes = null,
    ) {}

    public function handle(WinnerCalculationService $service): void
    {
        $draw = Draw::findOrFail($this->drawId);
        $user = User::findOrFail($this->userId);

        $service->authorizePayments($draw, $user, $this->notes);
    }
}
