<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Account;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\DeletionLog;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::where('hotel_id', $this->hotelId())
            ->with(['room', 'guest', 'creator'])
            ->latest()
            ->get();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function create()
    {
        $rooms = Room::where('hotel_id', $this->hotelId())
            ->where('status', 'available')
            ->orderBy('room_number')
            ->get();

        return view('admin.bookings.create', compact('rooms'));
    }

    public function store(Request $request)
    {
        $hotelId = $this->hotelId();
        $validated = $request->validate([
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')->where('hotel_id', $hotelId)],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:40'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'currency' => ['nullable', 'string', 'in:SYP,USD'],
        ]);

        DB::transaction(function () use ($hotelId, $validated) {
            $room = Room::where('hotel_id', $hotelId)
                ->whereKey($validated['room_id'])
                ->where('status', 'available')
                ->lockForUpdate()
                ->firstOrFail();

            $guest = Guest::create([
                'hotel_id' => $hotelId,
                'name' => $validated['guest_name'],
                'phone' => $validated['guest_phone'] ?? null,
                'email' => $validated['guest_email'] ?? null,
            ]);

            if (!empty($guest->email)) {
                \App\Services\GuestStayNotificationService::ensureUserForGuest($guest);
            }

            $currency = $validated['currency'] ?? 'SYP';
            $days = max(1, now()->parse($validated['check_in'])->diffInDays($validated['check_out']));
            $totalPriceSYP = (float)$room->price * $days;
            $totalPriceUSD = (float)($room->price_usd ?? 0) * $days;
            $totalPrice = ($currency === 'USD' && $totalPriceUSD > 0) ? $totalPriceUSD : $totalPriceSYP;

            Booking::create([
                'hotel_id' => $hotelId,
                'room_id' => $room->id,
                'guest_id' => $guest->id,
                'created_by' => auth()->id(),
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'currency' => $currency,
                'total_price' => $totalPrice,
                'total_price_usd' => $totalPriceUSD,
                'status' => 'confirmed',
            ]);

            $room->update(['status' => 'booked']);
        });

        return redirect()->route('bookings.index')->with('status', 'تم إنشاء وتأكيد الحجز بنجاح.');
    }

    public function edit(Booking $booking)
    {
        $this->ensureBookingBelongsToHotel($booking);
        $rooms = Room::where('hotel_id', $this->hotelId())->orderBy('room_number')->get();

        return view('admin.bookings.edit', compact('booking', 'rooms'));
    }

    public function update(Request $request, Booking $booking)
    {
        $this->ensureBookingBelongsToHotel($booking);
        $hotelId = $this->hotelId();
        $validated = $request->validate([
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')->where('hotel_id', $hotelId)],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:40'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'currency' => ['nullable', 'string', 'in:SYP,USD'],
            'status' => ['required', Rule::in(['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'])],
        ]);

        $oldStatus = $booking->status;

        DB::transaction(function () use ($booking, $validated, $hotelId) {
            $room = Room::where('hotel_id', $hotelId)->whereKey($validated['room_id'])->firstOrFail();
            $guest = $booking->guest;
            if (! $guest) {
                $guest = new Guest(['hotel_id' => $hotelId]);
            }
            $guest->fill([
                'name' => $validated['guest_name'],
                'phone' => $validated['guest_phone'] ?? null,
                'email' => $validated['guest_email'] ?? $guest->email,
            ]);
            $guest->hotel_id = $hotelId;
            $guest->save();

            if (!empty($guest->email)) {
                \App\Services\GuestStayNotificationService::ensureUserForGuest($guest);
            }

            $currency = $validated['currency'] ?? ($booking->currency ?: 'SYP');
            $days = max(1, now()->parse($validated['check_in'])->diffInDays($validated['check_out']));
            $totalPriceSYP = (float)$room->price * $days;
            $totalPriceUSD = (float)($room->price_usd ?? 0) * $days;
            $totalPrice = ($currency === 'USD' && $totalPriceUSD > 0) ? $totalPriceUSD : $totalPriceSYP;

            $booking->update([
                'room_id' => $room->id,
                'guest_id' => $guest->id,
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'currency' => $currency,
                'total_price' => $totalPrice,
                'total_price_usd' => $totalPriceUSD,
                'status' => $validated['status'],
            ]);
        });

        if ($oldStatus !== 'checked_in' && $validated['status'] === 'checked_in') {
            \App\Services\GuestStayNotificationService::sendCheckInEmail($booking);
        } elseif ($oldStatus !== 'checked_out' && $validated['status'] === 'checked_out') {
            \App\Services\GuestStayNotificationService::sendCheckOutEmail($booking);
        }

        return redirect()->route('bookings.index')->with('status', 'تم تحديث بيانات الحجز بنجاح.');
    }

    public function confirm(Request $request, Booking $booking)
    {
        $this->ensureBookingBelongsToHotel($booking);
        $validated = $request->validate([
            'currency' => ['required', 'string', 'in:SYP,USD'],
        ]);

        $booking->loadMissing(['room', 'hotel', 'guest.user']);
        $room = $booking->room;
        $nights = max(1, (int) \Carbon\Carbon::parse($booking->check_in)->diffInDays(\Carbon\Carbon::parse($booking->check_out)));

        $currency = $validated['currency'];
        $totalUSD = (float)($room?->price_usd ?? 0) * $nights;
        $totalSYP = (float)($room?->price ?? 0) * $nights;
        $total = ($currency === 'USD') ? $totalUSD : $totalSYP;

        $booking->update([
            'currency' => $currency,
            'total_price' => $total,
            'total_price_usd' => $totalUSD,
            'status' => 'confirmed',
        ]);

        $currencyLabel = ($currency === 'USD') ? 'دولار أمريكي ($ USD)' : 'ليرة سورية (ل.س)';

        // إشعار تأكيد الحجز للنزيل
        $guestUser = $booking->guest?->user;
        if (!$guestUser && $booking->created_by) {
            $creator = User::find($booking->created_by);
            if ($creator && $creator->role === 'guest') {
                $guestUser = $creator;
            }
        }
        if ($guestUser) {
            NotificationService::sendToUser(
                $guestUser,
                'تأكيد حجزك الفندقي بنجاح',
                "تم تأكيد حجزك رقم #{$booking->id} في فندق " . ($booking->hotel?->name ?? 'الفندق') . ". عملة الحساب المعتمدة: {$currencyLabel}.",
                'booking_confirmed',
                'fas fa-calendar-check',
                route('guest.bookings'),
                $booking->hotel_id,
                ['booking_id' => $booking->id]
            );
        }

        return redirect()->route('bookings.index')->with('status', "تم تأكيد الحجز بنجاح واعتماد عملة الحساب: {$currencyLabel}.");
    }

    public function destroy(Request $request, Booking $booking)
    {
        $this->ensureBookingBelongsToHotel($booking);

        $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'reason.required' => 'يرجى توضيح سبب حذف الحجز.',
            'reason.min' => 'سبب الحذف يجب ألا يقل عن 3 أحرف.',
        ]);

        $booking->load(['room', 'guest', 'invoices.payments']);

        DB::transaction(function () use ($booking, $request) {
            $hasPaidInvoice = $booking->invoices->contains('status', 'paid');
            $paymentStatus = $hasPaidInvoice ? 'paid' : ($booking->status === 'checked_out' ? 'checked_out' : 'unpaid');

            DeletionLog::create([
                'hotel_id' => $booking->hotel_id,
                'user_id' => auth()->id(),
                'record_type' => 'booking',
                'record_id' => $booking->id,
                'reference_number' => 'حجز #' . $booking->id,
                'guest_name' => $booking->guest?->name ?? '—',
                'room_number' => (string) ($booking->room?->room_number ?? '—'),
                'amount' => (float) $booking->total_price,
                'payment_status' => $paymentStatus,
                'reason' => $request->input('reason'),
                'snapshot' => [
                    'booking_id' => $booking->id,
                    'room_number' => $booking->room?->room_number,
                    'guest_name' => $booking->guest?->name,
                    'guest_phone' => $booking->guest?->phone,
                    'check_in' => $booking->check_in,
                    'check_out' => $booking->check_out,
                    'total_price' => $booking->total_price,
                    'status' => $booking->status,
                    'invoices' => $booking->invoices->toArray(),
                ],
                'deleted_at' => now(),
            ]);

            // إتاحة الغرفة إذا كانت محجوزة بهذا الحجز
            if ($booking->room && in_array($booking->room->status, ['booked', 'reserved'])) {
                $booking->room->update(['status' => 'available']);
            }

            // تنظيف الفواتير والمدفوعات المرتبطة بهذا الحجز
            foreach ($booking->invoices as $inv) {
                Account::where('invoice_id', $inv->id)->delete();
                $inv->payments()->delete();
                $inv->delete();
            }

            $booking->delete();
        });

        return redirect()->route('bookings.index')->with('status', 'تم حذف الحجز وتوثيق العملية في سجل المحذوفات.');
    }

    public function checkin(Booking $booking)
    {
        $this->ensureBookingBelongsToHotel($booking);
        abort_unless($booking->status === 'confirmed', 409, 'الحجز ليس في حالة تسمح بتسجيل الدخول.');

        $booking->update(['status' => 'checked_in']);
        $booking->room()->update(['status' => 'reserved']);

        // إرسال إشعار وتسجيل الدخول عبر البريد الإلكتروني للنزيل
        \App\Services\GuestStayNotificationService::sendCheckInEmail($booking);

        $booking->loadMissing(['hotel', 'guest.user', 'room']);
        $guestUser = $booking->guest?->user;
        if (!$guestUser && $booking->created_by) {
            $creator = User::find($booking->created_by);
            if ($creator && $creator->role === 'guest') {
                $guestUser = $creator;
            }
        }
        if ($guestUser) {
            NotificationService::sendToUser(
                $guestUser,
                'أهلاً بك - تم تسجيل الوصول بنجاح',
                "تم تسجيل وصولك إلى الغرفة رقم " . ($booking->room?->room_number ?? '—') . " في فندق " . ($booking->hotel?->name ?? 'الفندق') . ". نتمنى لك إقامة ممتعة!",
                'check_in',
                'fas fa-door-open',
                route('guest.bookings'),
                $booking->hotel_id,
                ['booking_id' => $booking->id]
            );
        }

        return back()->with('status', 'تم تسجيل دخول النزيل.');
    }

    public function showCheckout(Booking $booking)
    {
        $this->ensureBookingBelongsToHotel($booking);
        abort_unless($booking->status === 'checked_in', 409, 'يجب تسجيل دخول النزيل أولًا.');

        $booking->load(['hotel', 'room', 'guest']);

        $invoice = Invoice::where('hotel_id', $booking->hotel_id)
            ->where('booking_id', $booking->id)
            ->with('payments')
            ->first();

        return view('admin.bookings.checkout', compact('booking', 'invoice'));
    }

    public function checkout(Request $request, Booking $booking, InvoicePdfService $pdfService)
    {
        $this->ensureBookingBelongsToHotel($booking);
        abort_unless($booking->status === 'checked_in', 409, 'يجب تسجيل دخول النزيل أولًا.');

        $validated = $request->validate([
            'service_amount' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'service_description' => ['nullable', 'string', 'max:1000'],
            'tax_name' => ['nullable', 'string', 'max:100'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_method' => ['nullable', Rule::in(['cash', 'card', 'transfer'])],
            'transaction_id' => ['nullable', 'string', 'max:255'],
        ]);

        $invoiceId = DB::transaction(function () use ($booking, $validated) {
            $lockedBooking = Booking::where('hotel_id', $this->hotelId())
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($lockedBooking->status === 'checked_in', 409, 'تم إنهاء هذا الحجز مسبقًا.');

            $invoice = Invoice::where('hotel_id', $lockedBooking->hotel_id)
                ->where('booking_id', $lockedBooking->id)
                ->lockForUpdate()
                ->first();

            // في حال كانت الفاتورة مسددة مسبقًا، يتم تسجيل خروج النزيل مباشرة وتفريغ الغرفة دون إطلاق خطأ
            if ($invoice && $invoice->status === 'paid') {
                $lockedBooking->update(['status' => 'checked_out']);
                Room::where('hotel_id', $lockedBooking->hotel_id)
                    ->whereKey($lockedBooking->room_id)
                    ->update(['status' => 'cleaning', 'needs_cleaning' => true]);

                return $invoice->id;
            }

            $serviceAmount = (float) ($validated['service_amount'] ?? 0);
            $roomAmount = (float) $lockedBooking->total_price;
            $taxRate = (float) ($validated['tax_rate'] ?? 0);
            $taxName = $taxRate > 0 ? (trim($validated['tax_name'] ?? '') ?: 'ضريبة') : null;
            $taxAmount = round(($roomAmount + $serviceAmount) * $taxRate / 100, 2);
            $total = $roomAmount + $serviceAmount + $taxAmount;
            $paymentMethod = $validated['payment_method'] ?? 'cash';

            if (! $invoice) {
                $invoice = Invoice::create([
                    'hotel_id' => $lockedBooking->hotel_id,
                    'booking_id' => $lockedBooking->id,
                    'room_id' => $lockedBooking->room_id,
                    'amount' => $roomAmount,
                    'currency' => $lockedBooking->currency ?? 'SYP',
                    'service_amount' => $serviceAmount,
                    'service_description' => $validated['service_description'] ?? null,
                    'tax_name' => $taxName,
                    'tax_rate' => $taxRate,
                    'tax' => $taxAmount,
                    'total' => $total,
                    'status' => 'paid',
                ]);
            } else {
                $invoice->update([
                    'amount' => $roomAmount,
                    'currency' => $invoice->currency ?: ($lockedBooking->currency ?? 'SYP'),
                    'service_amount' => $serviceAmount,
                    'service_description' => $validated['service_description'] ?? null,
                    'tax_name' => $taxName,
                    'tax_rate' => $taxRate,
                    'tax' => $taxAmount,
                    'total' => $total,
                    'status' => 'paid',
                ]);
            }

            if (! $invoice->payments()->where('status', 'success')->exists()) {
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'transaction_id' => $validated['transaction_id'] ?? null,
                    'payment_method' => $paymentMethod,
                    'amount' => $total,
                    'currency' => $invoice->currency ?? 'SYP',
                    'status' => 'success',
                    'paid_at' => now(),
                ]);
            }

            Account::firstOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'hotel_id' => $invoice->hotel_id,
                    'type' => 'income',
                    'title' => 'تحصيل فاتورة ' . $invoice->invoice_number,
                    'amount' => $invoice->total,
                    'currency' => $invoice->currency ?? 'SYP',
                    'transaction_date' => now()->toDateString(),
                    'notes' => 'حجز رقم ' . $lockedBooking->id,
                ]
            );

            $lockedBooking->update(['status' => 'checked_out']);
            Room::where('hotel_id', $lockedBooking->hotel_id)
                ->whereKey($lockedBooking->room_id)
                ->update(['status' => 'cleaning', 'needs_cleaning' => true]);

            return $invoice->id;
        });

        $invoice = Invoice::with(['hotel', 'booking.guest', 'room', 'payments'])->findOrFail($invoiceId);
        $pdfService->store($invoice);

        // إرسال إشعار تسجيل الخروج والفاتورة الإلكترونية للنزيل
        \App\Services\GuestStayNotificationService::sendCheckOutEmail($booking, $invoice);

        $booking->loadMissing(['hotel', 'guest.user', 'room']);
        $guestUser = $booking->guest?->user;
        if (!$guestUser && $booking->created_by) {
            $creator = User::find($booking->created_by);
            if ($creator && $creator->role === 'guest') {
                $guestUser = $creator;
            }
        }
        if ($guestUser) {
            NotificationService::sendToUser(
                $guestUser,
                'تم تسجيل المغادرة - فاتورة إقامتك جاهزة',
                "شكراً لإقامتك في فندق " . ($booking->hotel?->name ?? 'الفندق') . ". تم إتمام تسجيل المغادرة وإصدار فاتورتك برقم " . ($invoice->invoice_number ?? "#{$invoice->id}") . ".",
                'check_out',
                'fas fa-file-invoice-dollar',
                route('invoices.show', $invoice->id),
                $booking->hotel_id,
                ['booking_id' => $booking->id, 'invoice_id' => $invoice->id]
            );
        }

        // إشعار لموظفي التنظيف بأن الغرفة بحاجة للتنظيف
        NotificationService::sendToRole(
            ['cleaner'],
            $booking->hotel_id,
            'غرفة تحتاج إلى تنظيف بعد مغادرة النزيل',
            "الغرفة رقم " . ($booking->room?->room_number ?? '—') . " أصبحت شاغرة وبحاجة إلى تنظيف فوري.",
            'cleaning_request',
            'fas fa-broom',
            route('cleaning.index'),
            ['room_id' => $booking->room_id, 'booking_id' => $booking->id]
        );

        return redirect()->route('invoices.show', $invoice)->with('status', 'تم تسجيل الدفع وحفظ الفاتورة PDF.');
    }

    private function hotelId(): int
    {
        abort_unless(auth()->user()?->hotel_id, 403);

        return (int) auth()->user()->hotel_id;
    }

    private function ensureBookingBelongsToHotel(Booking $booking): void
    {
        abort_unless((int) $booking->hotel_id === $this->hotelId(), 404);
    }
}