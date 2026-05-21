<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceIsAuthorized
{
    public function handle(Request $request, Closure $next): Response
    {
        $uuid = $request->header('X-Device-UUID');
        $user = $request->user();
        $requiresMobileDevice = $request->header('X-Requested-With') === 'BSLoteria-Android'
            || (bool) $user?->currentAccessToken()?->can('mobile');

        if (! $uuid) {
            if ($requiresMobileDevice) {
                return response()->json([
                    'message' => 'Dispositivo requerido para operar desde Android.',
                    'code' => 'DEVICE_UUID_REQUIRED',
                ], 403);
            }

            return $next($request);
        }

        if (! $user) {
            return $next($request);
        }

        $device = Device::query()
            ->where('uuid', $uuid)
            ->where('company_id', $user->company_id)
            ->first();

        if (! $device) {
            return response()->json([
                'message' => 'Dispositivo no registrado. Contacte a su administrador.',
                'code' => 'DEVICE_NOT_FOUND',
            ], 403);
        }

        if ($device->status === 'BLOCKED') {
            return response()->json([
                'message' => 'Dispositivo bloqueado. Contacte a su administrador.',
                'code' => 'DEVICE_BLOCKED',
            ], 403);
        }

        if ($device->status !== 'AUTHORIZED') {
            return response()->json([
                'message' => 'Dispositivo pendiente de autorizacion. Contacte a su administrador.',
                'code' => 'DEVICE_NOT_AUTHORIZED',
            ], 403);
        }

        $device->updateQuietly(['last_seen_at' => now()]);

        $request->attributes->set('device', $device);

        return $next($request);
    }
}
