<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemNotification;
use App\Services\Audit\AuditService;
use App\Services\Monitoring\SystemNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemNotificationController extends Controller
{
    public function __construct(
        private SystemNotificationService $notificationService,
    ) {}

    public function index(Request $request): View
    {
        $companyId = (int) session('active_company_id');

        $notifications = SystemNotification::with(['branch', 'readBy'])
            ->where('company_id', $companyId)
            ->when($request->filled('status'), fn ($query) => $query->where('status', strtoupper((string) $request->input('status'))))
            ->orderByRaw("CASE status WHEN 'UNREAD' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE severity WHEN 'CRITICAL' THEN 0 WHEN 'WARNING' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.notifications.index', compact('notifications'));
    }

    public function markRead(SystemNotification $notification): RedirectResponse
    {
        abort_unless($notification->company_id === session('active_company_id'), 404);

        $notification = $this->notificationService->markRead($notification, auth()->user());

        app(AuditService::class)->record(
            module: 'Monitoring',
            action: 'notification_read',
            auditable: $notification,
            description: "Notificacion #{$notification->id} marcada como leida.",
        );

        return back()->with('status', 'Notificacion marcada como leida.');
    }
}
