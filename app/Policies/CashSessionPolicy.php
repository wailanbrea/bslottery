<?php

namespace App\Policies;

use App\Models\CashSession;
use App\Models\User;

class CashSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('cash.view');
    }

    public function view(User $user, CashSession $session = null): bool
    {
        return $user->hasPermission('cash.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('cash.open');
    }

    public function update(User $user, ?CashSession $session = null): bool
    {
        $hasCashUpdatePermission = $user->hasPermission('cash.movement')
            || $user->hasPermission('cash.close')
            || $user->hasPermission('cash.confirm')
            || $user->hasPermission('cash.reopen');

        if (! $hasCashUpdatePermission) {
            return false;
        }

        if (! $session) {
            return true;
        }

        return $this->belongsToUserCompany($user, $session);
    }

    protected function belongsToUserCompany(User $user, CashSession $session): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $session->company_id === $user->company_id;
    }
}
