<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get user notifications list.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = AppNotification::where('user_id', $user->id);

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->filled('unread_only') && $request->boolean('unread_only')) {
            $query->where('is_read', false);
        }

        $notifications = (clone $query)
            ->latest()
            ->take(50)
            ->get();

        $unreadCount = AppNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        $data = $notifications->map(function ($n) {
            return [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'type' => $n->type,
                'icon' => $n->icon ?: '🔔',
                'link' => $n->link,
                'data' => $n->data,
                'is_read' => (bool) $n->is_read,
                'created_at' => $n->created_at->format('Y-m-d H:i'),
                'time_ago' => $n->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'unread_count' => $unreadCount,
            'data' => $data,
        ]);
    }

    /**
     * Mark single notification as read.
     */
    public function markSingleRead(Request $request, $id)
    {
        $user = $request->user();

        $notification = AppNotification::where('user_id', $user->id)->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديد الإشعار كمقروء.',
            'unread_count' => AppNotification::where('user_id', $user->id)->where('is_read', false)->count(),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request)
    {
        $user = $request->user();

        AppNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديد كافة الإشعارات كمقروءة.',
            'unread_count' => 0,
        ]);
    }

    /**
     * Send broadcast / targeted notification to hotel staff or everyone.
     */
    public function sendBroadcast(Request $request)
    {
        $user = $request->user();
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'عذراً، صلاحيات مالك الفندق مخصصة للاطلاع فقط.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:1000',
            'target' => 'required|in:everyone,staff,reception,cleaner,maintenance,guest',
            'icon' => 'nullable|string|max:10',
            'type' => 'nullable|string|max:50',
        ]);

        $hotelId = $user->hotel_id;
        $icon = $validated['icon'] ?? '📢';
        $type = $validated['type'] ?? 'broadcast';
        $title = $validated['title'];
        $message = $validated['message'];
        $target = $validated['target'];

        $count = 0;
        if ($target === 'everyone') {
            $count = NotificationService::sendToHotelEveryone($hotelId, $title, $message, $type, $icon, null, ['sender' => $user->name]);
        } elseif ($target === 'staff') {
            $count = NotificationService::sendToHotelStaff($hotelId, $title, $message, $type, $icon, null, ['sender' => $user->name]);
        } elseif ($target === 'guest') {
            $guests = User::where('hotel_id', $hotelId)->where('role', 'guest')->get();
            $count = NotificationService::sendToUsers($guests, $title, $message, $type, $icon, null, $hotelId, ['sender' => $user->name]);
        } else {
            $count = NotificationService::sendToRole($target, $hotelId, $title, $message, $type, $icon, null, ['sender' => $user->name]);
        }

        return response()->json([
            'status' => 'success',
            'message' => "تم إرسال الإشعار بنجاح إلى {$count} مستلم.",
            'recipients_count' => $count,
        ]);
    }
}
