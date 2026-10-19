<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HotelAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next)
{
    if (!Auth::check() || !in_array(Auth::user()->role, ['hotel_admin', 'admin'], true)) {
        abort(403, 'غير مسموح بالدخول هنا');
    }

    return $next($request);
}

}
