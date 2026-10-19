<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HotelAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        if (!in_array(Auth::user()->role, ['hotel_admin', 'admin'], true) || ! Auth::user()->hotel_id || ! Auth::user()->hotel()->exists()) {
            abort(403, 'غير مصرح لك بالدخول إلى لوحة الفندق');
        }

        return $next($request);
    }
}
