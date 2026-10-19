<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of user notifications.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($request->wantsJson() || $request->ajax()) {
            $notifications = $user->notifications()->latest()->take(15)->get();
            $unreadCount = $user->unreadNotificationsCount();

            return response()->json([
                'unread_count' => $unreadCount,
                'notifications' => $notifications->map(function ($n) {
                    return [
                        'id' => $n->id,
                        'title' => $n->title,
                        'message' => $n->message,
                        'icon' => $n->icon,
                        'link' => $n->link,
                        'is_read' => $n->is_read,
                        'time_ago' => $n->created_at->diffForHumans(),
                        'created_at' => $n->created_at->format('Y-m-d H:i'),
                    ];
                }),
            ]);
        }

        $notifications = $user->notifications()->latest()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark a single notification as read and optionally redirect to its link.
     */
    public function markAsRead(Request $request, AppNotification $notification)
    {
        abort_unless((int) $notification->user_id === (int) Auth::id(), 403);

        $notification->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        if ($notification->link) {
            return redirect($notification->link);
        }

        return back()->with('success', 'تم تحديد الإشعار كمقروء.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        Auth::user()->notifications()->where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'تم تحديد جميع الإشعارات كمقروءة.');
    }

    /**
     * Delete a notification.
     */
    public function destroy(AppNotification $notification)
    {
        abort_unless((int) $notification->user_id === (int) Auth::id(), 403);

        $notification->delete();

        return back()->with('success', 'تم حذف الإشعار بنجاح.');
    }
}
