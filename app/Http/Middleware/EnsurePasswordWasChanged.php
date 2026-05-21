<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordWasChanged
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs('password.*', 'logout', 'login', 'login.store', 'license.*', 'setup.*')) {
            return $next($request);
        }

        return redirect()->route('password.edit')
            ->with('warning', 'Debes cambiar tu contraseña antes de continuar.');
    }
}
