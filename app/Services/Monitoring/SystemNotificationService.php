<?php

namespace App\Services\Monitoring;

use App\Models\SystemNotification;
use App\Models\User;
use App\Support\Money;

class SystemNotificationService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function upsertUnread(
        int $companyId,
        ?int $branchId,
        string $type,
        string $severity,
        string $title,
        string $body,
        ?string $amount,
        string $fingerprint,
        array $payload = []
    ): SystemNotification {
        return SystemNotification::query()->updateOrCreate(
            [
                'fingerprint' => $fingerprint,
                'status' => 'UNREAD',
            ],
            [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'type' => $type,
                'severity' => $severity,
                'title' => $title,
                'body' => $body,
                'amount' => $amount !== null ? Money::normalize($amount) : null,
                'payload' => $payload,
            ]
        );
    }

    public function markRead(SystemNotification $notification, User $user): SystemNotification
    {
        if ($notification->status === 'READ') {
            return $notification;
        }

        $notification->update([
            'status' => 'READ',
            'read_by' => $user->id,
            'read_at' => now(),
        ]);

        return $notification;
    }
}
