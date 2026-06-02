<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrinterConfig;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\Access\AuthorizationException;

class QzTrayController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function queuePage(): Response|\Illuminate\View\View
    {
        $this->authorizeQueueAccess();

        return response()->view('admin.printers.queue');
    }

    public function certificate(): Response
    {
        $this->authorizeQzAccess();

        $path = $this->resolvedDigitalCertificatePath();

        if ($path === '' || ! is_file($path)) {
            return response('', 204);
        }

        $certificate = file_get_contents($path);
        if (! is_string($certificate) || $certificate === '') {
            return response('', 204);
        }

        return response($certificate, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function sign(Request $request): Response
    {
        $this->authorizeQzAccess();

        $payload = (string) ($request->input('request') ?? '');
        abort_if($payload === '', 422, 'La solicitud a firmar esta vacia.');

        $privateKeyPath = (string) config('print.qz_private_key_path', '');
        abort_if($privateKeyPath === '' || ! is_file($privateKeyPath), 422, 'La firma de QZ Tray no esta configurada.');

        $privateKey = openssl_pkey_get_private('file://'.$privateKeyPath);
        abort_if($privateKey === false, 500, 'No se pudo cargar la clave privada de QZ Tray.');

        $signature = '';
        $signed = openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA512);
        if (is_object($privateKey)) {
            openssl_pkey_free($privateKey);
        }

        abort_unless($signed, 500, 'No se pudo firmar la solicitud de QZ Tray.');

        return response(base64_encode($signature), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function currentTerminal(Request $request): JsonResponse
    {
        $this->authorizeQueueAccess();

        $data = $request->validate([
            'terminal_key' => 'required|string|max:100',
        ]);

        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');

        $printer = PrinterConfig::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('terminal_key', $data['terminal_key'])
            ->latest('updated_at')
            ->first();

        return response()->json([
            'success' => true,
            'printer' => $printer ? [
                'id' => $printer->id,
                'name' => $printer->name,
                'printer_name' => $printer->printer_identifier,
                'paper_width' => $printer->paper_width,
                'printing_mode' => $printer->printing_mode,
                'auto_cut' => (bool) $printer->auto_cut,
                'terminal_key' => $printer->terminal_key,
                'terminal_name' => $printer->terminal_name,
                'status' => $printer->status,
                'last_test_at' => optional($printer->last_test_at)?->toDateTimeString(),
            ] : null,
        ]);
    }

    public function saveCurrentTerminal(Request $request): JsonResponse
    {
        $this->authorizePrinterConfiguration();

        $data = $request->validate([
            'terminal_key' => 'required|string|max:100',
            'terminal_name' => 'required|string|max:150',
            'printer_name' => 'required|string|max:255',
            'paper_width' => 'required|in:58MM,80MM,88MM',
            'printing_mode' => 'required|in:RAW_ESCPOS',
            'auto_cut' => 'required|boolean',
        ]);

        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');

        $printer = PrinterConfig::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('terminal_key', $data['terminal_key'])
            ->first();

        $payload = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'terminal_key' => $data['terminal_key'],
            'terminal_name' => $data['terminal_name'],
            'name' => 'QZ '.$data['terminal_name'],
            'printer_type' => 'THERMAL',
            'connection_type' => 'QZ_TRAY',
            'paper_width' => $data['paper_width'],
            'printing_mode' => $data['printing_mode'],
            'auto_cut' => $data['auto_cut'],
            'printer_identifier' => $data['printer_name'],
            'status' => 'ACTIVE',
        ];

        if ($printer) {
            $oldValues = $printer->toArray();
            $printer->update($payload);

            app(AuditService::class)->record(
                module: 'Printers',
                action: 'updated',
                auditable: $printer,
                description: "Impresora QZ actualizada para terminal {$printer->terminal_name}.",
                oldValues: $oldValues,
                newValues: $printer->toArray(),
            );
        } else {
            $printer = PrinterConfig::create($payload);

            app(AuditService::class)->record(
                module: 'Printers',
                action: 'created',
                auditable: $printer,
                description: "Impresora QZ creada para terminal {$printer->terminal_name}.",
                newValues: $printer->toArray(),
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Impresora de esta terminal guardada.',
            'printer' => [
                'id' => $printer->id,
                'name' => $printer->name,
                'printer_name' => $printer->printer_identifier,
                'paper_width' => $printer->paper_width,
                'printing_mode' => $printer->printing_mode,
                'auto_cut' => (bool) $printer->auto_cut,
                'terminal_key' => $printer->terminal_key,
                'terminal_name' => $printer->terminal_name,
            ],
        ]);
    }

    public function markTested(Request $request): JsonResponse
    {
        $this->authorizePrinterConfiguration();

        $data = $request->validate([
            'terminal_key' => 'required|string|max:100',
        ]);

        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');

        $printer = PrinterConfig::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('terminal_key', $data['terminal_key'])
            ->latest('updated_at')
            ->firstOrFail();

        $printer->forceFill(['last_test_at' => now()])->save();

        return response()->json([
            'success' => true,
            'last_test_at' => optional($printer->last_test_at)?->toDateTimeString(),
        ]);
    }

    public function queueData(Request $request): JsonResponse
    {
        $this->authorizeQueueAccess();

        $data = $request->validate([
            'terminal_key' => 'required|string|max:100',
        ]);

        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');

        $printer = PrinterConfig::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('terminal_key', $data['terminal_key'])
            ->latest('updated_at')
            ->first();

        $jobs = \App\Models\PrintJob::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($printer, fn ($query) => $query->where('printer_config_id', $printer->id), fn ($query) => $query->whereRaw('1 = 0'))
            ->with('ticket:id,ticket_number')
            ->orderByRaw("FIELD(status, 'PENDING', 'PROCESSING', 'FAILED', 'PRINTED')")
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'printer' => $printer ? [
                'id' => $printer->id,
                'name' => $printer->name,
                'printer_name' => $printer->printer_identifier,
                'paper_width' => $printer->paper_width,
                'terminal_key' => $printer->terminal_key,
                'terminal_name' => $printer->terminal_name,
                'last_test_at' => optional($printer->last_test_at)?->toDateTimeString(),
            ] : null,
            'jobs' => $jobs->map(fn (\App\Models\PrintJob $job) => [
                'id' => $job->id,
                'uuid' => $job->uuid,
                'ticket_number' => $job->ticket?->ticket_number,
                'type' => $job->type,
                'status' => $job->status,
                'attempts' => $job->attempts,
                'error_message' => $job->error_message,
                'created_at' => optional($job->created_at)?->format('Y-m-d H:i:s'),
                'printed_at' => optional($job->printed_at)?->format('Y-m-d H:i:s'),
            ]),
        ]);
    }

    public function uploadCredentials(Request $request): JsonResponse
    {
        $this->authorizePrinterConfiguration();

        $data = $request->validate([
            'digital_certificate' => 'required|file|max:256',
            'private_key' => 'required|file|max:256',
        ]);

        /** @var UploadedFile $digitalCertificate */
        $digitalCertificate = $data['digital_certificate'];
        /** @var UploadedFile $privateKey */
        $privateKey = $data['private_key'];

        abort_unless(
            str_ends_with(strtolower($digitalCertificate->getClientOriginalName()), 'digital-certificate.txt'),
            422,
            'El archivo del certificado debe llamarse digital-certificate.txt'
        );
        abort_unless(
            str_ends_with(strtolower($privateKey->getClientOriginalName()), 'private-key.pem'),
            422,
            'La clave privada debe llamarse private-key.pem'
        );

        $directory = storage_path('app/qz');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $digitalPath = $directory.DIRECTORY_SEPARATOR.'digital-certificate.txt';
        $privateKeyPath = $directory.DIRECTORY_SEPARATOR.'private-key.pem';

        $digitalContent = file_get_contents($digitalCertificate->getRealPath());
        $privateContent = file_get_contents($privateKey->getRealPath());

        abort_unless(is_string($digitalContent) && trim($digitalContent) !== '', 422, 'El certificado QZ está vacío.');
        abort_unless(is_string($privateContent) && str_contains($privateContent, 'PRIVATE KEY'), 422, 'La clave privada QZ no es válida.');

        file_put_contents($digitalPath, $digitalContent);
        file_put_contents($privateKeyPath, $privateContent);

        return response()->json([
            'success' => true,
            'message' => 'Credenciales de QZ Tray cargadas correctamente para esta instalación.',
            'digital_certificate_path' => $digitalPath,
            'private_key_path' => $privateKeyPath,
        ]);
    }

    public function downloadTrustedCertificate(): Response
    {
        $this->authorizePrinterConfiguration();

        $path = $this->resolvedDigitalCertificatePath();
        abort_if($path === '' || ! is_file($path), 404, 'No hay certificado QZ disponible para descargar.');

        $content = file_get_contents($path);
        abort_unless(is_string($content) && $content !== '', 404, 'No hay certificado QZ disponible para descargar.');

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="digital-certificate.txt"',
        ]);
    }

    public function downloadTrustScript(): Response
    {
        $this->authorizePrinterConfiguration();

        $content = <<<'BAT'
@echo off
setlocal
set "CERT=%~dp0digital-certificate.txt"

if not exist "%CERT%" (
  echo No se encontro digital-certificate.txt en esta carpeta.
  echo Coloca este BAT junto al certificado descargado desde BSLottery.
  pause
  exit /b 1
)

set "QZCLI=%ProgramFiles%\QZ Tray\qz-tray-console.exe"
if not exist "%QZCLI%" set "QZCLI=%ProgramFiles(x86)%\QZ Tray\qz-tray-console.exe"

if not exist "%QZCLI%" (
  echo No se encontro QZ Tray en Program Files.
  echo Instala QZ Tray primero y vuelve a ejecutar este archivo.
  pause
  exit /b 1
)

echo Registrando certificado en la lista confiable de QZ Tray...
"%QZCLI%" --whitelist "%CERT%"
set "ERR=%ERRORLEVEL%"

if NOT "%ERR%"=="0" (
  echo QZ Tray devolvio un error al intentar registrar el certificado.
  echo Si QZ Tray esta abierto, cierralo y vuelve a abrirlo.
  pause
  exit /b %ERR%
)

echo Certificado agregado en QZ Tray correctamente.
echo Reinicia QZ Tray si estaba abierto y luego prueba imprimir otra vez.
pause
BAT;

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="confiar-qz.bat"',
        ]);
    }

    public function clearPending(Request $request): JsonResponse
    {
        $this->authorizeQueueAccess();

        $data = $request->validate([
            'terminal_key' => 'required|string|max:100',
        ]);

        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');

        $printer = PrinterConfig::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('terminal_key', $data['terminal_key'])
            ->latest('updated_at')
            ->first();

        abort_if(! $printer, 404, 'No hay impresora configurada para esta terminal.');

        $affected = \App\Models\PrintJob::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('printer_config_id', $printer->id)
            ->whereIn('status', ['PENDING', 'PROCESSING'])
            ->update([
                'status' => 'FAILED',
                'error_message' => 'Cancelado desde la cola de impresion.',
            ]);

        return response()->json([
            'success' => true,
            'message' => $affected > 0
                ? "Se detuvieron {$affected} trabajo(s) pendiente(s)."
                : 'No habia trabajos pendientes para detener.',
            'affected' => $affected,
        ]);
    }

    public function purgeQueue(Request $request): JsonResponse
    {
        $this->authorizeQueueAccess();

        $data = $request->validate([
            'terminal_key' => 'required|string|max:100',
        ]);

        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');

        $printer = PrinterConfig::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('terminal_key', $data['terminal_key'])
            ->latest('updated_at')
            ->first();

        abort_if(! $printer, 404, 'No hay impresora configurada para esta terminal.');

        $jobs = \App\Models\PrintJob::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('printer_config_id', $printer->id)
            ->get(['id']);

        $affected = $jobs->count();

        if ($affected > 0) {
            \App\Models\PrintJob::query()
                ->whereIn('id', $jobs->pluck('id'))
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => $affected > 0
                ? "Se eliminaron {$affected} trabajo(s) de la cola."
                : 'La cola ya estaba vacia.',
            'affected' => $affected,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    private function authorizeQzAccess(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_unless($user instanceof User, 401);

        if (
            $user->hasPermission('printers.view')
            || $user->hasPermission('printers.configure')
            || $user->hasPermission('printers.test')
            || $user->hasPermission('sales.create')
            || $user->hasPermission('sales.reprint')
            || $user->hasPermission('tickets.view')
        ) {
            return;
        }

        throw new AuthorizationException('No autorizado para usar la impresion local.');
    }

    /**
     * @throws AuthorizationException
     */
    private function authorizeQueueAccess(): void
    {
        $this->authorizeQzAccess();
    }

    /**
     * @throws AuthorizationException
     */
    private function authorizePrinterConfiguration(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_unless($user instanceof User, 401);

        if ($user->hasPermission('printers.configure')) {
            return;
        }

        throw new AuthorizationException('No autorizado para configurar impresoras.');
    }

    private function resolvedDigitalCertificatePath(): string
    {
        $path = (string) config('print.qz_digital_certificate_path', '');
        return ($path !== '' && is_file($path)) ? $path : '';
    }
}
