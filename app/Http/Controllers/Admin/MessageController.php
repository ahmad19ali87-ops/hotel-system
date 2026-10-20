<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Message;
use App\Models\User;
use App\Services\FileSecurityService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function inbox()
    {
        $messages = Message::where('receiver_id', auth()->id())
            ->with(['sender', 'hotel', 'guest'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.messages.inbox', compact('messages'));
    }

    public function sent()
    {
        $messages = Message::where('sender_id', auth()->id())
            ->with(['receiver', 'hotel', 'guest'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.messages.sent', compact('messages'));
    }

    public function create(Request $request)
    {
        $hotelId = auth()->user()->hotel_id;
        $users = User::where('hotel_id', $hotelId)->where('id', '!=', auth()->id())->get();
        $guests = Guest::where('hotel_id', $hotelId)->orderBy('name')->get();

        $preselectedGuest = null;
        if ($request->filled('guest_id')) {
            $preselectedGuest = Guest::where('hotel_id', $hotelId)->find($request->guest_id);
        }

        return view('admin.messages.create', compact('users', 'guests', 'preselectedGuest'));
    }

    public function store(Request $request)
    {
        $hotelId = auth()->user()->hotel_id;

        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'receiver_id' => 'nullable|exists:users,id',
            'guest_id' => 'nullable|exists:guests,id',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf,doc,docx,txt,zip|max:10240',
        ]);

        $receiverId = null;
        $guestId = null;

        if ($request->filled('guest_id')) {
            $guest = Guest::where('hotel_id', $hotelId)->findOrFail($request->guest_id);
            $guestId = $guest->id;

            if ($guest->user_id) {
                $receiverId = $guest->user_id;
            } elseif ($guest->email) {
                $matchedUser = User::firstOrCreate(
                    ['email' => $guest->email],
                    [
                        'name' => $guest->name,
                        'phone' => $guest->phone,
                        'password' => Hash::make(Str::random(16)),
                        'role' => 'guest',
                        'is_verified' => true,
                    ]
                );
                $guest->update(['user_id' => $matchedUser->id]);
                $receiverId = $matchedUser->id;
            } else {
                return back()->withInput()->with('error', 'النزيل المحدد ليس لديه بريد إلكتروني أو حساب مرتبط لمراسلته.');
            }
        } elseif ($request->filled('receiver_id')) {
            $receiver = User::findOrFail($request->receiver_id);
            // Allow hotel staff or guest users
            if ($receiver->role !== 'guest' && $receiver->role !== 'super_admin') {
                abort_unless((int) $receiver->hotel_id === (int) $hotelId, 403);
            }
            $receiverId = $receiver->id;
        } else {
            return back()->withInput()->with('error', 'يرجى تحديد مستلم الرسالة (موظف أو نزيل).');
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $attachmentPath = FileSecurityService::validateAttachment($request->file('attachment'), 10240);
        }

        $message = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $receiverId,
            'hotel_id' => $hotelId,
            'guest_id' => $guestId,
            'subject' => $request->subject,
            'body' => $request->body,
            'attachment' => $attachmentPath,
            'sender_type' => 'hotel',
            'is_read' => false,
        ]);

        // إرسال إشعار للمستلم بوصول رسالة جديدة
        $senderUser = auth()->user();
        $senderName = $senderUser->name ?: 'إدارة الفندق';
        $receiverUser = User::find($receiverId);
        $link = ($receiverUser && $receiverUser->role === 'guest')
            ? route('guest.messages')
            : route('messages.show', $message->id);

        NotificationService::sendToUser(
            $receiverId,
            'رسالة جديدة',
            "وصلتك رسالة جديدة من {$senderName}: {$request->subject}",
            'message_received',
            '✉️',
            $link,
            $hotelId
        );

        return redirect()->route('messages.sent')->with('success', 'تم إرسال الرسالة والمرفق بنجاح.');
    }

    public function show(Message $message)
    {
        $user = auth()->user();
        if ($message->sender_id != $user->id && $message->receiver_id != $user->id) {
            if (!in_array($user->role, ['hotel_admin', 'admin', 'hotel_owner', 'reception', 'super_admin']) || ($user->hotel_id && (int) $user->hotel_id !== (int) $message->hotel_id)) {
                abort(403);
            }
        }

        if ($message->receiver_id == auth()->id()) {
            $message->update(['is_read' => true]);
        }

        return view('admin.messages.show', compact('message'));
    }

    public function destroy(Message $message)
    {
        if ($message->sender_id != auth()->id() && $message->receiver_id != auth()->id()) {
            abort(403);
        }

        if ($message->attachment) {
            Storage::disk('public')->delete($message->attachment);
        }

        $message->delete();

        return back()->with('success', 'تم حذف الرسالة بنجاح.');
    }
}
