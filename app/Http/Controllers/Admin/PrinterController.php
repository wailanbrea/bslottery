<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PrinterConfig;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;
use Illuminate\View\View;

class PrinterController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', PrinterConfig::class);

        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');

        $printers = PrinterConfig::with('branch')
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->paginate(20);

        $qzSilentReady = is_file((string) config('print.qz_digital_certificate_path'))
            && is_file((string) config('print.qz_private_key_path'));

        return view('admin.printers.index', compact('printers', 'qzSilentReady'));
    }

    public function create(): View
    {
        Gate::authorize('create', PrinterConfig::class);

        $companyId = session('active_company_id');
        $branches = Branch::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();

        return view('admin.printers.form', ['printer' => new PrinterConfig, 'branches' => $branches]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', PrinterConfig::class);

        $data = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'terminal_key' => 'nullable|string|max:100',
            'terminal_name' => 'nullable|string|max:150',
            'name' => 'required|string|max:150',
            'printer_type' => 'required|in:THERMAL,NORMAL',
            'connection_type' => 'required|in:USB,NETWORK,WINDOWS_SHARED,BLUETOOTH,QZ_TRAY,PRINT_CONNECTOR',
            'paper_width' => 'required|in:58MM,80MM,88MM',
            'printing_mode' => 'nullable|in:RAW_ESCPOS',
            'auto_cut' => 'nullable|boolean',
            'printer_identifier' => 'required|string|max:255',
            'status' => 'sometimes|in:ACTIVE,INACTIVE',
        ]);

        $data['company_id'] = session('active_company_id');
        $data['printing_mode'] ??= 'RAW_ESCPOS';
        $data['auto_cut'] = (bool) ($data['auto_cut'] ?? true);

        $printer = PrinterConfig::create($data);

        app(AuditService::class)->record(
            module: 'Printers', action: 'created', auditable: $printer,
            description: "Impresora {$printer->name} ({$printer->connection_type}) creada.",
            newValues: $printer->toArray(),
        );

        return redirect()->route('admin.printers.index')->with('status', 'Impresora creada.');
    }

    public function edit(PrinterConfig $printer): View
    {
        Gate::authorize('update', $printer);

        $companyId = session('active_company_id');
        $branches = Branch::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();

        return view('admin.printers.form', compact('printer', 'branches'));
    }

    public function update(Request $request, PrinterConfig $printer): RedirectResponse
    {
        Gate::authorize('update', $printer);

        $data = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'terminal_key' => 'nullable|string|max:100',
            'terminal_name' => 'nullable|string|max:150',
            'name' => 'required|string|max:150',
            'printer_type' => 'required|in:THERMAL,NORMAL',
            'connection_type' => 'required|in:USB,NETWORK,WINDOWS_SHARED,BLUETOOTH,QZ_TRAY,PRINT_CONNECTOR',
            'paper_width' => 'required|in:58MM,80MM,88MM',
            'printing_mode' => 'nullable|in:RAW_ESCPOS',
            'auto_cut' => 'nullable|boolean',
            'printer_identifier' => 'required|string|max:255',
            'status' => 'sometimes|in:ACTIVE,INACTIVE',
        ]);

        $oldValues = $printer->toArray();
        $data['printing_mode'] ??= 'RAW_ESCPOS';
        $data['auto_cut'] = (bool) ($data['auto_cut'] ?? true);
        $printer->update($data);

        app(AuditService::class)->record(
            module: 'Printers', action: 'updated', auditable: $printer,
            description: "Impresora {$printer->name} actualizada.",
            oldValues: $oldValues, newValues: $printer->toArray(),
        );

        return redirect()->route('admin.printers.index')->with('status', 'Impresora actualizada.');
    }

    public function test(PrinterConfig $printer): RedirectResponse
    {
        Gate::authorize('update', $printer);

        $job = \App\Models\PrintJob::create([
            'company_id' => $printer->company_id,
            'branch_id' => $printer->branch_id ?: (int) session('active_branch_id'),
            'printer_config_id' => $printer->id,
            'type' => 'TEST',
            'content' => "BSLottery - Prueba de impresion\n" . str_repeat('-', $printer->paper_width === '58MM' ? 32 : 56) . "\nImpresora: {$printer->name}\nTipo: {$printer->printer_type}\nConexion: {$printer->connection_type}\nPapel: {$printer->paper_width}\n" . str_repeat('-', $printer->paper_width === '58MM' ? 32 : 56) . "\n\n\n",
            'status' => 'PENDING',
        ]);

        $printer->forceFill(['last_test_at' => now()])->save();

        app(AuditService::class)->record(
            module: 'Printers', action: 'test_print', auditable: $printer,
            description: "Prueba de impresion en {$printer->name}.",
        );

        return redirect()->route('admin.printers.index')->with('status', "Prueba enviada a {$printer->name}. Job #{$job->id}");
    }

    public function startAgent(Request $request): JsonResponse
    {
        Gate::authorize('create', PrinterConfig::class);

        $agentUrl = (string) config('print.agent_url', 'http://127.0.0.1:8765');
        if ($this->agentReachable($agentUrl)) {
            return $this->buildAgentStartResponse($agentUrl, true);
        }

        return response()->json([
            'success' => false,
            'agent_running' => false,
            'ready' => false,
            'message' => 'El Print Agent no esta activo. Ejecuta el launcher descargable para iniciarlo, diagnosticar Windows y dejarlo listo.',
        ], 422);
    }

    public function downloadAgentScript(): Response
    {
        Gate::authorize('create', PrinterConfig::class);

        $path = public_path('downloads/scritplottery.ps1');
        abort_unless(is_file($path), 404);

        $content = file_get_contents($path);
        abort_unless(is_string($content), 500);

        $token = str_replace("'", "''", (string) config('print.agent_token', ''));
        $content = str_replace('__BSLOTTERY_AGENT_TOKEN__', $token, $content);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="scritplottery.ps1"',
        ]);
    }

    public function downloadAgentLauncher(): Response
    {
        Gate::authorize('create', PrinterConfig::class);

        $content = "@echo off\r\n"
            ."setlocal\r\n"
            .'set "SCRIPT_DIR=%~dp0"'."\r\n"
            .'powershell.exe -NoExit -ExecutionPolicy Bypass -File "%SCRIPT_DIR%scritplottery.ps1" -Port 8765'."\r\n"
            ."endlocal\r\n";

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="start-scritplottery.bat"',
        ]);
    }

    private function agentReachable(string $agentUrl): bool
    {
        $host = (string) (parse_url($agentUrl, PHP_URL_HOST) ?: '127.0.0.1');
        $port = (int) (parse_url($agentUrl, PHP_URL_PORT) ?: 8765);

        $socket = @fsockopen($host, $port, $errno, $errstr, 1.0);
        if (! $socket) {
            return false;
        }

        fclose($socket);

        return true;
    }

    private function buildAgentStartResponse(string $agentUrl, bool $alreadyRunning): JsonResponse
    {
        $diagnostics = $this->agentPrinterDiagnostics($agentUrl);

        if (! $diagnostics['ok']) {
            return response()->json([
                'success' => false,
                'already_running' => $alreadyRunning,
                'agent_running' => true,
                'ready' => false,
                'message' => $diagnostics['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'already_running' => $alreadyRunning,
            'agent_running' => true,
            'ready' => true,
            'message' => $diagnostics['message'],
            'printers_count' => $diagnostics['printers_count'],
        ]);
    }

    private function agentPrinterDiagnostics(string $agentUrl): array
    {
        $token = (string) config('print.agent_token', '');
        $options = [
            'http' => [
                'method' => 'GET',
                'timeout' => 3,
                'ignore_errors' => true,
                'header' => implode("\r\n", array_filter([
                    'Accept: application/json',
                    $token !== '' ? 'Authorization: Bearer '.$token : null,
                ])),
            ],
        ];

        try {
            $context = stream_context_create($options);
            $body = @file_get_contents(rtrim($agentUrl, '/').'/api/printers', false, $context);
            $payload = is_string($body) ? json_decode($body, true) : null;
        } catch (Throwable) {
            $payload = null;
        }

        if (! is_array($payload)) {
            return [
                'ok' => false,
                'message' => 'El Print Agent inicio, pero no devolvio una respuesta valida al consultar impresoras.',
                'printers_count' => 0,
            ];
        }

        $printers = $payload['printers'] ?? [];
        $error = trim((string) ($payload['error'] ?? ''));

        if ($error !== '') {
            return [
                'ok' => false,
                'message' => 'El Print Agent inicio, pero no puede listar impresoras: '.$error,
                'printers_count' => is_array($printers) ? count($printers) : 0,
            ];
        }

        if (! is_array($printers) || count($printers) === 0) {
            return [
                'ok' => false,
                'message' => 'El Print Agent inicio, pero no encontro impresoras en Windows.',
                'printers_count' => 0,
            ];
        }

        return [
            'ok' => true,
            'message' => 'Print Agent listo. Impresoras detectadas: '.count($printers).'.',
            'printers_count' => count($printers),
        ];
    }
}
