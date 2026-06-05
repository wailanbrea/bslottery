<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrintJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrintJobController extends Controller
{
    private const PROCESSING_TIMEOUT_MINUTES = 2;

    /**
     * Returns pending print jobs for the authenticated user's branch.
     * Called by the Print Agent JS bridge on page load.
     */
    public function pending(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $terminalKey = trim((string) $request->header('X-Terminal-Key', $request->query('terminal_key', '')));

        if ($terminalKey === '') {
            return response()->json([]);
        }

        // pending() se consulta cada ~2s por cada caja. Un fallo transitorio de BD (deadlock por el
        // lockForUpdate, conexion caida, etc.) NO debe responder 500 y trabar la impresion:
        // reintentamos los deadlocks y, ante cualquier otro fallo, devolvemos cola vacia para que el
        // conector reintente en el siguiente ciclo sin atascarse.
        try {
            $staleThreshold = now()->subMinutes(self::PROCESSING_TIMEOUT_MINUTES);

            PrintJob::query()
                ->where('company_id', $user->company_id)
                ->when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('status', 'PROCESSING')
                ->where('updated_at', '<', $staleThreshold)
                ->whereHas('printerConfig', function ($query) use ($terminalKey): void {
                    $query->where('status', 'ACTIVE')
                        ->where('terminal_key', $terminalKey);
                })
                ->update([
                    'status' => 'FAILED',
                    'error_message' => 'Tiempo de confirmacion agotado.',
                    'updated_at' => now(),
                ]);

            $jobs = DB::transaction(function () use ($user, $terminalKey) {
                $jobs = PrintJob::query()
                    ->where('company_id', $user->company_id)
                    ->when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                    ->where('status', 'PENDING')
                    ->whereHas('printerConfig', function ($query) use ($terminalKey): void {
                        $query->where('status', 'ACTIVE')
                            ->where('terminal_key', $terminalKey);
                    })
                    ->with(['printerConfig', 'ticket:id,ticket_number'])
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->limit(20)
                    ->get(['id', 'uuid', 'type', 'content', 'printer_config_id', 'ticket_id', 'created_at']);

                if ($jobs->isNotEmpty()) {
                    PrintJob::query()
                        ->whereIn('id', $jobs->pluck('id'))
                        ->update([
                            'status' => 'PROCESSING',
                            'attempts' => DB::raw('attempts + 1'),
                            'updated_at' => now(),
                        ]);

                    $jobs->each(function (PrintJob $job): void {
                        $job->status = 'PROCESSING';
                        $job->attempts = (int) $job->attempts + 1;
                    });
                }

                return $jobs;
            }, 5);

            return response()->json($jobs->map(fn ($job) => [
                'uuid'             => $job->uuid,
                'type'             => $job->type,
                'content'          => $job->content,
                'ticket_number'    => $job->ticket?->ticket_number,
                'printer_name'     => $job->printerConfig?->printer_identifier,
                'connection_type'  => $job->printerConfig?->connection_type ?? 'USB',
                'paper_width'      => $job->printerConfig?->paper_width ?? '58MM',
                'printing_mode'    => $job->printerConfig?->printing_mode ?? 'RAW_ESCPOS',
                'auto_cut'         => (bool) ($job->printerConfig?->auto_cut ?? true),
                'terminal_key'     => $job->printerConfig?->terminal_key,
            ]));
        } catch (\Throwable $e) {
            Log::warning('print-jobs/pending fallo transitorio; devolviendo cola vacia', [
                'terminal_key' => $terminalKey,
                'error' => $e->getMessage(),
            ]);

            return response()->json([]);
        }
    }

    /**
     * Marks a print job as completed (printed) or failed.
     * Called by the Print Agent JS bridge after dispatch.
     */
    public function ack(Request $request, string $uuid): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $job = PrintJob::where('uuid', $uuid)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        $data = $request->validate([
            'status'        => 'required|in:PRINTED,FAILED',
            'error_message' => 'nullable|string|max:500',
        ]);

        $job->update([
            'status'        => $data['status'],
            'error_message' => $data['error_message'] ?? null,
            'printed_at'    => $data['status'] === 'PRINTED' ? now() : null,
        ]);

        return response()->json(['ok' => true]);
    }
}
