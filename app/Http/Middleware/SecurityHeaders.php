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

        // Strict CSP only in production to avoid blocking local dev tools.
        //
        // NOTA: 'unsafe-eval' es REQUERIDO por Alpine.js (cdn.jsdelivr.net) porque
        // evalua las expresiones inline (x-data, x-show, @click, etc) usando el
        // constructor Function() internamente. Sin esta directiva Alpine no se
        // inicializa y todos los x-show quedan visibles, los @click no responden,
        // etc -- el POS deja de funcionar.
        //
        // Mitigaciones aplicadas:
        //   - script-src restringido a 'self' + cdn.jsdelivr.net (CDN confiable, hash-pinned).
        //   - object-src 'none' bloquea plugins inseguros.
        //   - base-uri 'self' evita inyeccion de base href.
        //   - frame-ancestors 'self' bloquea clickjacking.
        // Mejora futura: migrar a build CSP-safe de Alpine (@alpinejs/csp) que
        // no usa eval, a cambio de refactor de blade templates (x-data -> Alpine.data()).
        if (app()->isProduction()) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; ".
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net fonts.bunny.net static.cloudflareinsights.com; ".
                "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.bunny.net; ".
                "font-src 'self' cdn.jsdelivr.net fonts.bunny.net data:; ".
                "img-src 'self' data:; ".
                "connect-src 'self' cdn.jsdelivr.net cloudflareinsights.com static.cloudflareinsights.com ws://localhost:* ws://127.0.0.1:* wss://localhost:* wss://127.0.0.1:*; ".
                "object-src 'none'; ".
                "base-uri 'self'; ".
                "frame-ancestors 'self';"
            );
        }

        return $response;
    }
}
