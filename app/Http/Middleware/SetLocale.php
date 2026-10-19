<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request and configure application locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', 'ar');

        if (!in_array($locale, ['ar', 'en'])) {
            $locale = 'ar';
        }

        App::setLocale($locale);
        view()->share('currentLocale', $locale);

        return $next($request);
    }
}

