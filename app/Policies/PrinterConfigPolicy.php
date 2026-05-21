<?php

namespace App\Policies;

use App\Models\PrinterConfig;
use App\Models\User;

class PrinterConfigPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('printers.view');
    }

    public function view(User $user, PrinterConfig $printer): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $printer->company_id === $user->company_id && $user->hasPermission('printers.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('printers.configure');
    }

    public function update(User $user, PrinterConfig $printer): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $printer->company_id === $user->company_id && $user->hasPermission('printers.configure');
    }

    public function test(User $user, PrinterConfig $printer): bool
    {
        return $this->update($user, $printer) && $user->hasPermission('printers.test');
    }
}
