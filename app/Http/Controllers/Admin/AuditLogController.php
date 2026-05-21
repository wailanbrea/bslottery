<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()->with(['company', 'branch', 'user'])->latest('created_at');

        if (! $request->user()->isSuperAdmin()) {
            $query->where('company_id', $request->user()->company_id);
        }

        if ($request->user()->branch_id) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        if ($module = $request->string('module')->trim()->toString()) {
            $query->where('module', $module);
        }

        if ($action = $request->string('action')->trim()->toString()) {
            $query->where('action', $action);
        }

        return view('admin.audit.index', [
            'logs' => $query->paginate(20)->withQueryString(),
            'module' => $module ?? '',
            'action' => $action ?? '',
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        Gate::authorize('view', $auditLog);

        return view('admin.audit.show', [
            'log' => $auditLog->load(['company', 'branch', 'user', 'device']),
        ]);
    }
}
