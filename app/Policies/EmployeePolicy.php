<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->company_id === $employee->company_id && $user->hasPermission('payroll.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.manage');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasPermission('payroll.manage') && $user->company_id === $employee->company_id;
    }
}
