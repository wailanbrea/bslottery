<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WinnerTicket;

class WinnerTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('prizes.pay');
    }

    public function view(User $user, WinnerTicket $winner): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $winner->company_id === $user->company_id;
    }

    public function update(User $user, WinnerTicket $winner): bool
    {
        return $this->view($user, $winner) && $user->hasPermission('prizes.pay');
    }
}
