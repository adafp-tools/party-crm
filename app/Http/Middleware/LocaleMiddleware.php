<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    protected array $supported = ['en', 'fr', 'pt', 'ar', 'am'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale')
            ?? auth()->user()?->locale
            ?? config('app.locale', 'en');

        if (! in_array($locale, $this->supported, true)) {
            $locale = 'en';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
