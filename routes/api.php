<?php

use App\Http\Controllers\Api\MobileController;
use App\Http\Controllers\Api\OfflineController;
use App\Http\Controllers\Api\PrintJobController;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

// Públicas — no requieren auth
Route::post('/login', [ApiController::class, 'login']);
Route::get('/lotteries', [ApiController::class, 'lotteries']);
Route::get('/draws', [ApiController::class, 'draws']);
Route::get('/bet-types', [ApiController::class, 'betTypes']);

// Protegidas — requieren token Sanctum + dispositivo no bloqueado
Route::middleware(['auth:sanctum', 'device.authorized'])->group(function (): void {
    // Bootstrap online
    Route::get('/bootstrap', [OfflineController::class, 'bootstrap']);

    // Tickets online
    Route::get('/tickets', [ApiController::class, 'tickets']);
    Route::get('/tickets/{ticket}', [ApiController::class, 'ticket']);
    Route::post('/tickets/preview', [ApiController::class, 'preview']);
    Route::post('/tickets', [ApiController::class, 'storeTicket']);
    Route::post('/tickets/{ticket}/reprint', [ApiController::class, 'reprint']);

    // Offline sync
    Route::post('/offline/session/open', [OfflineController::class, 'openSession']);
    Route::get('/offline/bootstrap', [OfflineController::class, 'sessionBootstrap']);
    Route::post('/offline/sync', [OfflineController::class, 'sync']);
    Route::get('/offline/conflicts', [OfflineController::class, 'conflicts']);
    Route::post('/offline/conflicts/{conflict}/resolve', [OfflineController::class, 'resolveConflict']);

    // Print Agent bridge
    Route::get('/print-jobs/pending', [PrintJobController::class, 'pending']);
    Route::post('/print-jobs/{uuid}/ack', [PrintJobController::class, 'ack']);
});

// ── Mobile (Android app) ─────────────────────────────────────────────────────
Route::post('/mobile/login', [MobileController::class, 'login']);

Route::middleware(['auth:sanctum', 'device.authorized'])->prefix('mobile')->group(function (): void {
    Route::post('/logout', [MobileController::class, 'logout']);
    Route::get('/sync/data', [MobileController::class, 'syncData']);
    Route::get('/tickets', [MobileController::class, 'tickets']);
    Route::get('/tickets/lookup', [MobileController::class, 'lookupTicket']);
    Route::get('/tickets/check-limit', [MobileController::class, 'checkLimit']);
    Route::post('/tickets/batch', [MobileController::class, 'storeTicketsBatch']);
    Route::post('/tickets', [MobileController::class, 'storeTicket']);
    Route::get('/tickets/{uuid}', [MobileController::class, 'ticket']);

    // Premios
    Route::get('/tickets/{uuid}/winners', [MobileController::class, 'ticketWinners']);
    Route::post('/tickets/{uuid}/pay-prize', [MobileController::class, 'payTicketPrize']);

    // Caja
    Route::get('/cash/status', [MobileController::class, 'cashStatus']);
    Route::post('/cash/open', [MobileController::class, 'cashOpen']);
    Route::post('/cash/close', [MobileController::class, 'cashClose']);
});
