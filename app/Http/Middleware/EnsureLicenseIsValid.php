<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Licensing\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLicenseIsValid
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        $decision = app(LicenseService::class)->accessDecision();

        if ($decision['allowed']) {
            if ($decision['mode'] === 'offline_grace') {
                session()->flash('license_warning', 'El sistema está operando en gracia offline de licencia.');
            }

            return $next($request);
        }

        if ($decision['mode'] === 'unactivated') {
            return redirect()->route('license.activate');
        }

        return redirect()->route('license.blocked', ['reason' => $decision['reason']]);
    }

    private function shouldBypass(Request $request): bool
    {
        return $request->is('license/*')
            || $request->is('api/*')
            || $request->is('t/*')
            || $request->is('up')
            || $request->is('favicon.ico')
            || $request->is('build/*')
            || $request->is('css/*')
            || $request->is('js/*')
            || $request->is('images/*');
    }
}
