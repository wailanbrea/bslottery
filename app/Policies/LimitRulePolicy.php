<?php

namespace App\Policies;

use App\Models\LimitRule;
use App\Models\User;

class LimitRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('limit_rules.view');
    }

    public function view(User $user, LimitRule $rule): bool
    {
        return $this->belongsToUserCompany($user, $rule)
            && $user->hasPermission('limit_rules.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('limit_rules.create');
    }

    public function update(User $user, LimitRule $rule): bool
    {
        return $this->belongsToUserCompany($user, $rule)
            && $user->hasPermission('limit_rules.update');
    }

    public function approve(User $user, LimitRule $rule): bool
    {
        return $this->belongsToUserCompany($user, $rule)
            && $user->hasPermission('limit_rules.approve');
    }

    protected function belongsToUserCompany(User $user, LimitRule $rule): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $rule->company_id === $user->company_id;
    }
}
