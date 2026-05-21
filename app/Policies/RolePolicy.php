<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('roles.view');
    }

    public function assignPermissions(User $user, Role $role): bool
    {
        if ($role->slug === 'SUPER_ADMIN' && ! $user->isSuperAdmin()) {
            return false;
        }

        return $user->hasPermission('roles.assign_permissions');
    }
}
