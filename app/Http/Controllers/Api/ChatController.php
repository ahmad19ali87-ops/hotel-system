<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Message;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    /**
     * Get Messenger-style contacts list for 1-on-1 chat.
     * As requested: البريد يجب ان يكون مثل مسنجر ليست محادثه جماعيه تدخل على حساب تراسله كدردشه في مكان واحد
     */
    public function contacts(Request $request)
    {
        $user = $request->user();

        $roleLabels = [
            'guest' => 'نزيل',
            'hotel_admin' => 'مدير الفندق',
            'admin' => 'إدارة الفندق',
            'super_admin' => 'إدارة المنصة',
            'reception' => 'موظف الاستقبال',
            'accountant' => 'المحاسب',
            'maintenance' => 'مسؤول الصيانة',
            'cleaner' => 'خدمة الغرف',
            'hotel_owner' => 'مالك الفندق',
        ];

        // Messenger Style: Only show accounts who have exchanged messages previously
        $sentToIds = Message::where('sender_id', $user->id)
            ->whereNotNull('receiver_id')
            ->pluck('receiver_id');

        $receivedFromIds = Message::where('receiver_id', $user->id)
            ->whereNotNull('sender_id')
            ->pluck('sender_id');

        $partnerIds = $sentToIds->merge($receivedFromIds)
            ->unique()
            ->filter(fn ($id) => (int) $id !== (int) $user->id)
            ->values();

        $contacts = User::whereIn('id', $partnerIds)->get();

        $list = $contacts->map(function ($c) use ($user, $roleLabels) {
            $lastMsg = Message::where(function ($q) use ($user, $c) {
                $q->where(function ($sub) use ($user, $c) {
                    $sub->where('sender_id', $user->id)->where('receiver_id', $c->id);
                })->orWhere(function ($sub) use ($user, $c) {
                    $sub->where('sender_id', $c->id)->where('receiver_id', $user->id);
                });
            })->latest()->first();

            $unreadCount = Message::where('sender_id', $c->id)
                ->where('receiver_id', $user->id)
                ->where('is_read', false)
                ->count();

            $isBroadcastContact = Message::where('sender_id', $c->id)
                ->where('receiver_id', $user->id)
                ->where('is_broadcast', true)
                ->exists();

            $allowReply = !Message::where('sender_id', $c->id)
                ->where('receiver_id', $user->id)
                ->where('allow_reply', false)
                ->exists();

            $hotel = $c->hotel_id ? Hotel::find($c->hotel_id) : null;
            $roleLabel = $roleLabels[$c->role] ?? $c->role;
            if ($isBroadcastContact) {
                $roleLabel = '📢 تعميم رسمي من إدارة التطبيق';
            } elseif ($hotel) {
                $roleLabel = "{$roleLabel} - {$hotel->name}";
            }

            $displayName = $c->name;
            if ($isBroadcastContact && in_array($c->role, ['super_admin', 'platform_admin', 'admin', 'hotel_admin'])) {
                $displayName = '📢 إدارة التطبيق (تعميم رسمي)';
            }

            return [
                'id' => $c->id,
                'name' => $displayName,
                'email' => $c->email,
                'phone' => $c->phone ?? '',
                'role' => $c->role,
                'role_label' => $roleLabel,
                'hotel_id' => $c->hotel_id,
                'hotel_name' => $hotel?->name,
                'avatar_url' => $c->avatar_url,
                'is_broadcast' => $isBroadcastContact,
                'allow_reply' => $allowReply,
                'last_message' => $lastMsg ? $lastMsg->body : '',
                'last_time' => $lastMsg ? $lastMsg->created_at->diffForHumans() : '',
                'last_timestamp' => $lastMsg ? $lastMsg->created_at->timestamp : 0,
                'unread_count' => $unreadCount,
            ];
        })->sortByDesc('last_timestamp')->values();

        return response()->json([
            'status' => 'success',
            'data' => $list,
        ]);
    }

    /**
     * Get chat conversation list (Legacy & Mobile compatibility).
     */
    public function conversations(Request $request)
    {
        return $this->contacts($request);
    }

    /**
     * Get chat stream messages for a conversation (1-on-1 Messenger thread).
     */
    public function messages(Request $request)
    {
        $user = $request->user();

        $contactId = $request->input('contact_id', $request->input('user_id', $request->input('receiver_id')));

        $query = Message::query();

        if ($contactId) {
            // Strict 1-on-1 Messenger conversation between $user and $contactId
            $query->where(function ($q) use ($user, $contactId) {
                $q->where(function ($sub) use ($user, $contactId) {
                    $sub->where('sender_id', $user->id)->where('receiver_id', $contactId);
                })->orWhere(function ($sub) use ($user, $contactId) {
                    $sub->where('sender_id', $contactId)->where('receiver_id', $user->id);
                });
            });

            // Mark incoming messages from this contact as read
            Message::where('sender_id', $contactId)
                ->where('receiver_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        } elseif ($user->role === 'guest') {
            $hotelId = $request->hotel_id ?? Hotel::where('is_visible', true)->value('id');
            if ($hotelId) {
                $query->where('hotel_id', $hotelId)
                      ->where(function ($q) use ($user) {
                          $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id);
                      });

                Message::where('hotel_id', $hotelId)
                    ->where('receiver_id', $user->id)
                    ->where('is_read', false)
                    ->update(['is_read' => true]);
            }
        } else {
            $guestId = $request->guest_id;
            if ($guestId) {
                $query->where('guest_id', $guestId);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id);
                });
            }

            Message::where('receiver_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        $messages = $query->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        $chatStream = $messages->map(function ($msg) use ($user) {
            $isSender = (int) $msg->sender_id === (int) $user->id;

            $attachmentUrl = null;
            if ($msg->attachment) {
                $attachmentUrl = Storage::disk('public')->exists($msg->attachment)
                    ? asset('storage/' . ltrim($msg->attachment, '/'))
                    : asset($msg->attachment);
            }

            return [
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'sender_name' => $msg->sender?->name ?? 'مستخدم',
                'is_sender' => $isSender,
                'subject' => $msg->subject,
                'body' => $msg->body,
                'attachment_url' => $attachmentUrl,
                'is_image' => $attachmentUrl && preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $attachmentUrl),
                'is_read' => (bool) $msg->is_read,
                'is_broadcast' => (bool) ($msg->is_broadcast ?? false),
                'allow_reply' => (bool) ($msg->allow_reply ?? true),
                'time' => $msg->created_at->format('H:i'),
                'date' => $msg->created_at->format('Y-m-d'),
                'created_at' => $msg->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $chatStream,
        ]);
    }

    /**
     * Send message in chat thread.
     */
    public function sendMessage(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'body' => 'required|string|max:5000',
            'receiver_id' => 'nullable|exists:users,id',
            'hotel_id' => 'nullable|exists:hotels,id',
            'guest_id' => 'nullable|exists:guests,id',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf,doc,docx|max:10240',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $attachmentPath = $request->file('attachment')->store('messages/attachments', 'public');
        }

        // Direct 1-on-1 Messenger message to a specific user
        if ($request->filled('receiver_id')) {
            $receiver = User::findOrFail($request->receiver_id);

            // Prevent replying to broadcast / no-reply announcements from administration
            $isReplyBlocked = Message::where('sender_id', $receiver->id)
                ->where('receiver_id', $user->id)
                ->where('allow_reply', false)
                ->exists();

            if ($isReplyBlocked) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'عذراً، هذه رسالة تعميمية رسمية من إدارة التطبيق ولا يمكن الرد عليها.',
                ], 403);
            }

            $hotelId = $user->hotel_id ?? $receiver->hotel_id ?? Hotel::where('is_visible', true)->value('id');

            $message = Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $receiver->id,
                'hotel_id' => $hotelId,
                'subject' => 'رسالة خاصة',
                'body' => $request->body,
                'attachment' => $attachmentPath,
                'sender_type' => $user->role === 'guest' ? 'guest' : 'hotel',
                'is_read' => false,
            ]);

            NotificationService::sendToUser(
                $receiver->id,
                'رسالة جديدة من ' . $user->name,
                "وصلتك رسالة جديدة: {$request->body}",
                'message_received',
                '✉️',
                $receiver->role === 'guest' ? route('guest.messages') : route('messages.inbox'),
                $hotelId
            );
        } elseif ($user->role === 'guest') {
            $hotelId = $request->hotel_id ?? Hotel::where('is_visible', true)->value('id');
            $hotel = Hotel::findOrFail($hotelId);

            // Find staff receiver
            $receiver = User::where('hotel_id', $hotel->id)
                ->whereIn('role', ['hotel_admin', 'admin', 'reception'])
                ->first() ?? User::where('role', 'super_admin')->first();

            $guest = Guest::where('hotel_id', $hotel->id)->where('user_id', $user->id)->first();

            $message = Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $receiver ? $receiver->id : $user->id,
                'hotel_id' => $hotel->id,
                'guest_id' => $guest?->id,
                'subject' => 'محادثة مباشرة',
                'body' => $request->body,
                'attachment' => $attachmentPath,
                'sender_type' => 'guest',
                'is_read' => false,
            ]);

            // Notify staff members
            if ($receiver) {
                NotificationService::sendToUser(
                    $receiver->id,
                    'رسالة جديدة من نزيل',
                    "وصلت رسالة جديدة من {$user->name}: {$request->body}",
                    'message_received',
                    '✉️',
                    route('messages.inbox'),
                    $hotel->id
                );
            }
        } else {
            // Staff sending to guest
            $hotelId = $user->hotel_id;
            $guest = Guest::where('hotel_id', $hotelId)->findOrFail($request->guest_id);

            $receiverId = $guest->user_id;
            if (! $receiverId && $guest->email) {
                $matched = User::firstOrCreate(
                    ['email' => $guest->email],
                    [
                        'name' => $guest->name,
                        'phone' => $guest->phone,
                        'password' => bcrypt(Str::random(16)),
                        'role' => 'guest',
                        'is_verified' => true,
                    ]
                );
                $guest->update(['user_id' => $matched->id]);
                $receiverId = $matched->id;
            }

            $message = Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $receiverId ?: $user->id,
                'hotel_id' => $hotelId,
                'guest_id' => $guest->id,
                'subject' => 'رسالة من إدارة الفندق',
                'body' => $request->body,
                'attachment' => $attachmentPath,
                'sender_type' => 'hotel',
                'is_read' => false,
            ]);

            if ($receiverId) {
                NotificationService::sendToUser(
                    $receiverId,
                    'رسالة من الفندق',
                    "وصلتك رسالة جديدة من إدارة الفندق: {$request->body}",
                    'message_received',
                    '✉️',
                    route('guest.messages'),
                    $hotelId
                );
            }
        }

        $attachmentUrl = null;
        if ($message->attachment) {
            $attachmentUrl = asset('storage/' . ltrim($message->attachment, '/'));
        }

        return response()->json([
            'status' => 'success',
            'message' => [
                'id' => $message->id,
                'sender_id' => $user->id,
                'sender_name' => $user->name,
                'is_sender' => true,
                'body' => $message->body,
                'attachment_url' => $attachmentUrl,
                'is_image' => $attachmentUrl && preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $attachmentUrl),
                'is_read' => false,
                'time' => $message->created_at->format('H:i'),
                'date' => $message->created_at->format('Y-m-d'),
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ]);
    }
}

