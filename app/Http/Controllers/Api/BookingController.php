<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Create a new booking for authenticated guest.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'currency' => 'nullable|string|in:SYP,USD',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $room = Room::where('hotel_id', $validated['hotel_id'])->findOrFail($validated['room_id']);

        // Check if room is already booked on those dates
        $overlap = Booking::where('room_id', $room->id)
            ->where('status', '!=', 'cancelled')
            ->where('check_in', '<', $validated['check_out'])
            ->where('check_out', '>', $validated['check_in'])
            ->exists();

        if ($overlap) {
            return response()->json([
                'status' => 'error',
                'message' => 'عذراً، هذه الغرفة محجوزة بالفعل خلال التواريخ المحددة. يرجى اختيار غرفة أخرى أو تعديل التواريخ.',
            ], 422);
        }

        // Calculate nights and price
        $nights = max(1, Carbon::parse($validated['check_in'])->diffInDays(Carbon::parse($validated['check_out'])));
        $currency = $validated['currency'] ?? 'USD';

        $priceUsdPerNight = (float) ($room->price_per_night ?: 50);
        $priceSypPerNight = (float) ($room->price_syp ?: ($priceUsdPerNight * 14500));

        $totalUsd = $priceUsdPerNight * $nights;
        $totalSyp = $priceSypPerNight * $nights;

        // Sync or find guest record for this hotel
        $guest = Guest::firstOrCreate(
            ['hotel_id' => $validated['hotel_id'], 'user_id' => $user->id],
            [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'nationality' => $user->nationality ?? 'سوري',
                'id_number' => $user->id_number ?: $user->national_id,
            ]
        );

        $booking = Booking::create([
            'hotel_id' => $validated['hotel_id'],
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'check_in' => $validated['check_in'],
            'check_out' => $validated['check_out'],
            'status' => 'confirmed',
            'currency' => $currency,
            'total_price' => $currency === 'USD' ? $totalUsd : $totalSyp,
            'total_price_usd' => $totalUsd,
            'total_price_syp' => $totalSyp,
            'notes' => $validated['special_requests'] ?? null,
            'created_by' => $user->id,
        ]);

        $hotel = Hotel::find($validated['hotel_id']);

        // Send notifications
        // 1. To the guest
        NotificationService::sendToUser(
            $user->id,
            'تأكيد حجز جديد',
            "تم تأكيد حجزك رقم #{$booking->id} في {$hotel->name} بنجاح.",
            'booking_confirmed',
            '🛎️',
            route('guest.bookings'),
            $hotel->id
        );

        // 2. To hotel staff (manager, reception, hotel owner)
        $staffMembers = User::where('hotel_id', $hotel->id)
            ->whereIn('role', ['hotel_admin', 'admin', 'reception', 'hotel_owner'])
            ->get();

        foreach ($staffMembers as $staff) {
            NotificationService::sendToUser(
                $staff->id,
                'حجز إلكتروني جديد',
                "قام النزيل {$user->name} بحجز الغرفة {$room->room_number} من تاريخ {$booking->check_in} إلى {$booking->check_out}.",
                'booking_new',
                '🛎️',
                route('bookings.index'),
                $hotel->id
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم تأكيد حجزك بنجاح!',
            'booking' => [
                'id' => $booking->id,
                'hotel_name' => $hotel->name,
                'room_number' => $room->room_number,
                'check_in' => $booking->check_in,
                'check_out' => $booking->check_out,
                'nights' => $nights,
                'currency' => $currency,
                'total_price' => $booking->total_price,
                'total_price_formatted' => $currency === 'USD' ? '$' . number_format($totalUsd, 2) : number_format($totalSyp, 0) . ' ل.س',
                'status' => $booking->status,
            ],
        ]);
    }

    /**
     * Get guest's bookings.
     */
    public function myBookings(Request $request)
    {
        $user = $request->user();

        $bookings = Booking::where(function ($q) use ($user) {
            $q->where('created_by', $user->id)
              ->orWhereHas('guest', function ($gQ) use ($user) {
                  $gQ->where('user_id', $user->id)->orWhere('email', $user->email);
              });
        })
        ->with(['hotel', 'room.roomType'])
        ->latest()
        ->get();

        $data = $bookings->map(function ($b) {
            return [
                'id' => $b->id,
                'hotel_id' => $b->hotel_id,
                'hotel_name' => $b->hotel?->name ?? 'فندق',
                'hotel_province' => $b->hotel?->province,
                'hotel_image' => $b->hotel?->cover_image_url ?: $b->hotel?->logo_url,
                'room_number' => $b->room?->room_number,
                'room_type' => $b->room?->roomType?->name ?? 'غرفة قياسية',
                'check_in' => $b->check_in,
                'check_out' => $b->check_out,
                'currency' => $b->currency ?? 'USD',
                'total_price' => (float) $b->total_price,
                'total_price_formatted' => ($b->currency === 'USD')
                    ? '$' . number_format($b->total_price, 2)
                    : number_format($b->total_price, 0) . ' ل.س',
                'status' => $b->status,
                'status_label' => match($b->status) {
                    'confirmed' => 'مؤكد',
                    'checked_in' => 'مقيم حالياً',
                    'checked_out' => 'مكتمل ومغادر',
                    'cancelled' => 'ملغى',
                    default => $b->status,
                },
                'can_cancel' => in_array($b->status, ['confirmed', 'pending']),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Cancel booking.
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $booking = Booking::where(function ($q) use ($user) {
            $q->where('created_by', $user->id)
              ->orWhereHas('guest', function ($gQ) use ($user) {
                  $gQ->where('user_id', $user->id);
              });
        })->findOrFail($id);

        if (! in_array($booking->status, ['confirmed', 'pending'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن إلغاء الحجز في حالته الحالية.',
            ], 422);
        }

        $booking->status = 'cancelled';
        $booking->save();

        return response()->json([
            'status' => 'success',
            'message' => 'تم إلغاء الحجز بنجاح.',
        ]);
    }
}

