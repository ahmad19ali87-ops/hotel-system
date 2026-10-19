<?php

namespace App\HotelSystem\Ahmad\Http\Middleware;

use Closure;
use Illuminate\Session\TokenMismatchException;

class HandleExpiredSession
{
    public function handle($request, Closure $next)
    {
        try {
            return $next($request);
        } catch (TokenMismatchException $e) {
            return redirect()->back()->with('warning', 'انتهت الجلسة، يرجى إعادة المحاولة.');
        }
    }
}
