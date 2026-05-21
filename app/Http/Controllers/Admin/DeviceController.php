<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\Devices\DeviceAuthorizationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DeviceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Device::query()->with(['company', 'branch', 'user', 'authorizedBy'])->latest('id');

        if (! $request->user()->isSuperAdmin()) {
            $query->where('company_id', $request->user()->company_id);
        }

        if ($request->user()->branch_id) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('device_fingerprint', 'like', "%{$search}%");
            });
        }

        return view('admin.devices.index', [
            'devices' => $query->paginate(15)->withQueryString(),
            'search' => $search ?? '',
            'status' => $status ?? '',
        ]);
    }

    public function authorizeDevice(Request $request, Device $device, DeviceAuthorizationService $service): RedirectResponse
    {
        Gate::authorize('authorizeDevice', $device);

        $service->authorize($device, $request->user());

        return back()->with('status', 'Dispositivo autorizado.');
    }

    public function block(Request $request, Device $device, DeviceAuthorizationService $service): RedirectResponse
    {
        Gate::authorize('block', $device);

        $service->block($device, $request->user());

        return back()->with('status', 'Dispositivo bloqueado.');
    }
}
