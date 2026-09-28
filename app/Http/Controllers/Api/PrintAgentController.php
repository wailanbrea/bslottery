<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrinterConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrintAgentController extends Controller
{
    public function config(Request $request): JsonResponse
    {
        $user = $request->user();
        $activeBranchId = $request->hasSession() ? $request->session()->get('active_branch_id') : null;
        $branchId = (int) ($activeBranchId ?: $user->branch_id);

        $printers = PrinterConfig::query()
            ->where('company_id', $user->company_id)
            ->where('status', 'ACTIVE')
            ->when($branchId, fn ($query) => $query->where(function ($branchQuery) use ($branchId): void {
                $branchQuery->where('branch_id', $branchId)->orWhereNull('branch_id');
            }))
            ->whereNotNull('terminal_key')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'terminal_key', 'terminal_name', 'name', 'printer_identifier', 'paper_width', 'is_default']);

        $default = $printers->first(fn (PrinterConfig $printer): bool => (bool) $printer->is_default)
            ?: $printers->first();

        return response()->json([
            'terminal' => $default ? [
                'key' => $default->terminal_key,
                'name' => $default->terminal_name ?: $default->name,
                'printer' => $default->printer_identifier,
                'paper_width' => $default->paper_width,
            ] : null,
            'printers' => $printers->map(fn (PrinterConfig $printer): array => [
                'id' => $printer->id,
                'key' => $printer->terminal_key,
                'name' => $printer->terminal_name ?: $printer->name,
                'printer' => $printer->printer_identifier,
                'paper_width' => $printer->paper_width,
            ])->values(),
        ]);
    }
}
