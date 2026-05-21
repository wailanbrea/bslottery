<?php

namespace App\Policies;

use App\Models\PayoutRule;
use App\Models\User;

class PayoutRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payout_rules.view');
    }

    public function view(User $user, PayoutRule $rule): bool
    {
        return $this->belongsToUserCompany($user, $rule)
            && $user->hasPermission('payout_rules.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payout_rules.create');
    }

    public function update(User $user, PayoutRule $rule): bool
    {
        return $this->belongsToUserCompany($user, $rule)
            && $user->hasPermission('payout_rules.update');
    }

    public function approve(User $user, PayoutRule $rule): bool
    {
        return $this->belongsToUserCompany($user, $rule)
            && $user->hasPermission('payout_rules.approve');
    }

    protected function belongsToUserCompany(User $user, PayoutRule $rule): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $rule->company_id === $user->company_id;
    }
}
