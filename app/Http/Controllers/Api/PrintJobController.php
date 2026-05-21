<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrintJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrintJobController extends Controller
{
    /**
     * Returns pending print jobs for the authenticated user's branch.
     * Called by the Print Agent JS bridge on page load.
     */
    public function pending(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $jobs = PrintJob::where('company_id', $user->company_id)
            ->when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->where('status', 'PENDING')
            ->with('printerConfig')
            ->orderBy('created_at')
            ->limit(20)
            ->get(['id', 'uuid', 'type', 'content', 'printer_config_id', 'created_at']);

        return response()->json($jobs->map(fn ($job) => [
            'uuid'             => $job->uuid,
            'type'             => $job->type,
            'content'          => $job->content,
            'printer_name'     => $job->printerConfig?->printer_identifier,
            'connection_type'  => $job->printerConfig?->connection_type ?? 'USB',
            'paper_width'      => $job->printerConfig?->paper_width ?? '58MM',
        ]));
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

        $job->increment('attempts');
        $job->update([
            'status'        => $data['status'],
            'error_message' => $data['error_message'] ?? null,
            'printed_at'    => $data['status'] === 'PRINTED' ? now() : null,
        ]);

        return response()->json(['ok' => true]);
    }
}
