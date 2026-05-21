<?php

namespace App\Policies;

use App\Models\Draw;
use App\Models\User;

class DrawPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('draws.view');
    }

    public function view(User $user, Draw $draw): bool
    {
        return $this->belongsToUserCompany($user, $draw)
            && $user->hasPermission('draws.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('draws.create');
    }

    public function update(User $user, Draw $draw): bool
    {
        return $this->belongsToUserCompany($user, $draw)
            && $user->hasPermission('draws.update');
    }

    public function close(User $user, Draw $draw): bool
    {
        return $this->belongsToUserCompany($user, $draw)
            && $user->hasPermission('draws.close');
    }

    protected function belongsToUserCompany(User $user, Draw $draw): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $draw->company_id === $user->company_id;
    }
}
