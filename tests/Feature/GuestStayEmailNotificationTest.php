<?php

namespace Tests\Feature;

use App\Mail\GuestCheckInMail;
use App\Mail\GuestCheckOutMail;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\User;
use App\Services\GuestStayNotificationService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuestStayEmailNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('hotels')) {
            Schema::create('hotels', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug');
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->string('province')->nullable();
                $table->boolean('is_visible')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id');
                $table->foreignId('room_type_id')->nullable();
                $table->string('room_number');
                $table->string('type')->default('standard');
                $table->decimal('price', 10, 2);
                $table->decimal('price_syp', 10, 2)->default(0);
                $table->decimal('price_usd', 10, 2)->default(0);
                $table->string('status')->default('available');
                $table->boolean('needs_cleaning')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('guests')) {
            Schema::create('guests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id');
                $table->foreignId('user_id')->nullable();
                $table->string('name');
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('first_name')->nullable();
                $table->string('father_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('id_type')->nullable();
                $table->string('id_number')->nullable();
                $table->string('national_id')->nullable();
                $table->string('nationality')->nullable();
                $table->string('address')->nullable();
                $table->text('notes')->nullable();
                $table->text('attachments')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id');
                $table->unsignedBigInteger('room_id');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('guest_id')->nullable();
                $table->string('guest_name')->nullable();
                $table->string('guest_phone')->nullable();
                $table->date('check_in')->nullable();
                $table->date('check_out')->nullable();
                $table->decimal('total_price', 10, 2)->default(0);
                $table->decimal('total_price_usd', 10, 2)->default(0);
                $table->string('currency')->default('SYP');
                $table->string('status')->default('confirmed');
                $table->timestamps();
            });
        }

                if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id');
                $table->string('transaction_id')->nullable();
                $table->string('payment_method')->default('cash');
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('currency')->default('SYP');
                $table->string('status')->default('success');
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id');
                $table->foreignId('booking_id')->nullable();
                $table->foreignId('room_id')->nullable();
                $table->string('invoice_number')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('service_amount', 10, 2)->default(0);
                $table->string('service_description')->nullable();
                $table->decimal('tax', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->string('currency')->default('SYP');
                $table->string('status')->default('paid');
                $table->string('pdf_path')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('role')->default('guest');
                $table->foreignId('hotel_id')->nullable();
                $table->string('phone')->nullable();
                $table->text('permissions')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->timestamps();
            });
        }
    }

    public function test_unregistered_guest_gets_provisioned_and_receives_checkin_email_with_password_setup(): void
    {
        Mail::fake();

        $hotel = Hotel::create([
            'name' => 'فندق دمشق الدولي',
            'slug' => 'damascus-international',
            'phone' => '+963112233445',
        ]);

        $room = Room::create([
            'hotel_id' => $hotel->id,
            'room_number' => '405',
            'type' => 'جناح تنفيذي',
            'price' => 300000,
            'price_usd' => 80,
            'status' => 'available',
        ]);

        // Unregistered guest: NO user in users table
        $guestEmail = 'newguest@example.com';
        $guest = Guest::create([
            'hotel_id' => $hotel->id,
            'name' => 'أحمد العلي',
            'email' => $guestEmail,
            'phone' => '0999888777',
        ]);

        $booking = Booking::create([
            'hotel_id' => $hotel->id,
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'check_in' => Carbon::now()->toDateString(),
            'check_out' => Carbon::now()->addDays(3)->toDateString(),
            'currency' => 'SYP',
            'total_price' => 900000,
            'status' => 'confirmed',
        ]);

        // Trigger Check-in Email
        $sent = GuestStayNotificationService::sendCheckInEmail($booking);
        $this->assertTrue($sent);

        // Verify User was automatically provisioned
        $this->assertDatabaseHas('users', [
            'email' => $guestEmail,
            'role' => 'guest',
            'is_verified' => true,
        ]);

        $newUser = User::where('email', $guestEmail)->first();
        $this->assertNotNull($newUser);
        $this->assertEquals($newUser->id, $guest->fresh()->user_id);

        // Verify Password setup token was generated
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $guestEmail,
        ]);

        // Verify Check-in Mail was dispatched
        Mail::assertSent(GuestCheckInMail::class, function ($mail) use ($guestEmail) {
            $this->assertFalse($mail->data['is_existing_user']);
            $this->assertNotEmpty($mail->data['password_setup_url']);
            $this->assertStringContainsString('reset-password', $mail->data['password_setup_url']);
            return $mail->hasTo($guestEmail);
        });
    }

    public function test_existing_registered_user_receives_checkin_email_with_platform_thanks(): void
    {
        Mail::fake();

        $hotel = Hotel::create([
            'name' => 'فندق قصر الشام',
            'slug' => 'cham-palace',
        ]);

        $room = Room::create([
            'hotel_id' => $hotel->id,
            'room_number' => '102',
            'type' => 'غرفة ديلوكس',
            'price' => 150000,
            'price_usd' => 45,
            'status' => 'available',
        ]);

        // Pre-existing user
        $existingEmail = 'loyal.customer@example.com';
        $user = User::create([
            'name' => 'سامر الحلبي',
            'email' => $existingEmail,
            'password' => bcrypt('password123'),
            'role' => 'guest',
            'is_verified' => true,
        ]);

        $guest = Guest::create([
            'hotel_id' => $hotel->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $existingEmail,
        ]);

        $booking = Booking::create([
            'hotel_id' => $hotel->id,
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'check_in' => Carbon::now()->toDateString(),
            'check_out' => Carbon::now()->addDays(2)->toDateString(),
            'currency' => 'USD',
            'total_price' => 90,
            'status' => 'checked_in',
        ]);

        $sent = GuestStayNotificationService::sendCheckInEmail($booking);
        $this->assertTrue($sent);

        Mail::assertSent(GuestCheckInMail::class, function ($mail) use ($existingEmail) {
            $this->assertTrue($mail->data['is_existing_user']);
            $this->assertNull($mail->data['password_setup_url']);
            return $mail->hasTo($existingEmail);
        });
    }

    public function test_checkout_email_sent_with_invoice_details_and_links(): void
    {
        Mail::fake();

        $hotel = Hotel::create([
            'name' => 'منتجع الشاطئ الأزرق',
            'slug' => 'blue-coast-resort',
        ]);

        $room = Room::create([
            'hotel_id' => $hotel->id,
            'room_number' => '501',
            'type' => 'جناح بحري',
            'price' => 400000,
            'price_usd' => 120,
            'status' => 'occupied',
        ]);

        $guestEmail = 'departing.guest@example.com';
        $guest = Guest::create([
            'hotel_id' => $hotel->id,
            'name' => 'نور الهدى',
            'email' => $guestEmail,
        ]);

        $booking = Booking::create([
            'hotel_id' => $hotel->id,
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'check_in' => Carbon::now()->subDays(2)->toDateString(),
            'check_out' => Carbon::now()->toDateString(),
            'currency' => 'USD',
            'total_price' => 240,
            'status' => 'checked_out',
        ]);

        $invoice = Invoice::create([
            'hotel_id' => $hotel->id,
            'booking_id' => $booking->id,
            'room_id' => $room->id,
            'invoice_number' => 'INV-2026-999',
            'amount' => 240,
            'service_amount' => 25,
            'service_description' => 'وجبة عشاء إضافية',
            'total' => 265,
            'currency' => 'USD',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $sent = GuestStayNotificationService::sendCheckOutEmail($booking, $invoice);
        $this->assertTrue($sent);

        Mail::assertSent(GuestCheckOutMail::class, function ($mail) use ($guestEmail) {
            $this->assertEquals('INV-2026-999', $mail->data['invoice_number']);
            $this->assertEquals(265.0, $mail->data['invoice_total']);
            $this->assertEquals('USD', $mail->data['invoice_currency']);
            $this->assertNotEmpty($mail->data['invoice_url']);
            return $mail->hasTo($guestEmail);
        });
    }
}

