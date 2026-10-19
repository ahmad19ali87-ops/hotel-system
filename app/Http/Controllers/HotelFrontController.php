<?php
// Copilot: fix SQL Server connection and hotel data retrieval

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Complaint;
use App\Models\HotelReview;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class HotelFrontController extends Controller
{
public function index(string $hotelSlug)
{
    $hotel = $this->hotelForSlug($hotelSlug);
    $images = $hotel->image_urls;
    $reviews = $hotel->reviews()->latest()->limit(30)->get();
    $averageRating = $hotel->reviews()->avg('rating');

    return view('public.hotel-details', compact('hotel', 'images', 'reviews', 'averageRating'));
}






    public function rooms(Request $request, $hotelSlug)
    {
        $hotel = $this->hotelForSlug($hotelSlug);
        $validated = $request->validate([
            'check_in' => ['nullable', 'date', 'required_with:check_out', 'after_or_equal:today'],
            'check_out' => ['nullable', 'date', 'required_with:check_in', 'after:check_in'],
        ]);
        $checkIn = $validated['check_in'] ?? null;
        $checkOut = $validated['check_out'] ?? null;
        $rooms = collect();

        if ($checkIn && $checkOut) {
            $rooms = $hotel->rooms()
                ->whereNotIn('status', ['maintenance', 'cleaning', 'reserved'])
                ->whereDoesntHave('bookings', function ($query) use ($checkIn, $checkOut) {
                    $query->whereIn('status', ['confirmed', 'checked_in'])
                        ->where('check_in', '<', $checkOut)
                        ->where('check_out', '>', $checkIn);
                })
                ->with('type')
                ->orderBy('room_number')
                ->get();
        } else {
            $rooms = $hotel->rooms()
                ->where('status', 'available')
                ->with('type')
                ->orderBy('room_number')
                ->get();
        }

        return view('frontend.rooms', compact('hotel', 'rooms', 'checkIn', 'checkOut'));
    }

    public function showRoom(Request $request, $hotelSlug, $id)
    {
        $hotel = $this->hotelForSlug($hotelSlug);
        $room = $hotel->rooms()->with('type')->whereKey($id)->firstOrFail();

        return view('frontend.room', [
            'hotel' => $hotel,
            'room' => $room,
            'checkIn' => $request->query('check_in'),
            'checkOut' => $request->query('check_out'),
        ]);
    }

    public function book(Request $request, $hotelSlug)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('warning', 'يرجى تسجيل الدخول أو إنشاء حساب جديد وتفعيله أولاً لتتمكن من إتمام الحجز.');
        }

        $user = auth()->user();
        if ($user->role === 'guest' && !$user->is_verified) {
            session(['pending_verify_user_id' => $user->id]);
            return redirect()->route('verification.notice')->with('warning', 'يرجى تفعيل حسابك بكود التفعيل أولاً لتتمكن من إتمام الحجز.');
        }

        $hotel = $this->hotelForSlug($hotelSlug);
        $validated = $request->validate([
            'room_id' => ['required', 'integer'],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:32'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ]);

        DB::transaction(function () use ($hotel, $validated, $user) {
            $room = Room::where('hotel_id', $hotel->id)
                ->whereKey($validated['room_id'])
                ->whereNotIn('status', ['maintenance', 'cleaning', 'reserved'])
                ->whereDoesntHave('bookings', function ($query) use ($validated) {
                    $query->whereIn('status', ['confirmed', 'checked_in'])
                        ->where('check_in', '<', $validated['check_out'])
                        ->where('check_out', '>', $validated['check_in']);
                })
                ->lockForUpdate()
                ->firstOrFail();

            // Find existing guest or create new one linked to user
            $guest = Guest::firstOrNew([
                'hotel_id' => $hotel->id,
                'user_id' => $user->id,
            ]);

            $guest->fill([
                'name' => $validated['guest_name'] ?: $user->name,
                'first_name' => $guest->first_name ?: $user->first_name,
                'father_name' => $guest->father_name ?: $user->father_name,
                'last_name' => $guest->last_name ?: $user->last_name,
                'nationality' => $guest->nationality ?: ($user->nationality ?? 'سوري'),
                'birth_date' => $guest->birth_date ?: $user->birth_date,
                'id_type' => $guest->id_type ?: ($user->id_type ?? 'national_id'),
                'id_number' => $guest->id_number ?: $user->id_number,
                'national_id' => $guest->national_id ?: $user->national_id,
                'phone' => $validated['guest_phone'] ?: ($user->phone ?: $guest->phone),
                'email' => $user->email,
                'address' => $guest->address ?: $user->address,
                'attachments' => $guest->attachments ?: $user->attachments,
            ]);
            $guest->save();

            $checkIn = Carbon::parse($validated['check_in']);
            $checkOut = Carbon::parse($validated['check_out']);

            $nights = max(1, (int) $checkIn->diffInDays($checkOut));
            $totalPriceSYP = (float)$room->price * $nights;
            $totalPriceUSD = (float)($room->price_usd ?? 0) * $nights;

            Booking::create([
                'hotel_id' => $hotel->id,
                'room_id' => $room->id,
                'guest_id' => $guest->id,
                'created_by' => auth()->id(),
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'currency' => 'SYP',
                'total_price' => $totalPriceSYP,
                'total_price_usd' => $totalPriceUSD,
                'status' => 'pending',
            ]);

            $room->update(['status' => 'booked']);

            \App\Services\NotificationService::sendToRole(
                ['hotel_admin', 'admin', 'reception'],
                $hotel->id,
                'طلب حجز جديد 🛎️',
                "قام النزيل {$guest->name} بتقديم طلب حجز للغرفة رقم {$room->room_number} للفترة من {$validated['check_in']} إلى {$validated['check_out']}.",
                'booking_created',
                '🛎️',
                route('bookings.index')
            );
        });

        if ($user && $user->role === 'guest') {
            return redirect()->route('guest.bookings')->with('success', 'تم إرسال طلب الحجز بنجاح! حالة الحجز الآن قيد المراجعة (حجز معلق)، وسيقوم فريق الاستقبال أو الإدارة بتأكيده وتحديد العملة والتواصل معكم.');
        }

        return back()->with('success', 'تم إرسال طلب الحجز بنجاح! حالة الحجز الآن قيد المراجعة، وسيقوم فريق الاستقبال بتأكيده وتحديد العملة والتواصل معكم.');
    }

    public function storeReview(Request $request, string $hotelSlug)
    {
        $hotel = $this->hotelForSlug($hotelSlug);
        $validated = $request->validate([
            'guest_name' => ['nullable', 'string', 'max:120'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        HotelReview::create([
            ...$validated,
            'hotel_id' => $hotel->id,
        ]);

        $guestName = $validated['guest_name'] ?: 'نزيل';
        \App\Services\NotificationService::sendToRole(
            ['hotel_admin', 'admin'],
            $hotel->id,
            'تقييم فندقي جديد ⭐',
            "حصل فندق {$hotel->name} على تقييم {$validated['rating']}/5 نجوم من قبل ({$guestName}).",
            'review_new',
            '⭐',
            route('admin.dashboard')
        );

        return back()->with('status', 'شكرًا لك، تم تسجيل تقييمك.');
    }

    public function storeComplaint(Request $request, string $hotelSlug)
    {
        $hotel = $this->hotelForSlug($hotelSlug);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $complaint = Complaint::create([
            ...$validated,
            'hotel_id' => $hotel->id,
            'status' => 'new',
        ]);

        \App\Services\NotificationService::sendToRole(
            ['hotel_admin', 'admin'],
            $hotel->id,
            'شكوى نزيل جديدة 💬',
            "تم استلام شكوى جديدة بعنوان: \"{$validated['title']}\" لفندق {$hotel->name}.",
            'complaint_new',
            '💬',
            route('complaints.show', $complaint->id)
        );

        return back()->with('status', 'تم إرسال الشكوى إلى إدارة الفندق.');
    }

    public function replyReview(Request $request, string $hotelSlug, int $reviewId)
    {
        $hotel = $this->hotelForSlug($hotelSlug);
        $user = auth()->user();

        if (!$user || (!in_array($user->role, ['super_admin', 'platform_admin']) && ((int) $user->hotel_id !== (int) $hotel->id || !in_array($user->role, ['hotel_admin', 'admin', 'reception'])))) {
            abort(403, 'غير مصرح لك بالرد على هذا التقييم.');
        }

        $validated = $request->validate([
            'hotel_reply' => ['required', 'string', 'max:2000'],
        ]);

        $review = $hotel->reviews()->findOrFail($reviewId);
        $review->update([
            'hotel_reply' => $validated['hotel_reply'],
            'replied_at' => now(),
        ]);

        return back()->with('status', 'تم حفظ ونشر رد إدارة الفندق على التقييم بنجاح.');
    }

    private function hotelForSlug(string $hotelSlug): Hotel
    {
        $hotel = Hotel::forSlug($hotelSlug)->firstOrFail();
        $user = auth()->user();

        if ($user && !in_array($user->role, ['super_admin', 'platform_admin', 'guest'], true) && (int) $user->hotel_id !== (int) $hotel->id) {
            abort(404);
        }

        return $hotel;
    }
}
