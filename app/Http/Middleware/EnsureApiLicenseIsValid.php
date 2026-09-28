<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Licensing\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiLicenseIsValid
{
    public function __construct(private readonly LicenseService $licenses)
    {
    }

    /**
     * API clients receive a machine-readable decision instead of a Blade
     * redirect. Login remains available so an administrator can diagnose and
     * activate the server from the web flow.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $decision = $this->licenses->accessDecision();

        if ($decision['allowed']) {
            $request->attributes->set('license_decision', $decision);

            return $next($request);
        }

        return response()->json([
            'message' => $decision['mode'] === 'unactivated'
                ? 'El sistema requiere activar una licencia.'
                : 'La licencia no permite operar en este momento.',
            'code' => $decision['reason'],
            'mode' => $decision['mode'],
        ], 403);
    }
}
