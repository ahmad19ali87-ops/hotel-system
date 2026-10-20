<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Message;
use App\Models\Offer;
use App\Models\User;
use App\Services\FileSecurityService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GuestPortalController extends Controller
{
    /**
     * Display all guest bookings (completed, active, cancelled).
     */
    public function bookings()
    {
        if (Auth::check() && !Auth::user()->is_verified) {
            return redirect()->route('verification.notice')->with('warning', 'يرجى إدخال رمز التحقق لتفعيل حسابك.');
        }

        $bookings = Booking::with(['hotel', 'room.type', 'invoices'])
            ->where('created_by', Auth::id())
            ->latest()
            ->paginate(15);

        return view('guest.bookings', compact('bookings'));
    }

    /**
     * Cancel an active/pending booking.
     */
    public function cancelBooking(Booking $booking)
    {
        abort_unless($booking->created_by === Auth::id(), 403);

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'لا يمكن إلغاء الحجز في حالته الحالية (الحجز إما مكتمل أو مسجل دخول بالفعل).');
        }

        $booking->update(['status' => 'cancelled']);

        if ($booking->room && $booking->room->status === 'booked') {
            $booking->room->update(['status' => 'available']);
        }

        return back()->with('success', 'تم إلغاء الحجز بنجاح.');
    }

    /**
     * Show guest profile edit form.
     */
    public function profile()
    {
        $user = Auth::user();
        return view('guest.profile', compact('user'));
    }

    /**
     * Update guest profile with two-way sync to hotel guest directory.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'id_type' => ['nullable', 'string', 'max:50'],
            'id_number' => ['nullable', 'string', 'max:100'],
            'national_id' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:jpeg,png,jpg,webp,pdf', 'max:8192'],
            'deleted_attachments' => ['nullable', 'array'],
        ]);

        $fullName = trim(($validated['first_name'] ?? '') . ' ' . ($validated['father_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));
        if (empty($fullName)) {
            $fullName = $validated['name'] ?? $user->name;
        }

        $idNumber = $validated['id_number'] ?? ($validated['national_id'] ?? $user->id_number);

        // Manage existing attachments
        $existingAttachments = $user->attachments ? (json_decode($user->attachments, true) ?: []) : [];

        // Delete requested attachments
        if ($request->has('deleted_attachments') && is_array($request->deleted_attachments)) {
            foreach ($request->deleted_attachments as $delPath) {
                $idx = array_search($delPath, $existingAttachments);
                if ($idx !== false) {
                    unset($existingAttachments[$idx]);
                    Storage::disk('public')->delete($delPath);
                }
            }
            $existingAttachments = array_values($existingAttachments);
        }

        // Upload new attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $path = FileSecurityService::validateAttachment($file, 8192);
                    $existingAttachments[] = $path;
                }
            }
        }

        // Upload avatar
        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = FileSecurityService::validateImage($request->file('avatar'));
        }

        $user->name = $fullName;
        $user->first_name = $validated['first_name'] ?? $user->first_name;
        $user->father_name = $validated['father_name'] ?? $user->father_name;
        $user->last_name = $validated['last_name'] ?? $user->last_name;
        $user->phone = $validated['phone'] ?? $user->phone;
        $user->nationality = $validated['nationality'] ?? ($user->nationality ?: 'سوري');
        $user->birth_date = $validated['birth_date'] ?? $user->birth_date;
        $user->id_type = $validated['id_type'] ?? ($user->id_type ?: 'national_id');
        $user->id_number = $idNumber;
        $user->national_id = $idNumber;
        $user->address = $validated['address'] ?? $user->address;
        $user->attachments = !empty($existingAttachments) ? json_encode($existingAttachments, JSON_UNESCAPED_SLASHES) : null;
        $user->save();

        // Two-way sync: update all linked Guest records in hotel guest directory
        Guest::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->update([
                'user_id' => $user->id,
                'name' => $fullName,
                'first_name' => $user->first_name,
                'father_name' => $user->father_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'nationality' => $user->nationality,
                'birth_date' => $user->birth_date,
                'id_type' => $user->id_type,
                'id_number' => $user->id_number,
                'national_id' => $user->national_id,
                'address' => $user->address,
                'attachments' => $user->attachments,
            ]);

        return back()->with('success', 'تم حفظ وتحديث ملفك الشخصي بنجاح، ومزامنته تلقائياً مع سجلات الفنادق.');
    }

    /**
     * Show public/guest active offers.
     */
    public function offers()
    {
        $offers = Offer::with('hotel')
            ->active()
            ->latest()
            ->paginate(12);

        return view('public.offers', compact('offers'));
    }

    /**
     * Show guest messages mailbox.
     */
    public function messages()
    {
        $user = Auth::user();

        // تحديث حالة الرسائل الواردة غير المقروءة إلى مقروءة عند فتح الصندوق
        Message::where('receiver_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = Message::where('receiver_id', $user->id)
            ->orWhere('sender_id', $user->id)
            ->with(['sender', 'receiver', 'hotel'])
            ->latest()
            ->paginate(20);

        $hotels = Hotel::where('is_visible', true)->orderBy('name')->get();

        return view('guest.messages', compact('messages', 'hotels'));
    }

    /**
     * Send message from guest to a hotel.
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'hotel_id' => ['required', 'exists:hotels,id'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,pdf,doc,docx', 'max:10240'],
        ]);

        $hotel = Hotel::findOrFail($validated['hotel_id']);
        
        // Find staff recipient (hotel manager or reception)
        $receiver = User::where('hotel_id', $hotel->id)
            ->whereIn('role', ['hotel_admin', 'admin', 'reception'])
            ->first() ?? User::where('role', 'super_admin')->first();

        $attachmentPath = null;
        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $attachmentPath = FileSecurityService::validateAttachment($request->file('attachment'), 10240);
        }

        // Find linked guest record
        $guest = Guest::where('hotel_id', $hotel->id)->where('user_id', Auth::id())->first();

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $receiver ? $receiver->id : Auth::id(),
            'hotel_id' => $hotel->id,
            'guest_id' => $guest?->id,
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'attachment' => $attachmentPath,
            'sender_type' => 'guest',
            'is_read' => false,
        ]);

        $guestName = Auth::user()->name ?: 'نزيل';

        // إرسال إشعار للموظف المستلم
        if ($receiver) {
            NotificationService::sendToUser(
                $receiver->id,
                'رسالة جديدة من نزيل',
                "وصلت رسالة جديدة من النزيل {$guestName}: {$validated['subject']}",
                'message_received',
                '✉️',
                route('messages.show', $message->id),
                $hotel->id
            );
        }

        // إرسال إشعار لمدير الفندق والاستقبال ومالك الفندق لمتابعة بريد النزلاء
        $staffToNotify = User::where('hotel_id', $hotel->id)
            ->whereIn('role', ['hotel_admin', 'admin', 'reception', 'hotel_owner'])
            ->where('id', '!=', $receiver?->id)
            ->get();

        foreach ($staffToNotify as $staff) {
            NotificationService::sendToUser(
                $staff->id,
                'رسالة جديدة من نزيل',
                "وصلت رسالة جديدة من النزيل {$guestName}: {$validated['subject']}",
                'message_received',
                '✉️',
                route('messages.inbox'),
                $hotel->id
            );
        }

        return back()->with('success', 'تم إرسال رسالتك ومرفقاتك بنجاح إلى إدارة الفندق.');
    }
}

