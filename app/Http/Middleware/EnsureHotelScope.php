<?php

namespace App\Http\Middleware;

use App\Models\Hotel;
use Closure;
use Illuminate\Http\Request;

class EnsureHotelScope
{
    public function handle(Request $request, Closure $next)
    {
        $hotel = Hotel::forSlug((string) $request->route('hotelSlug'))->firstOrFail();
        $user = $request->user();

        if ($user && !in_array($user->role, ['super_admin', 'platform_admin', 'guest'], true) && (int) $user->hotel_id !== (int) $hotel->id) {
            abort(404);
        }

        $request->attributes->set('hotel', $hotel);

        return $next($request);
    }
}