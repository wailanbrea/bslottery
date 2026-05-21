<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;

class JournalEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('accounting.view');
    }

    public function view(User $user, JournalEntry $entry): bool
    {
        return $this->belongsToUserCompany($user, $entry)
            && $user->hasPermission('accounting.view');
    }

    protected function belongsToUserCompany(User $user, JournalEntry $entry): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $entry->company_id === $user->company_id;
    }
}
