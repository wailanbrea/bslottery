<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Licensing\ActivateLicenseRequest;
use App\Services\Licensing\LicenseService;
use App\Services\Setup\InitialSetupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LicenseActivationController extends Controller
{
    public function create(LicenseService $licenses): View
    {
        return view('licensing.activate', [
            'state' => $licenses->current(),
            'projectCode' => config('licensing.project_code'),
            'defaultLocationCode' => config('licensing.default_location_code'),
        ]);
    }

    public function store(
        ActivateLicenseRequest $request,
        LicenseService $licenses,
        InitialSetupService $setup,
    ): RedirectResponse
    {
        $result = $licenses->activate(
            activationCode: (string) $request->validated('activation_code'),
            locationCode: $request->validated('client_location_code') ?: (string) config('licensing.default_location_code'),
            domain: $request->getHost(),
        );

        if ($result->success && $result->valid) {
            return redirect()
                ->route($setup->isCompleted() ? 'dashboard' : 'setup.initial')
                ->with('status', 'Licencia activada correctamente.');
        }

        return back()
            ->withInput($request->safe()->except('activation_code'))
            ->withErrors([
                'activation_code' => $result->message ?: 'No fue posible activar la licencia.',
            ]);
    }

    public function revalidate(
        Request $request,
        LicenseService $licenses,
        InitialSetupService $setup,
    ): RedirectResponse
    {
        if (! $licenses->current()) {
            return redirect()->route('license.activate');
        }

        $result = $licenses->validateCurrent($request->getHost());

        if ($result->success && $result->valid) {
            return redirect()
                ->route($setup->isCompleted() ? 'dashboard' : 'setup.initial')
                ->with('status', 'Licencia revalidada correctamente.');
        }

        $state = $licenses->current();

        return redirect()
            ->route('license.blocked', ['reason' => $state?->reason_code ?: $result->reasonCode ?: 'LICENSE_INVALID'])
            ->withErrors([
                'license' => $result->message ?: 'No fue posible revalidar la licencia.',
            ]);
    }

    public function blocked(Request $request, LicenseService $licenses): View
    {
        return view('licensing.blocked', [
            'state' => $licenses->current(),
            'reason' => $request->query('reason', 'LICENSE_INVALID'),
        ]);
    }
}
