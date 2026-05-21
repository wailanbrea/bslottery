<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('branches.view');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->isSuperAdmin()
            || ($user->company_id === $branch->company_id && (! $user->branch_id || $user->branch_id === $branch->id));
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('branches.create');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasPermission('branches.update') && $this->view($user, $branch);
    }
}
