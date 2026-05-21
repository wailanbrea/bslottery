<?php

namespace App\Policies;

use App\Models\Result;
use App\Models\User;

class ResultPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('results.view');
    }

    public function view(User $user, Result $result): bool
    {
        return $this->belongsToUserCompany($user, $result) && $user->hasPermission('results.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('results.create');
    }

    public function update(User $user, Result $result): bool
    {
        return $this->belongsToUserCompany($user, $result)
            && ($user->hasPermission('results.confirm')
                || $user->hasPermission('results.modify_confirmed'));
    }

    public function calculateWinners(User $user): bool
    {
        return $user->hasPermission('winners.calculate') || $user->isSuperAdmin();
    }

    public function authorizePayments(User $user): bool
    {
        return $user->hasPermission('payments.authorize') || $user->isSuperAdmin();
    }

    protected function belongsToUserCompany(User $user, Result $result): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $result->company_id === $user->company_id;
    }
}
