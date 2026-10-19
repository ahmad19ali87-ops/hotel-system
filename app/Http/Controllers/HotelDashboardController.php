<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Maintenance;

class HotelDashboardController extends Controller
{
    public function index($hotelSlug)
    {
        $hotel = Hotel::forSlug($hotelSlug)->firstOrFail();

        if ((int) auth()->user()->hotel_id !== (int) $hotel->id) {
            abort(403, 'غير مسموح لك بالدخول لهذا الفندق');
        }

        $rooms = Room::where('hotel_id', $hotel->id)->count();
        $bookings = Booking::where('hotel_id', $hotel->id)->count();
        $guests = Guest::where('hotel_id', $hotel->id)->count();
        $revenue = Invoice::where('hotel_id', $hotel->id)->sum('total');
        $maintenanceOpen = Maintenance::where('hotel_id', $hotel->id)->where('status', 'open')->count();

        return view('dashboard.hotel', compact(
            'hotel',
            'rooms',
            'bookings',
            'guests',
            'revenue',
            'maintenanceOpen'
        ));
    }
}
