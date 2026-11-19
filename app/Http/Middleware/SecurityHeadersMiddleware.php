<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('up')) {
            return $response;
        }

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-XSS-Protection', '0');

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
        }

        $nonce = app()->has('csp-nonce') ? app('csp-nonce') : '';
        $nonceDirective = $nonce ? " 'nonce-{$nonce}'" : '';

        $unsafeInline = app()->isLocal() ? " 'unsafe-inline'" : '';

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-eval'{$unsafeInline}{$nonceDirective}",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net{$nonceDirective}",
            "img-src 'self' data: https:",
            "font-src 'self' https://fonts.bunny.net https://cdn.jsdelivr.net",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
