<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tickets.view');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $this->belongsToUserCompany($user, $ticket)
            && $user->hasPermission('tickets.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('sales.create');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $this->belongsToUserCompany($user, $ticket)
            && ($user->hasPermission('sales.cancel') || $user->hasPermission('sales.reprint'));
    }

    public function pay(User $user, Ticket $ticket): bool
    {
        return $this->belongsToUserCompany($user, $ticket)
            && $user->hasPermission('prizes.pay');
    }

    protected function belongsToUserCompany(User $user, Ticket $ticket): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $ticket->company_id === $user->company_id;
    }
}
