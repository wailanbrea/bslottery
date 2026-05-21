<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Setup\InitialSetupService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInitialSetupIsCompleted
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        if (! app(InitialSetupService::class)->isCompleted()) {
            return redirect()->route('setup.initial');
        }

        return $next($request);
    }

    private function shouldBypass(Request $request): bool
    {
        return $request->is('license/*')
            || $request->is('setup/*')
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
