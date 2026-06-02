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

        return view('admin.printers.index', compact('printers'));
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
            'connection_type' => 'required|in:USB,NETWORK,WINDOWS_SHARED,BLUETOOTH,PRINT_CONNECTOR',
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
            'connection_type' => 'required|in:USB,NETWORK,WINDOWS_SHARED,BLUETOOTH,PRINT_CONNECTOR',
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


    public function downloadConnectorInstallScript(): Response
    {
        Gate::authorize('create', PrinterConfig::class);

        $url = asset('downloads/BSolutionsPrintConnectorSetup.exe');

        $content = "@echo off\r\n"
            ."setlocal\r\n"
            .'set "URL='.$url.'"'."\r\n"
            .'set "DEST=%TEMP%\BSolutionsPrintConnectorSetup.exe"'."\r\n"
            ."echo Descargando BSolutions Print Connector...\r\n"
            .'powershell -NoProfile -ExecutionPolicy Bypass -Command "Invoke-WebRequest -Uri \'%URL%\' -OutFile \'%DEST%\' -UseBasicParsing"'."\r\n"
            ."echo Instalando...\r\n"
            .'"%DEST%" /VERYSILENT /SUPPRESSMSGBOXES /NORESTART'."\r\n"
            .'del "%DEST%" >nul 2>&1'."\r\n"
            ."echo Listo. Abre BSolutions Print Connector desde el menu inicio.\r\n"
            ."pause\r\n";

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="instalar-print-connector.bat"',
        ]);
    }
}
