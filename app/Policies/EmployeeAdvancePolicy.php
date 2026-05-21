<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EmployeeAdvance;
use App\Models\User;

class EmployeeAdvancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.manage');
    }

    public function update(User $user, EmployeeAdvance $advance): bool
    {
        return $user->hasPermission('payroll.approve') && $user->company_id === $advance->company_id;
    }
}
