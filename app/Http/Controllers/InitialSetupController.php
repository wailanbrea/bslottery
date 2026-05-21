<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Setup\CompleteInitialSetupRequest;
use App\Services\Licensing\LicenseService;
use App\Services\Setup\InitialBusinessProfileService;
use App\Services\Setup\InitialSetupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class InitialSetupController extends Controller
{
    public function create(
        LicenseService $licenses,
        InitialBusinessProfileService $profiles,
        InitialSetupService $setup,
    ): View|RedirectResponse {
        if ($setup->isCompleted()) {
            return redirect()->route('login');
        }

        $state = $licenses->current();

        abort_unless($state, 403);

        return view('setup.initial', [
            'profile' => $profiles->fromLicense($state),
            'license' => $state,
        ]);
    }

    public function store(
        CompleteInitialSetupRequest $request,
        LicenseService $licenses,
        InitialSetupService $setup,
    ): RedirectResponse {
        $state = $licenses->current();

        abort_unless($state, 403);

        $user = $setup->createFromLicense($state, $request->validated());

        Auth::login($user);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Setup inicial completado correctamente.');
    }
}
