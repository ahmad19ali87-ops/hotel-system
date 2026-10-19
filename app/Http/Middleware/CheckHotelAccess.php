<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Hotel;

class CheckHotelAccess
{
    public function handle($request, Closure $next)
    {
        $hotelSlug = $request->route('hotelSlug');
        $hotel = Hotel::forSlug($hotelSlug)->firstOrFail();

        if (auth()->user()->hotel_id !== $hotel->id) {
            abort(403, 'غير مسموح لك بالدخول لهذا الفندق');
        }

        return $next($request);
    }
}
