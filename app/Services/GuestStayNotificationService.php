<?php

namespace App\Services;

use App\Mail\GuestCheckInMail;
use App\Mail\GuestCheckOutMail;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GuestStayNotificationService
{
    /**
     * Ensure a User account is linked to the Guest.
     * If already registered, links to existing User (is_existing = true).
     * If not registered, provisions a new guest User account and generates a password setup URL.
     *
     * @return array{user: ?User, is_existing: bool, password_setup_url: ?string, email: ?string}
     */
    public static function ensureUserForGuest(Guest $guest): array
    {
        $email = $guest->email;
        if (empty($email) && $guest->user_id) {
            $user = User::find($guest->user_id);
            $email = $user?->email;
        }

        if (empty($email)) {
            return [
                'user' => null,
                'is_existing' => false,
                'password_setup_url' => null,
                'email' => null,
            ];
        }

        $email = strtolower(trim($email));

        // 1. Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            if (!$guest->user_id || $guest->user_id !== $existingUser->id) {
                $guest->user_id = $existingUser->id;
                $guest->save();
            }

            return [
                'user' => $existingUser,
                'is_existing' => true,
                'password_setup_url' => null,
                'email' => $email,
            ];
        }

        // 2. Provision new User account for this guest
        $fullName = $guest->name ?: ($guest->full_name ?: 'نزيل فندقي');
        $randomPassword = Str::random(32);

        $userData = [
            'name' => $fullName,
            'email' => $email,
            'phone' => $guest->phone,
            'role' => 'guest',
            'is_verified' => true,
            'password' => Hash::make($randomPassword),
        ];

        if (Schema::hasColumn('users', 'first_name')) $userData['first_name'] = $guest->first_name;
        if (Schema::hasColumn('users', 'father_name')) $userData['father_name'] = $guest->father_name;
        if (Schema::hasColumn('users', 'last_name')) $userData['last_name'] = $guest->last_name;
        if (Schema::hasColumn('users', 'nationality')) $userData['nationality'] = $guest->nationality ?? 'سوري';
        if (Schema::hasColumn('users', 'id_type')) $userData['id_type'] = $guest->id_type ?? 'national_id';
        if (Schema::hasColumn('users', 'id_number')) $userData['id_number'] = $guest->id_number;
        if (Schema::hasColumn('users', 'national_id')) $userData['national_id'] = $guest->national_id ?? $guest->id_number;
        if (Schema::hasColumn('users', 'address')) $userData['address'] = $guest->address;
        if (Schema::hasColumn('users', 'attachments')) $userData['attachments'] = $guest->attachments;

        $newUser = User::create($userData);

        $guest->user_id = $newUser->id;
        $guest->save();

        // 3. Generate direct password setup / activation token
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        $token = Str::random(60);
        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $newUser->email],
            [
                'token' => $token . '|' . $code,
                'created_at' => now(),
            ]
        );

        $passwordSetupUrl = route('password.reset', ['token' => $token, 'email' => $newUser->email]);

        return [
            'user' => $newUser,
            'is_existing' => false,
            'password_setup_url' => $passwordSetupUrl,
            'email' => $email,
        ];
    }

    /**
     * Send Check-in Email to the guest when checking in.
     */
    public static function sendCheckInEmail(Booking $booking): bool
    {
        $booking->loadMissing(['hotel', 'room.roomType', 'guest.user', 'invoices']);

        $guest = $booking->guest;
        $userMeta = $guest ? self::ensureUserForGuest($guest) : null;
        $recipientEmail = $userMeta['email'] ?? null;

        // Fallback: If no guest email, check booking creator if they are a guest
        if (!$recipientEmail && $booking->created_by) {
            $creator = User::find($booking->created_by);
            if ($creator && $creator->role === 'guest' && !empty($creator->email)) {
                $recipientEmail = $creator->email;
                $userMeta = [
                    'user' => $creator,
                    'is_existing' => true,
                    'password_setup_url' => null,
                    'email' => $creator->email,
                ];
            }
        }

        if (empty($recipientEmail)) {
            Log::info("No email found for booking #{$booking->id} check-in notification.");
            return false;
        }

        $hotelName = $booking->hotel?->name ?: config('app.name', 'بوابة حجز الفنادق');
        $nights = max(1, (int) Carbon::parse($booking->check_in)->diffInDays(Carbon::parse($booking->check_out)));

        $data = [
            'guest_name' => $guest?->name ?: ($userMeta['user']?->name ?? 'عزيزنا النزيل'),
            'guest_email' => $recipientEmail,
            'hotel_name' => $hotelName,
            'room_number' => $booking->room?->room_number ?? '—',
            'room_type' => $booking->room?->roomType?->name ?? ($booking->room?->type ?? 'غرفة فندقية'),
            'check_in' => $booking->check_in,
            'check_out' => $booking->check_out,
            'nights' => $nights,
            'currency' => $booking->currency ?: 'SYP',
            'total_price' => (float) $booking->total_price,
            'is_existing_user' => $userMeta['is_existing'] ?? true,
            'password_setup_url' => $userMeta['password_setup_url'] ?? null,
            'portal_url' => route('guest.bookings'),
            'apps_url' => url('/#downloads'),
            'site_url' => route('home'),
            'hotel_phone' => $booking->hotel?->phone,
            'hotel_address' => $booking->hotel?->address ?: ($booking->hotel?->province ?: 'سوريا'),
        ];

        try {
            Mail::to($recipientEmail)->send(new GuestCheckInMail($booking, $data));
            Log::info("Check-in email sent successfully to {$recipientEmail} for booking #{$booking->id}");
            return true;
        } catch (\Throwable $e) {
            Log::warning("Failed to send check-in email to {$recipientEmail}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send Check-out Email with invoice details when guest checks out.
     */
    public static function sendCheckOutEmail(Booking $booking, ?Invoice $invoice = null): bool
    {
        if (Schema::hasTable('payments')) {
            $booking->loadMissing(['hotel', 'room.roomType', 'guest.user', 'invoices.payments']);
        } else {
            $booking->loadMissing(['hotel', 'room.roomType', 'guest.user', 'invoices']);
        }

        if (!$invoice) {
            $invoice = $booking->invoices()->latest('id')->first();
        }

        $guest = $booking->guest;
        $userMeta = $guest ? self::ensureUserForGuest($guest) : null;
        $recipientEmail = $userMeta['email'] ?? null;

        // Fallback: If no guest email, check booking creator if they are a guest
        if (!$recipientEmail && $booking->created_by) {
            $creator = User::find($booking->created_by);
            if ($creator && $creator->role === 'guest' && !empty($creator->email)) {
                $recipientEmail = $creator->email;
                $userMeta = [
                    'user' => $creator,
                    'is_existing' => true,
                    'password_setup_url' => null,
                    'email' => $creator->email,
                ];
            }
        }

        if (empty($recipientEmail)) {
            Log::info("No email found for booking #{$booking->id} check-out notification.");
            return false;
        }

        $hotelName = $booking->hotel?->name ?: config('app.name', 'بوابة حجز الفنادق');

        // Payment method translation
        $paymentMethod = $invoice?->payments?->first()?->payment_method ?? 'cash';
        $paymentMethodLabels = [
            'cash' => 'نقداً (كاش)',
            'card' => 'بطاقة بنكية / إلكترونية',
            'transfer' => 'حوالة بنكية',
            'online' => 'دفع إلكتروني مباشر',
        ];
        $paymentMethodLabel = $paymentMethodLabels[$paymentMethod] ?? 'نقداً (كاش)';

        // Invoice electronic URL
        $invoiceUrl = '';
        if ($invoice && $booking->hotel && $booking->hotel->slug) {
            $invoiceUrl = route('invoice.by-hotel', [
                'hotelSlug' => $booking->hotel->slug,
                'invoice' => $invoice->invoice_number ?: $invoice->id,
            ]);
        } elseif ($invoice) {
            $invoiceUrl = route('invoices.show', $invoice->id);
        }

        // Check if PDF exists on disk
        $pdfFullPath = null;
        if ($invoice && $invoice->pdf_path && Storage::disk('public')->exists($invoice->pdf_path)) {
            $pdfFullPath = Storage::disk('public')->path($invoice->pdf_path);
        } elseif ($invoice && $booking->hotel) {
            $hotelDirectory = Str::slug($booking->hotel->slug ?: $booking->hotel->name);
            $candidate = $hotelDirectory . '/invoices/' . $invoice->id . '.pdf';
            if (Storage::disk('public')->exists($candidate)) {
                $pdfFullPath = Storage::disk('public')->path($candidate);
            }
        }

        $reviewUrl = $booking->hotel && $booking->hotel->slug
            ? route('hotel.front', ['hotelSlug' => $booking->hotel->slug]) . '#reviews'
            : route('home');

        $data = [
            'guest_name' => $guest?->name ?: ($userMeta['user']?->name ?? 'عزيزنا النزيل'),
            'guest_email' => $recipientEmail,
            'hotel_name' => $hotelName,
            'room_number' => $booking->room?->room_number ?? '—',
            'check_in' => $booking->check_in,
            'check_out' => $booking->check_out,
            'checked_out_at' => Carbon::now()->format('Y-m-d h:i A'),
            'invoice_number' => $invoice?->invoice_number ?: ($invoice ? "#{$invoice->id}" : "#{$booking->id}"),
            'invoice_total' => (float) ($invoice?->total ?? $booking->total_price),
            'invoice_currency' => $invoice?->currency ?: ($booking->currency ?: 'SYP'),
            'service_amount' => (float) ($invoice?->service_amount ?? 0),
            'service_description' => $invoice?->service_description,
            'payment_method' => $paymentMethodLabel,
            'invoice_url' => $invoiceUrl,
            'pdf_full_path' => $pdfFullPath,
            'is_existing_user' => $userMeta['is_existing'] ?? true,
            'password_setup_url' => $userMeta['password_setup_url'] ?? null,
            'review_url' => $reviewUrl,
            'portal_url' => route('guest.bookings'),
            'apps_url' => url('/#downloads'),
            'site_url' => route('home'),
            'hotel_phone' => $booking->hotel?->phone,
            'hotel_address' => $booking->hotel?->address ?: ($booking->hotel?->province ?: 'سوريا'),
        ];

        try {
            Mail::to($recipientEmail)->send(new GuestCheckOutMail($booking, $invoice, $data));
            Log::info("Check-out email sent successfully to {$recipientEmail} for booking #{$booking->id}");
            return true;
        } catch (\Throwable $e) {
            Log::warning("Failed to send check-out email to {$recipientEmail}: " . $e->getMessage());
            return false;
        }
    }
}

