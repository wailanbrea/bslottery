<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PrinterConfig;
use App\Services\Audit\AuditService;
use App\Support\Printing\PrinterTargetGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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

        // Codigo de aprovisionamiento de la sucursal activa (URL + token, base64) para pegar en el conector.
        $provisionCode = null;
        if ($branchId && auth()->user()->hasPermission('printers.configure')) {
            $branch = \App\Models\Branch::where('company_id', $companyId)->find($branchId);
            if ($branch) {
                $token = $this->ensureEnrollToken($branch);
                $serverUrl = rtrim($request->getSchemeAndHttpHost(), '/');
                $provisionCode = base64_encode(json_encode([
                    'u' => $serverUrl,
                    't' => $token,
                ], JSON_UNESCAPED_SLASHES));
            }
        }

        return view('admin.printers.index', compact('printers', 'provisionCode'));
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
            'make_default' => 'nullable|boolean',
            'name' => 'required|string|max:150',
            'printer_type' => 'required|in:THERMAL,NORMAL',
            'connection_type' => 'required|in:USB,NETWORK,WINDOWS_SHARED,BLUETOOTH,PRINT_CONNECTOR',
            'paper_width' => 'required|in:58MM,80MM,88MM',
            'printing_mode' => 'nullable|in:RAW_ESCPOS',
            'auto_cut' => 'nullable|boolean',
            'show_logo' => 'nullable|boolean',
            'show_qr' => 'nullable|boolean',
            'show_phone' => 'nullable|boolean',
            'show_address' => 'nullable|boolean',
            'show_potential_prize' => 'nullable|boolean',
            'footer_text' => 'nullable|string|max:1500',
            'open_cash_drawer' => 'nullable|boolean',
            'print_copies' => 'nullable|integer|min:1|max:5',
            'printer_identifier' => 'required|string|max:255',
            'status' => 'sometimes|in:ACTIVE,INACTIVE',
        ]);

        $data['company_id'] = session('active_company_id');
        $data['printing_mode'] ??= 'RAW_ESCPOS';
        $data['auto_cut'] = (bool) ($data['auto_cut'] ?? true);
        $makeDefault = (bool) ($data['make_default'] ?? false);
        unset($data['make_default']);
        $data['show_logo'] = (bool) ($data['show_logo'] ?? false);
        $data['show_qr'] = (bool) ($data['show_qr'] ?? true);
        $data['show_phone'] = (bool) ($data['show_phone'] ?? true);
        $data['show_address'] = (bool) ($data['show_address'] ?? false);
        $data['show_potential_prize'] = (bool) ($data['show_potential_prize'] ?? false);
        $data['open_cash_drawer'] = (bool) ($data['open_cash_drawer'] ?? false);
        $data['print_copies'] = (int) ($data['print_copies'] ?? 1);
        $this->assertAllowedPrinterIdentifier($data);

        $printer = PrinterConfig::create($data);
        if ($makeDefault) {
            $this->makePrinterDefault($printer);
        }

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
            'make_default' => 'nullable|boolean',
            'name' => 'required|string|max:150',
            'printer_type' => 'required|in:THERMAL,NORMAL',
            'connection_type' => 'required|in:USB,NETWORK,WINDOWS_SHARED,BLUETOOTH,PRINT_CONNECTOR',
            'paper_width' => 'required|in:58MM,80MM,88MM',
            'printing_mode' => 'nullable|in:RAW_ESCPOS',
            'auto_cut' => 'nullable|boolean',
            'show_logo' => 'nullable|boolean',
            'show_qr' => 'nullable|boolean',
            'show_phone' => 'nullable|boolean',
            'show_address' => 'nullable|boolean',
            'show_potential_prize' => 'nullable|boolean',
            'footer_text' => 'nullable|string|max:1500',
            'open_cash_drawer' => 'nullable|boolean',
            'print_copies' => 'nullable|integer|min:1|max:5',
            'printer_identifier' => 'required|string|max:255',
            'status' => 'sometimes|in:ACTIVE,INACTIVE',
        ]);

        $oldValues = $printer->toArray();
        $data['printing_mode'] ??= 'RAW_ESCPOS';
        $data['auto_cut'] = (bool) ($data['auto_cut'] ?? true);
        $makeDefault = (bool) ($data['make_default'] ?? false);
        unset($data['make_default']);
        $data['show_logo'] = (bool) ($data['show_logo'] ?? false);
        $data['show_qr'] = (bool) ($data['show_qr'] ?? true);
        $data['show_phone'] = (bool) ($data['show_phone'] ?? true);
        $data['show_address'] = (bool) ($data['show_address'] ?? false);
        $data['show_potential_prize'] = (bool) ($data['show_potential_prize'] ?? false);
        $data['open_cash_drawer'] = (bool) ($data['open_cash_drawer'] ?? false);
        $data['print_copies'] = (int) ($data['print_copies'] ?? 1);
        $this->assertAllowedPrinterIdentifier($data);
        $printer->update($data);
        if ($makeDefault) {
            $this->makePrinterDefault($printer->fresh());
        }

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

    public function destroy(PrinterConfig $printer): RedirectResponse
    {
        Gate::authorize('update', $printer);
        abort_unless((int) $printer->company_id === (int) session('active_company_id'), 403);

        $name = $printer->name;
        $oldValues = $printer->toArray();

        try {
            DB::transaction(function () use ($printer, $name, $oldValues): void {
                // Si es la impresora por defecto de alguna sucursal, limpiar la referencia.
                Branch::where('default_printer_id', $printer->id)->update(['default_printer_id' => null]);

                $printer->delete(); // print_jobs.printer_config_id queda en null (nullOnDelete)

                app(AuditService::class)->record(
                    module: 'Printers',
                    action: 'deleted',
                    auditable: $printer,
                    description: "Impresora {$name} eliminada.",
                    oldValues: $oldValues,
                );
            });
        } catch (Throwable $e) {
            Log::error('No se pudo eliminar la impresora.', [
                'printer_id' => $printer->id,
                'company_id' => $printer->company_id,
                'branch_id' => $printer->branch_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.printers.index')
                ->with('error', "No se pudo eliminar la impresora {$name}. Intenta de nuevo.");
        }

        return redirect()->route('admin.printers.index')->with('status', "Impresora {$name} eliminada.");
    }

    public function makeDefault(PrinterConfig $printer): RedirectResponse
    {
        Gate::authorize('update', $printer);
        abort_unless((int) $printer->company_id === (int) session('active_company_id'), 403);

        $this->makePrinterDefault($printer);

        return back()->with('status', "La impresora {$printer->name} ahora es la principal.");
    }

    public function queue(Request $request): View
    {
        Gate::authorize('viewAny', PrinterConfig::class);

        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');

        $printers = PrinterConfig::where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get();

        $printerId = $request->integer('printer_config_id') ?: null;

        $jobs = \App\Models\PrintJob::with(['ticket:id,ticket_number', 'printerConfig:id,name,terminal_name'])
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($printerId, fn ($q) => $q->where('printer_config_id', $printerId))
            ->orderByRaw("FIELD(status, 'PENDING', 'PROCESSING', 'FAILED', 'PRINTED')")
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('admin.printers.queue', compact('printers', 'jobs', 'printerId'));
    }

    public function retryJob(\App\Models\PrintJob $job): RedirectResponse
    {
        Gate::authorize('create', PrinterConfig::class);
        abort_unless((int) $job->company_id === (int) session('active_company_id'), 403);

        if (in_array($job->status, ['FAILED', 'PROCESSING'], true)) {
            $job->update(['status' => 'PENDING', 'error_message' => null, 'printed_at' => null]);
        }

        return back()->with('status', "Trabajo #{$job->id} reencolado.");
    }

    public function clearQueue(Request $request): RedirectResponse
    {
        Gate::authorize('create', PrinterConfig::class);

        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');
        $printerId = $request->integer('printer_config_id') ?: null;

        $affected = \App\Models\PrintJob::where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($printerId, fn ($q) => $q->where('printer_config_id', $printerId))
            ->whereIn('status', ['PENDING', 'PROCESSING'])
            ->update(['status' => 'FAILED', 'error_message' => 'Cancelado desde la cola de impresion.']);

        return back()->with('status', "Se cancelaron {$affected} trabajo(s) pendiente(s).");
    }

    public function purgeQueue(Request $request): RedirectResponse
    {
        Gate::authorize('create', PrinterConfig::class);

        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');
        $printerId = $request->integer('printer_config_id') ?: null;

        $affected = \App\Models\PrintJob::where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($printerId, fn ($q) => $q->where('printer_config_id', $printerId))
            ->delete();

        return back()->with('status', "Se eliminaron {$affected} trabajo(s) de la cola.");
    }

    public function regenerateEnrollToken(): RedirectResponse
    {
        Gate::authorize('create', PrinterConfig::class);

        $branch = $this->activeBranchForEnroll();
        $branch->forceFill(['connector_enroll_token' => \Illuminate\Support\Str::random(48)])->save();

        return back()->with('status', 'Token de instalacion regenerado para esta sucursal.');
    }

    private function activeBranchForEnroll(): \App\Models\Branch
    {
        $branchId = session('active_branch_id');
        abort_if(! $branchId, 422, 'Selecciona una sucursal para generar el instalador auto-configurado.');

        $branch = \App\Models\Branch::where('company_id', session('active_company_id'))->find($branchId);
        abort_if(! $branch, 404, 'Sucursal no encontrada.');

        return $branch;
    }

    private function ensureEnrollToken(\App\Models\Branch $branch): string
    {
        if (! $branch->connector_enroll_token) {
            $branch->forceFill(['connector_enroll_token' => \Illuminate\Support\Str::random(48)])->save();
        }

        return $branch->connector_enroll_token;
    }

    private function makePrinterDefault(PrinterConfig $printer): void
    {
        if ((string) $printer->status !== 'ACTIVE') {
            return;
        }

        if ($printer->terminal_key) {
            PrinterConfig::where('company_id', $printer->company_id)
                ->where('terminal_key', $printer->terminal_key)
                ->update(['is_default' => false]);

            $printer->forceFill(['is_default' => true])->save();
            return;
        }

        $printer->forceFill(['is_default' => false])->save();

        $branchId = $printer->branch_id ?: (int) session('active_branch_id');
        if (! $branchId) {
            return;
        }

        $branch = Branch::where('company_id', $printer->company_id)->find($branchId);
        if ($branch && (int) $branch->default_printer_id !== (int) $printer->id) {
            $branch->forceFill(['default_printer_id' => $printer->id])->save();
        }
    }

    private function assertAllowedPrinterIdentifier(array $data): void
    {
        if (($data['connection_type'] ?? null) !== 'PRINT_CONNECTOR') {
            return;
        }

        if (! PrinterTargetGuard::isAllowedForConnector($data['printer_identifier'] ?? null)) {
            throw ValidationException::withMessages([
                'printer_identifier' => PrinterTargetGuard::connectorValidationMessage(),
            ]);
        }
    }
}
