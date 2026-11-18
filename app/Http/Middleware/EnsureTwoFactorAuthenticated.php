<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->requiresTwoFactor()) {
            return $next($request);
        }

        $twoFactorRoutes = ['two-factor.challenge', 'two-factor.verify', 'two-factor.setup', 'two-factor.setup.confirm', 'logout'];

        if (! $user->hasTotpEnabled()) {
            if (! $request->routeIs(...$twoFactorRoutes)) {
                return redirect()->route('two-factor.setup');
            }
        } elseif (! session('two_factor_authenticated')) {
            if (! $request->routeIs(...$twoFactorRoutes)) {
                return redirect()->route('two-factor.challenge');
            }
        }

        return $next($request);
    }
}
