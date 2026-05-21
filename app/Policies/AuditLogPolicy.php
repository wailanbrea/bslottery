<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('audit.view');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        if (! $user->hasPermission('audit.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->company_id === $auditLog->company_id
            && (! $user->branch_id || $user->branch_id === $auditLog->branch_id);
    }
}
