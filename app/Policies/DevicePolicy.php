<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('devices.view');
    }

    public function view(User $user, Device $device): bool
    {
        return $user->isSuperAdmin()
            || ($user->company_id === $device->company_id && (! $user->branch_id || $user->branch_id === $device->branch_id));
    }

    public function authorizeDevice(User $user, Device $device): bool
    {
        return $user->hasPermission('devices.authorize') && $this->view($user, $device);
    }

    public function block(User $user, Device $device): bool
    {
        return $user->hasPermission('devices.block') && $this->view($user, $device);
    }
}
