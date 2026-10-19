<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Room;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    /**
     * Submit a new complaint or suggestion from mobile app or web guest.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:3000',
            'guest_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'room_number' => 'nullable|string|max:50',
            'booking_id' => 'nullable|exists:bookings,id',
            'category' => 'nullable|string|max:50',
        ]);

        $user = $request->user();
        $hotelId = (int) $validated['hotel_id'];
        $hotel = Hotel::findOrFail($hotelId);

        $guestId = null;
        if ($user) {
            $guest = Guest::where('hotel_id', $hotelId)
                ->where(function ($q) use ($user) {
                    $q->where('email', $user->email);
                    if ($user->phone) {
                        $q->orWhere('phone', $user->phone);
                    }
                })->first();
            $guestId = $guest?->id;
        }

        $roomId = null;
        if (! empty($validated['room_number'])) {
            $room = Room::where('hotel_id', $hotelId)
                ->where('room_number', trim($validated['room_number']))
                ->first();
            $roomId = $room?->id;
        }

        // Prefix title with category if provided
        $title = $validated['title'];
        if (! empty($validated['category'])) {
            $categoryName = match ($validated['category']) {
                'service' => 'خدمة الغرف',
                'cleanliness' => 'النظافة',
                'maintenance' => 'صيانة',
                'reception' => 'الاستقبال',
                'suggestion' => 'اقتراح تحسين',
                default => $validated['category'],
            };
            $title = "[{$categoryName}] " . $title;
        }

        $complaint = Complaint::create([
            'hotel_id' => $hotelId,
            'guest_id' => $guestId,
            'booking_id' => $validated['booking_id'] ?? null,
            'room_id' => $roomId,
            'title' => $title,
            'message' => $validated['message'] . (! empty($validated['phone']) ? "\nهاتف للتواصل: " . $validated['phone'] : '') . (! empty($validated['guest_name']) ? " (النزيل: " . $validated['guest_name'] . ")" : ''),
            'status' => 'new',
        ]);

        // Send instant notification to reception and hotel staff
        $roomText = $roomId ? "في الغرفة {$validated['room_number']}" : "عامة";
        NotificationService::sendToRole(
            'reception',
            $hotelId,
            'شكوى / مقترح نزيل جديد',
            "وردت شكوى جديدة ({$title}) {$roomText}. يرجى المتابعة والرد.",
            'complaint',
            '⚠️',
            '/complaints'
        );

        NotificationService::sendToRole(
            'manager',
            $hotelId,
            'تنبيه شكوى نزيل',
            "وردت شكوى جديدة ({$title}) بفندق {$hotel->name}.",
            'complaint',
            '📢',
            '/complaints'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم استلام شكواك / مقترحك بنجاح، وستتم متابعته والتواصل معك من قبل إدارة الفندق.',
            'data' => [
                'id' => $complaint->id,
                'title' => $complaint->title,
                'status' => $complaint->status,
                'created_at' => $complaint->created_at->format('Y-m-d H:i'),
            ],
        ]);
    }

    /**
     * Get complaints submitted by current guest / user.
     */
    public function myComplaints(Request $request)
    {
        $user = $request->user();
        $query = Complaint::with(['hotel:id,name,province', 'room:id,room_number'])->latest();

        if ($user) {
            $userGuestIds = Guest::where('email', $user->email)
                ->orWhere(function ($q) use ($user) {
                    if ($user->phone) {
                        $q->where('phone', $user->phone);
                    }
                })->pluck('id');

            $query->where(function ($q) use ($userGuestIds, $user) {
                if ($userGuestIds->isNotEmpty()) {
                    $q->whereIn('guest_id', $userGuestIds);
                }
                if ($user->phone) {
                    $q->orWhere('message', 'like', "%{$user->phone}%");
                }
                if ($user->name) {
                    $q->orWhere('message', 'like', "%{$user->name}%");
                }
            });
        } elseif ($request->filled('phone')) {
            $query->where('message', 'like', "%{$request->phone}%");
        } else {
            return response()->json([
                'status' => 'success',
                'data' => [],
            ]);
        }

        $complaints = $query->take(30)->get()->map(function ($c) {
            $statusLabel = match ($c->status) {
                'closed' => 'تمت المعالجة',
                'in_progress' => 'قيد المعالجة',
                default => 'جديدة',
            };

            return [
                'id' => $c->id,
                'hotel_id' => $c->hotel_id,
                'hotel_name' => $c->hotel?->name ?? 'فندق',
                'title' => $c->title,
                'message' => $c->message,
                'status' => $c->status,
                'status_label' => $statusLabel,
                'response' => $c->response,
                'room_number' => $c->room?->room_number,
                'created_at' => $c->created_at->format('Y-m-d H:i'),
                'time_ago' => $c->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $complaints,
        ]);
    }
}

