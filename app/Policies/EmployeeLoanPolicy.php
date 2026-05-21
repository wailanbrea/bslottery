<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EmployeeLoan;
use App\Models\User;

class EmployeeLoanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.manage');
    }

    public function update(User $user, EmployeeLoan $loan): bool
    {
        return $user->hasPermission('payroll.manage') && $user->company_id === $loan->company_id;
    }
}
