<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Setup\InitialSetupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create(InitialSetupService $setup): View|RedirectResponse
    {
        if (! $setup->isCompleted()) {
            return redirect()->route('setup.initial');
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request, AuditService $audit): RedirectResponse
    {
        $credentials = $request->validated();
        $throttleKey = Str::lower($credentials['username']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'username' => "Demasiados intentos de acceso. Espera {$seconds} segundos e intenta de nuevo.",
            ]);
        }

        /** @var User|null $user */
        $user = User::query()
            ->where('username', $credentials['username'])
            ->with(['company', 'branch'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            if ($user) {
                $attempts = $user->failed_login_attempts + 1;
                $fill = ['failed_login_attempts' => $attempts];

                if ($attempts >= 5) {
                    $fill['locked_until'] = now()->addMinutes(15);
                }

                $user->forceFill($fill)->save();
                $audit->record('auth', 'login_failed', "Intento fallido #{$attempts}.", $user, $user, request: $request);
            }

            throw ValidationException::withMessages([
                'username' => 'Las credenciales no son válidas.',
            ]);
        }

        $this->ensureUserCanLogin($user);

        RateLimiter::clear($throttleKey);
        Auth::login($user);
        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $audit->record('auth', 'login', 'Usuario inició sesión.', $user, $user, request: $request);

        if ($user->must_change_password) {
            return redirect()->route('password.edit');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function editPassword(): View
    {
        return view('auth.change-password');
    }

    public function updatePassword(ChangePasswordRequest $request, AuditService $audit): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->forceFill([
            'password' => $request->validated('password'),
            'must_change_password' => false,
            'password_changed_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $audit->record('auth', 'password_changed', 'Usuario cambió su contraseña.', $user, $user, request: $request);

        return redirect()->intended(route('dashboard'))->with('status', 'Contraseña actualizada correctamente.');
    }

    public function destroy(Request $request, AuditService $audit): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user) {
            $audit->record('auth', 'logout', 'Usuario cerró sesión.', $user, $user, request: $request);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function ensureUserCanLogin(User $user): void
    {
        if ($user->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'username' => 'El usuario no está activo.',
            ]);
        }

        if ($user->locked_until && $user->locked_until->isFuture()) {
            throw ValidationException::withMessages([
                'username' => 'El usuario está bloqueado temporalmente.',
            ]);
        }

        if ($user->company && $user->company->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'username' => 'La empresa no está activa.',
            ]);
        }

        if ($user->branch && $user->branch->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'username' => 'La sucursal no está activa.',
            ]);
        }
    }
}
