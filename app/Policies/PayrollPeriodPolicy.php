<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PayrollPeriod;
use App\Models\User;

class PayrollPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.view');
    }

    public function view(User $user, PayrollPeriod $period): bool
    {
        return $user->hasPermission('payroll.view') && $user->company_id === $period->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.manage');
    }

    public function update(User $user, PayrollPeriod $period): bool
    {
        return $user->hasPermission('payroll.approve') && $user->company_id === $period->company_id;
    }
}
