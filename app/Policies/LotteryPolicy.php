<?php

namespace App\Policies;

use App\Models\Lottery;
use App\Models\User;

class LotteryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('lotteries.view');
    }

    public function view(User $user, Lottery $lottery): bool
    {
        return $this->belongsToUserCompany($user, $lottery)
            && $user->hasPermission('lotteries.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('lotteries.create');
    }

    public function update(User $user, Lottery $lottery): bool
    {
        return $this->belongsToUserCompany($user, $lottery)
            && $user->hasPermission('lotteries.update');
    }

    protected function belongsToUserCompany(User $user, Lottery $lottery): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $lottery->company_id === null || $lottery->company_id === $user->company_id;
    }
}
