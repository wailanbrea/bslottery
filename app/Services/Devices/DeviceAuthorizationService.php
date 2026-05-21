<?php

declare(strict_types=1);

namespace App\Services\Devices;

use App\Models\Device;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class DeviceAuthorizationService
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function authorize(Device $device, User $authorizedBy): Device
    {
        return DB::transaction(function () use ($device, $authorizedBy): Device {
            $old = $device->toArray();

            $device->forceFill([
                'status' => 'AUTHORIZED',
                'authorized_by' => $authorizedBy->id,
                'authorized_at' => now(),
            ])->save();

            $this->audit->record(
                module: 'devices',
                action: 'authorize',
                description: 'Dispositivo autorizado.',
                auditable: $device,
                user: $authorizedBy,
                oldValues: $old,
                newValues: $device->fresh()->toArray(),
            );

            return $device;
        });
    }

    public function block(Device $device, User $blockedBy): Device
    {
        return DB::transaction(function () use ($device, $blockedBy): Device {
            $old = $device->toArray();

            $device->forceFill([
                'status' => 'BLOCKED',
            ])->save();

            $this->audit->record(
                module: 'devices',
                action: 'block',
                description: 'Dispositivo bloqueado.',
                auditable: $device,
                user: $blockedBy,
                oldValues: $old,
                newValues: $device->fresh()->toArray(),
            );

            return $device;
        });
    }
}
