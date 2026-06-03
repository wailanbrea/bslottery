<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Device;
use App\Models\PrinterConfig;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Auto-aprovisionamiento del BSolutions Print Connector: con un token de instalacion de
 * sucursal, registra la caja (impresora + dispositivo autorizado) y devuelve el token de sesion.
 * Pensado para desplegar muchos puntos sin configuracion manual.
 */
class ConnectorEnrollController extends Controller
{
    public function enroll(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enroll_token' => 'required|string|max:80',
            'device_uuid'  => 'required|uuid',
            'device_name'  => 'nullable|string|max:150',
            'machine_name' => 'nullable|string|max:150',
            'printer_name' => 'nullable|string|max:255',
        ]);

        $branch = Branch::where('connector_enroll_token', $data['enroll_token'])
            ->where('status', 'ACTIVE')
            ->first();

        if (! $branch) {
            return response()->json([
                'message' => 'Token de instalacion invalido o sucursal inactiva.',
                'code'    => 'ENROLL_TOKEN_INVALID',
            ], 403);
        }

        $companyId = $branch->company_id;
        $machineName = trim((string) ($data['machine_name'] ?? $data['device_name'] ?? '')) ?: 'Caja';

        // Usuario de servicio por sucursal (emite el token y resuelve company/branch en pending/ack).
        $serviceUser = User::firstOrCreate(
            ['company_id' => $companyId, 'username' => 'connector-b'.$branch->id],
            [
                'uuid'      => (string) Str::uuid(),
                'branch_id' => $branch->id,
                'name'      => 'Print Connector '.$branch->name,
                'password'  => Hash::make(Str::random(48)),
                'status'    => 'ACTIVE',
            ]
        );

        // Dispositivo: autorizado automaticamente (el token es el secreto de confianza).
        $device = Device::firstOrNew(['uuid' => $data['device_uuid'], 'company_id' => $companyId]);
        $device->forceFill([
            'branch_id'          => $branch->id,
            'user_id'            => $serviceUser->id,
            'name'               => $machineName,
            'device_type'        => 'WINDOWS',
            'platform'           => 'WINDOWS',
            'device_fingerprint' => $data['device_uuid'],
            'status'             => 'AUTHORIZED',
            'authorized_at'      => now(),
            'last_seen_at'       => now(),
        ])->save();

        // Terminal key estable y legible (nombre de la PC + sufijo del uuid).
        $base = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $machineName) ?: 'CAJA');
        $terminalKey = substr($base, 0, 20).'-'.strtoupper(substr(str_replace('-', '', $data['device_uuid']), 0, 6));

        $printer = PrinterConfig::firstOrNew([
            'company_id'   => $companyId,
            'branch_id'    => $branch->id,
            'terminal_key' => $terminalKey,
        ]);
        $printer->fill([
            'device_id'          => $device->id,
            'terminal_name'      => $machineName,
            'name'               => 'Caja '.$machineName,
            'printer_type'       => 'THERMAL',
            'connection_type'    => 'PRINT_CONNECTOR',
            'paper_width'        => $printer->paper_width ?: '80MM',
            'printing_mode'      => 'RAW_ESCPOS',
            'auto_cut'           => $printer->auto_cut ?? true,
            'printer_identifier' => ! empty($data['printer_name']) ? $data['printer_name'] : ($printer->printer_identifier ?: $machineName),
            'status'             => 'ACTIVE',
        ]);
        $printer->save();

        // Si la sucursal no tiene impresora por defecto, asignar esta (las ventas web caen aqui).
        if (! $branch->default_printer_id) {
            $branch->forceFill(['default_printer_id' => $printer->id])->save();
        }

        // Evitar acumular tokens: dejar uno por dispositivo.
        $serviceUser->tokens()->where('name', 'connector-'.$data['device_uuid'])->delete();
        $token = $serviceUser->createToken('connector-'.$data['device_uuid'])->plainTextToken;

        return response()->json([
            'token'         => $token,
            'terminal_key'  => $terminalKey,
            'terminal_name' => $machineName,
            'paper_width'   => $printer->paper_width,
            'auto_cut'      => (bool) $printer->auto_cut,
            'device_status' => 'AUTHORIZED',
            'branch'        => $branch->name,
        ]);
    }
}
