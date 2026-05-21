<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('companies.view');
    }

    public function view(User $user, Company $company): bool
    {
        return $user->isSuperAdmin() || $user->company_id === $company->id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() && $user->hasPermission('companies.create');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->hasPermission('companies.update')
            && ($user->isSuperAdmin() || $user->company_id === $company->id);
    }
}
