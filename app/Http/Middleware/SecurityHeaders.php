<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only apply to non-API web responses
        if ($request->expectsJson()) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=()');
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        // Strict CSP only in production to avoid blocking local dev tools
        if (app()->isProduction()) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.bunny.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.bunny.net; font-src 'self' fonts.bunny.net data:; img-src 'self' data:; connect-src 'self' http://127.0.0.1:8765;"
            );
        }

        return $response;
    }
}
