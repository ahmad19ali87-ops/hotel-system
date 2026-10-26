<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\HotelStayCalculatorService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HotelStayActualDatesAndPricingTest extends TestCase
{
    protected Hotel $hotel;
    protected User $staffUser;
    protected Room $room;
    protected Guest $guest;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        if (!Schema::hasTable('hotels')) {
            Schema::create('hotels', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug');
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->string('province')->nullable();
                $table->string('status')->default('active');
                $table->boolean('is_visible')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('room_types')) {
            Schema::create('room_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id');
                $table->string('name');
                $table->text('description')->nullable();
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
                $table->dateTime('actual_check_in_at')->nullable();
                $table->dateTime('actual_check_out_at')->nullable();
                $table->decimal('total_price', 10, 2)->default(0);
                $table->decimal('total_price_usd', 10, 2)->default(0);
                $table->string('currency')->default('USD');
                $table->string('status')->default('confirmed');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id');
                $table->foreignId('booking_id')->nullable();
                $table->foreignId('room_id')->nullable();
                $table->integer('nights')->nullable();
                $table->decimal('nightly_rate', 10, 2)->nullable();
                $table->string('invoice_number')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('service_amount', 10, 2)->default(0);
                $table->string('service_description')->nullable();
                $table->string('tax_name')->nullable();
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('tax', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->string('currency')->default('USD');
                $table->string('status')->default('paid');
                $table->string('pdf_path')->nullable();
                $table->timestamp('paid_at')->nullable();
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
                $table->string('currency')->default('USD');
                $table->string('status')->default('success');
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id');
                $table->foreignId('invoice_id')->nullable();
                $table->string('type')->default('income');
                $table->string('title');
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('currency')->default('USD');
                $table->date('transaction_date');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('password');
                $table->string('role')->default('reception');
                $table->unsignedBigInteger('hotel_id')->nullable();
                $table->boolean('is_verified')->default(true);
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('app_notifications')) {
            Schema::create('app_notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('hotel_id')->nullable();
                $table->string('type')->default('general');
                $table->string('title');
                $table->text('message');
                $table->string('icon')->nullable();
                $table->string('link')->nullable();
                $table->text('data')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }

        // Clean tables
        DB::table('app_notifications')->delete();
        Account::query()->delete();
        Payment::query()->delete();
        Invoice::query()->delete();
        Booking::query()->delete();
        Room::query()->delete();
        RoomType::query()->delete();
        Guest::query()->delete();
        User::query()->delete();
        Hotel::query()->delete();

        // Create test hotel
        $this->hotel = Hotel::create([
            'name' => 'فندق الفردوس التجريبي',
            'slug' => 'al-fardous-' . uniqid(),
            'address' => 'دمشق - المزة',
            'province' => 'دمشق',
            'phone' => '011223344',
            'status' => 'active',
            'is_visible' => true,
        ]);

        $this->staffUser = User::create([
            'name' => 'موظف الاستقبال',
            'email' => 'reception-' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'role' => 'reception',
            'hotel_id' => $this->hotel->id,
            'is_verified' => true,
        ]);

        $roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'جناح ديلوكس',
            'description' => 'جناح فاخر',
        ]);

        $this->room = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $roomType->id,
            'room_number' => '401',
            'price' => 100000,
            'price_usd' => 50,
            'status' => 'available',
        ]);

        $this->guest = Guest::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'السيد عماد الأحمد',
            'phone' => '0999123456',
            'email' => 'guest-' . uniqid() . '@example.com',
        ]);
    }

    public function test_calculator_applies_12pm_hotel_day_cutoff_rule(): void
    {
        // 1. Check in 2 PM, check out next day 11 AM => exactly 1 night
        $in1 = Carbon::parse('2026-10-25 14:00:00');
        $out1 = Carbon::parse('2026-10-26 11:00:00');
        $this->assertEquals(1, HotelStayCalculatorService::calculateHotelNights($in1, $out1));

        // 2. Check in 2 PM, check out next day 2 PM (after 12:00 PM cutoff) => 2 nights
        $in2 = Carbon::parse('2026-10-25 14:00:00');
        $out2 = Carbon::parse('2026-10-26 14:00:00');
        $this->assertEquals(2, HotelStayCalculatorService::calculateHotelNights($in2, $out2));

        // 3. Early check in 9 AM (before 12 PM), check out next day 11 AM => 2 nights
        $in3 = Carbon::parse('2026-10-25 09:00:00');
        $out3 = Carbon::parse('2026-10-26 11:00:00');
        $this->assertEquals(2, HotelStayCalculatorService::calculateHotelNights($in3, $out3));

        // 4. Same day day-use check in 1 PM, check out 5 PM => minimum 1 night
        $in4 = Carbon::parse('2026-10-25 13:00:00');
        $out4 = Carbon::parse('2026-10-25 17:00:00');
        $this->assertEquals(1, HotelStayCalculatorService::calculateHotelNights($in4, $out4));
    }

    public function test_advance_booking_checkin_updates_to_actual_real_date(): void
    {
        // Booking made for next month (e.g. 2026-11-20 to 2026-11-25: 5 days)
        $futureIn = Carbon::now()->addDays(20)->toDateString();
        $futureOut = Carbon::now()->addDays(25)->toDateString();

        $booking = Booking::create([
            'hotel_id' => $this->hotel->id,
            'room_id' => $this->room->id,
            'guest_id' => $this->guest->id,
            'guest_name' => $this->guest->name,
            'guest_phone' => $this->guest->phone,
            'check_in' => $futureIn,
            'check_out' => $futureOut,
            'total_price' => 250,
            'total_price_usd' => 250,
            'currency' => 'USD',
            'status' => 'confirmed',
        ]);

        $this->assertEquals($futureIn, Carbon::parse($booking->check_in)->toDateString());

        // Perform check-in via HotelStayCalculatorService or reception checkin action
        HotelStayCalculatorService::processCheckIn($booking);

        $booking->refresh();

        // Actual check-in is now today
        $this->assertEquals(Carbon::today()->toDateString(), Carbon::parse($booking->check_in)->toDateString());
        $this->assertNotNull($booking->actual_check_in_at);
        $this->assertEquals('checked_in', $booking->status);
        $this->assertEquals('occupied', $this->room->fresh()->status);
    }

    public function test_checkout_calculates_actual_days_and_allows_manual_override(): void
    {
        // Check in 2 days ago at 14:00
        $twoDaysAgo = Carbon::now()->subDays(2)->setTime(14, 0, 0);

        $booking = Booking::create([
            'hotel_id' => $this->hotel->id,
            'room_id' => $this->room->id,
            'guest_id' => $this->guest->id,
            'guest_name' => $this->guest->name,
            'guest_phone' => $this->guest->phone,
            'check_in' => $twoDaysAgo->toDateString(),
            'check_out' => Carbon::now()->addDays(3)->toDateString(),
            'actual_check_in_at' => $twoDaysAgo,
            'total_price' => 500,
            'total_price_usd' => 500,
            'currency' => 'USD',
            'status' => 'checked_in',
        ]);

        // Scenario 1: Checkout with auto-calculated duration (before 12:00 PM => 2 nights)
        $result = HotelStayCalculatorService::processCheckOut($booking, [
            'check_out_time' => Carbon::now()->setTime(11, 0, 0),
        ]);

        $this->assertEquals(2, $result['days']);
        $this->assertEquals(50, $result['nightly_rate']);
        $this->assertEquals(100, $result['room_amount']);
        $this->assertEquals(100, $result['invoice']->total);
        $this->assertEquals('paid', $result['invoice']->status);
        $this->assertEquals('checked_out', $booking->fresh()->status);

        // Scenario 2: Test with manual override (receptionist specifies 4 days and $45 rate)
        $booking2 = Booking::create([
            'hotel_id' => $this->hotel->id,
            'room_id' => $this->room->id,
            'guest_id' => $this->guest->id,
            'guest_name' => $this->guest->name,
            'guest_phone' => $this->guest->phone,
            'check_in' => Carbon::today()->toDateString(),
            'check_out' => Carbon::today()->addDay()->toDateString(),
            'total_price' => 50,
            'currency' => 'USD',
            'status' => 'checked_in',
        ]);

        $result2 = HotelStayCalculatorService::processCheckOut($booking2, [
            'days' => 4,
            'nightly_rate' => 45.0,
            'service_amount' => 20.0,
        ]);

        $this->assertEquals(4, $result2['days']);
        $this->assertEquals(45.0, $result2['nightly_rate']);
        $this->assertEquals(180.0, $result2['room_amount']);
        $this->assertEquals(200.0, $result2['invoice']->total);
        $this->assertEquals('paid', $result2['invoice']->status);
    }

    public function test_staff_api_checkout_uses_actual_days_and_updates_invoice(): void
    {
        $checkInTime = Carbon::now()->subDays(1)->setTime(14, 0, 0);

        $booking = Booking::create([
            'hotel_id' => $this->hotel->id,
            'room_id' => $this->room->id,
            'guest_id' => $this->guest->id,
            'guest_name' => $this->guest->name,
            'guest_phone' => $this->guest->phone,
            'check_in' => $checkInTime->toDateString(),
            'check_out' => Carbon::now()->addDays(5)->toDateString(),
            'actual_check_in_at' => $checkInTime,
            'total_price' => 300,
            'currency' => 'USD',
            'status' => 'checked_in',
        ]);

        $response = $this->actingAs($this->staffUser, 'sanctum')
            ->postJson("/api/staff/bookings/{$booking->id}/check-out", [
                'days' => 2,
                'nightly_rate' => 50,
                'service_amount' => 15,
                'payment_method' => 'cash',
            ]);

        $response->assertOk();
        $this->assertEquals('checked_out', $booking->fresh()->status);

        $invoice = Invoice::where('booking_id', $booking->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(2, $invoice->nights);
        $this->assertEquals(50, $invoice->nightly_rate);
        $this->assertEquals(100, $invoice->amount);
        $this->assertEquals(115, $invoice->total);
        $this->assertEquals('paid', $invoice->status);
    }
}
