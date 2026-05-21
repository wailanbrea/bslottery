<?php

namespace App\Policies;

use App\Models\BetType;
use App\Models\User;

class BetTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('lotteries.view');
    }

    public function view(User $user, BetType $betType): bool
    {
        return $this->belongsToUserCompany($user, $betType)
            && $user->hasPermission('lotteries.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('lotteries.create');
    }

    public function update(User $user, BetType $betType): bool
    {
        return $this->belongsToUserCompany($user, $betType)
            && $user->hasPermission('lotteries.update');
    }

    protected function belongsToUserCompany(User $user, BetType $betType): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $betType->company_id === null || $betType->company_id === $user->company_id;
    }
}
