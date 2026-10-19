<?php

namespace App\Http\Controllers;

use App\Models\CleaningLog;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class HousekeepingController extends Controller
{
    public function index()
    {
        $hotelId = (int) auth()->user()->hotel_id;
        $rooms = Room::where('hotel_id', $hotelId)
            ->where(fn ($query) => $query->where('needs_cleaning', true)->orWhere('status', 'cleaning'))
            ->with('type')
            ->orderBy('room_number')
            ->get();
        $recentLogs = CleaningLog::where('hotel_id', $hotelId)
            ->with(['room', 'cleaner'])
            ->latest('cleaned_at')
            ->limit(20)
            ->get();

        return view('admin.cleaning.staff', compact('rooms', 'recentLogs'));
    }

    public function clean(Room $room)
    {
        abort_unless((int) $room->hotel_id === (int) auth()->user()->hotel_id, 404);

        DB::transaction(function () use ($room) {
            $room->update([
                'needs_cleaning' => false,
                'status' => 'available',
            ]);

            CleaningLog::create([
                'hotel_id' => $room->hotel_id,
                'room_id' => $room->id,
                'user_id' => auth()->id(),
                'cleaned_at' => now(),
            ]);
        });

        return back()->with('status', 'تم تسجيل إتمام تنظيف الغرفة.');
    }

    /**
     * Reception/Admin requests cleaning for a room (even if guest has not checked out yet).
     */
    public function requestCleaning(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $hotelId = (int) auth()->user()->hotel_id;
        $room = Room::where('id', $request->room_id)->where('hotel_id', $hotelId)->firstOrFail();

        $room->update(['needs_cleaning' => true]);

        $cleanerNote = $request->notes ? " (ملاحظة: {$request->notes})" : '';
        \App\Services\NotificationService::sendToRole(
            'cleaner',
            $hotelId,
            'طلب تنظيف غرفة',
            "طلب تنظيف عاجل للغرفة رقم {$room->room_number} من قبل قسم الاستقبال{$cleanerNote}.",
            'cleaning_request',
            '🧹',
            route('cleaning.index')
        );

        return back()
            ->with('status', "تم إرسال طلب تنظيف الغرفة رقم {$room->room_number} إلى طاقم النظافة بنجاح.")
            ->with('success', "تم إرسال طلب تنظيف الغرفة رقم {$room->room_number} إلى طاقم النظافة بنجاح.");
    }
}