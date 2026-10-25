<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Room;
use DateTime;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($hotelSlug = null)
    {
        if ($hotelSlug) {
            $hotel = Hotel::where('slug', $hotelSlug)->firstOrFail();
            $bookings = Booking::where('hotel_id', $hotel->id)->get();

            return view('bookings.index', compact('bookings', 'hotel'));
        }
$hotel = Hotel::where('slug', $hotelSlug)->firstOrFail();

Room::where('hotel_id', $hotel->id)->get();

        $bookings = Booking::with('room')->get();

        return view('bookings.index', compact('bookings'));
    }

    public function create()
    {
        $rooms = Room::where('status', 'available')->get();
        $guests = Guest::all();

        return view('bookings.create', compact('rooms', 'guests'));
    }

    public function store(Request $request, $hotelSlug = null)
    {
        if ($hotelSlug) {
            $hotel = Hotel::where('slug', $hotelSlug)->firstOrFail();

            Booking::create([
                'hotel_id' => $hotel->id,
                'room_id' => $request->room_id,
                'guest_id' => $request->guest_id,
                'check_in' => $request->check_in,
                'check_out' => $request->check_out,
                'status' => 'reserved',
            ]);

            return redirect()->route('bookings.index', $hotelSlug);
        }

        $roomId = $request->room_id;
        $checkIn = $request->check_in;
        $checkOut = $request->check_out;

        $overlap = Booking::where('room_id', $roomId)
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut])
                    ->orWhereBetween('check_out', [$checkIn, $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                    });
            })
            ->exists();

        if ($overlap) {
            return back()->with('error', 'This room is already booked for the selected dates.');
        }

        $room = Room::findOrFail($request->room_id);

        $days = (new DateTime($request->check_in))->diff(new DateTime($request->check_out))->days;
        $total = $days * $room->price;

        Booking::create([
            'room_id' => $room->id,
            'guest_id' => $request->guest_id,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'total_price' => $total,
            'status' => 'confirmed',
        ]);

        $room->update(['status' => 'reserved']);

        return redirect()->route('bookings.index')->with('success', 'تم إنشاء الحجز بنجاح');
    }

    public function checkin($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'لا يمكن تسجيل دخول لهذا الحجز.');
        }

        $booking->update(['status' => 'checked_in']);

        if ($booking->room) {
            $booking->room->update(['status' => 'reserved']);
        }

        \App\Services\GuestStayNotificationService::sendCheckInEmail($booking);

        return back()->with('success', 'تم تسجيل الدخول بنجاح.');
    }

    public function checkout($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'checked_in') {
            return back()->with('error', 'لا يمكن تسجيل خروج لهذا الحجز.');
        }

        $booking->update(['status' => 'checked_out']);

        if ($booking->room) {
            $booking->room->update(['status' => 'available']);
        }

        \App\Services\GuestStayNotificationService::sendCheckOutEmail($booking);

        return back()->with('success', 'تم تسجيل الخروج بنجاح.');
    }
}
