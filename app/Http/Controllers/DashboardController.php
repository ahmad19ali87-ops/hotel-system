<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function occupancy()
    {
        $rooms = Room::all();

        $total = $rooms->count();
        $available = $rooms->where('status', 'available')->count();
        $booked = $rooms->where('status', 'booked')->count();
        $cleaning = $rooms->where('status', 'cleaning')->count();
        $maintenance = $rooms->where('status', 'maintenance')->count();
$hotel = Hotel::where('slug', $hotelSlug)->firstOrFail();

$rooms = Room::where('hotel_id', $hotel->id)->get();

        // نسبة الإشغال
        $occupancyRate = $total > 0 ? round(($booked / $total) * 100) : 0;

        return view('dashboard.occupancy', compact(
            'rooms', 'total', 'available', 'booked', 'cleaning', 'maintenance', 'occupancyRate'
        ));
    }
}

