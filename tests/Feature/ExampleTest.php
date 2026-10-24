<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\UploadedFile;
use App\Models\Hotel;
use App\Models\HotelReview;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use App\Models\AppNotification;
use App\Models\RoomType;
use App\Models\Guest;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('logo')->nullable();
            $table->unsignedTinyInteger('stars')->default(3);
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('province')->nullable();
            $table->text('phones')->nullable();
            $table->text('google_maps_url')->nullable();
            $table->string('email')->nullable();
            $table->text('description')->nullable();
            $table->text('images')->nullable();
            $table->string('tagline')->nullable();
            $table->text('amenities')->nullable();
            $table->text('printer_settings')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('link')->nullable();
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hotel_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id');
            $table->string('guest_name')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->foreignId('hotel_id')->nullable();
            $table->string('avatar')->nullable();
            $table->string('phone')->nullable();
            $table->text('bio')->nullable();
            $table->string('job_title')->nullable();
            $table->text('permissions')->nullable();
            $table->string('verification_code', 10)->nullable();
            $table->timestamp('verification_code_sent_at')->nullable();
            $table->unsignedInteger('verification_attempts')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->string('first_name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('nationality')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable();
            $table->string('national_id')->nullable();
            $table->string('address')->nullable();
            $table->text('attachments')->nullable();
            $table->string('google_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id');
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('room_id');
            $table->string('invoice_number')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('service_amount', 10, 2)->default(0);
            $table->text('service_description')->nullable();
            $table->decimal('tax', 10, 2)->default(0);
            $table->string('tax_name')->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency')->default('SYP');
            $table->decimal('amount_usd', 10, 2)->default(0);
            $table->decimal('total_usd', 10, 2)->default(0);
            $table->string('status')->default('unpaid');
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->nullable();
            $table->string('room_number');
            $table->unsignedBigInteger('room_type_id')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('price_syp', 12, 2)->default(0);
            $table->decimal('price_usd', 10, 2)->default(0);
            $table->string('status')->default('available');
            $table->boolean('needs_cleaning')->default(false);
            $table->timestamps();
        });

        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('nationality')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('national_id')->nullable();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->text('attachments')->nullable();
            $table->timestamps();
        });

        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('guest_id')->nullable();
            $table->date('check_in')->nullable();
            $table->date('check_out')->nullable();
            $table->decimal('total_price', 10, 2)->default(0);
            $table->decimal('total_price_usd', 10, 2)->default(0);
            $table->string('currency')->default('SYP');
            $table->string('status')->default('confirmed');
            $table->timestamps();
        });

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id');
            $table->unsignedBigInteger('guest_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('title');
            $table->text('message');
            $table->string('status')->default('new');
            $table->text('response')->nullable();
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id');
            $table->foreignId('room_id');
            $table->string('issue_type');
            $table->string('priority')->default('normal');
            $table->text('notes')->nullable();
            $table->string('status')->default('open');
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->string('transaction_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('SYP');
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('type');
            $table->string('title');
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('SYP');
            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('deletion_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('record_type')->default('invoice');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('identifier')->nullable();
            $table->string('guest_name')->nullable();
            $table->string('room_number')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency', 10)->default('SYP');
            $table->string('payment_status')->nullable();
            $table->text('reason')->nullable();
            $table->longText('snapshot')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cleaning_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id');
            $table->foreignId('room_id');
            $table->foreignId('user_id')->nullable();
            $table->dateTime('cleaned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('action');
            $table->text('details')->nullable();
            $table->timestamps();
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('special_price', 12, 2)->default(0);
            $table->decimal('special_price_usd', 10, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id');
            $table->foreignId('receiver_id')->nullable();
            $table->foreignId('hotel_id')->nullable();
            $table->foreignId('guest_id')->nullable();
            $table->string('subject');
            $table->text('body');
            $table->string('attachment')->nullable();
            $table->string('sender_type')->default('staff');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('hotel_id')->nullable();
            $table->string('type', 60);
            $table->string('title');
            $table->text('message');
            $table->string('icon')->nullable();
            $table->string('link')->nullable();
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_staff_only_sees_and_opens_their_assigned_hotel(): void
    {
        $assignedHotel = Hotel::create([
            'name' => 'Hilton Hotel',
            'slug' => 'hilton',
            'province' => 'الخبر',
            'stars' => 5,
        ]);
        Hotel::create([
            'name' => 'Other Hotel',
            'slug' => 'other-hotel',
            'province' => 'الرياض',
            'stars' => 4,
        ]);
        $staff = User::create([
            'name' => 'Reception',
            'email' => 'reception@example.test',
            'password' => 'password123',
            'role' => 'reception',
            'hotel_id' => $assignedHotel->id,
        ]);

        $this->actingAs($staff)
            ->get('/')
            ->assertOk()
            ->assertSee('Hilton Hotel')
            ->assertDontSee('Other Hotel');

        $this->get('/hotel/other-hotel')->assertNotFound();
        $this->get('/dashboard/hotel/rooms/create')->assertForbidden();
    }

    public function test_guest_can_submit_a_star_rating_for_a_hotel(): void
    {
        $hotel = Hotel::create([
            'name' => 'Hilton Hotel',
            'slug' => 'hilton',
            'province' => 'الخبر',
            'stars' => 5,
        ]);

        $this->post('/hotel/hilton/reviews', [
            'guest_name' => 'Guest',
            'rating' => 5,
            'comment' => 'إقامة رائعة',
        ])->assertRedirect();

        $this->assertDatabaseHas('hotel_reviews', [
            'hotel_id' => $hotel->id,
            'rating' => 5,
            'comment' => 'إقامة رائعة',
        ]);
    }

    public function test_paid_invoice_creates_one_accounting_income_entry(): void
    {
        Storage::fake('public');
        $hotel = Hotel::create([
            'name' => 'Hilton Hotel',
            'slug' => 'hilton',
            'province' => 'الخبر',
            'stars' => 5,
        ]);
        $accountant = User::create([
            'name' => 'Accountant',
            'email' => 'accountant@example.test',
            'password' => 'password123',
            'role' => 'accountant',
            'hotel_id' => $hotel->id,
        ]);
        $invoice = Invoice::create([
            'hotel_id' => $hotel->id,
            'booking_id' => 1,
            'room_id' => 1,
            'amount' => 100,
            'tax' => 0,
            'total' => 100,
            'status' => 'unpaid',
        ]);

        $this->actingAs($accountant)->put(route('invoices.pay', $invoice), [
            'payment_method' => 'cash',
        ])->assertRedirect(route('invoices.show', $invoice));

        $this->actingAs($accountant)->put(route('invoices.pay', $invoice), [
            'payment_method' => 'cash',
        ])->assertRedirect(route('invoices.show', $invoice));

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('accounts', 1);
        $this->assertDatabaseHas('accounts', [
            'hotel_id' => $hotel->id,
            'invoice_id' => $invoice->id,
            'type' => 'income',
            'amount' => 100,
        ]);
        Storage::disk('public')->assertExists($invoice->fresh()->pdf_path);
        $this->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('طباعة الفاتورة');

        $this->get(route('invoice.by-hotel', ['hotelSlug' => $hotel->slug, 'invoice' => $invoice->id]))
            ->assertOk()
            ->assertSee($invoice->invoice_number);
    }

    public function test_platform_admin_can_create_hotel_and_its_manager(): void
    {
        Storage::fake('public');
        $owner = User::create([
            'name' => 'Platform Owner',
            'email' => 'owner@example.test',
            'password' => 'password123',
            'role' => 'super_admin',
            'hotel_id' => null,
        ]);

        $this->actingAs($owner)->post(route('platform.hotels.store'), [
            'name' => 'Damascus Grand',
            'slug' => '',
            'province' => 'دمشق',
            'stars' => 4,
            'email' => 'hotel@example.test',
            'phones' => ['0110000000', '0990000000'],
            'logo' => UploadedFile::fake()->image('logo.png'),
            'images' => [UploadedFile::fake()->image('lobby.png')],
            'manager_name' => 'Hotel Manager',
            'manager_email' => 'manager@example.test',
            'manager_password' => 'password123',
            'manager_password_confirmation' => 'password123',
        ])->assertRedirect(route('platform.hotels.index'));

        $hotel = Hotel::where('slug', 'damascus-grand')->firstOrFail();

        Storage::disk('public')->assertExists($hotel->logo);
        $this->assertNotNull($hotel->logo_url);
        $gallery = json_decode($hotel->images, true);
        $this->assertCount(1, $gallery);
        Storage::disk('public')->assertExists($gallery[0]);
        $this->assertContains(asset('storage/' . $gallery[0]), $hotel->image_urls);
        $this->assertSame('hotel@example.test', $hotel->email);
        $this->assertSame(['0110000000', '0990000000'], json_decode($hotel->phones, true));

        $this->assertDatabaseHas('users', [
            'email' => 'manager@example.test',
            'role' => 'admin',
            'hotel_id' => $hotel->id,
        ]);
    }

    public function test_navigation_shows_only_the_current_role_actions(): void
    {
        $hotel = Hotel::create([
            'name' => 'Hilton Hotel',
            'slug' => 'hilton',
            'province' => 'الخبر',
            'stars' => 5,
        ]);
        $manager = User::create([
            'name' => 'Hotel Manager',
            'email' => 'manager@example.test',
            'password' => 'password123',
            'role' => 'admin',
            'hotel_id' => $hotel->id,
        ]);

        $this->actingAs($manager)->get('/')
            ->assertOk()
            ->assertSee('الغرف')
            ->assertSee('الحجوزات')
            ->assertSee('المحاسبة')
            ->assertDontSee('إدارة الفنادق');
    }

    public function test_checkout_collects_services_and_creates_paid_invoice_pdf_and_income(): void
    {
        Storage::fake('public');
        $hotel = Hotel::create([
            'name' => 'Hilton Hotel',
            'slug' => 'hilton',
            'province' => 'الخبر',
            'stars' => 5,
        ]);
        $receptionist = User::create([
            'name' => 'Reception',
            'email' => 'checkout-reception@example.test',
            'password' => 'password123',
            'role' => 'reception',
            'hotel_id' => $hotel->id,
        ]);
        $roomId = DB::table('rooms')->insertGetId([
            'hotel_id' => $hotel->id,
            'room_number' => '204',
            'status' => 'reserved',
            'needs_cleaning' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $guestId = DB::table('guests')->insertGetId([
            'hotel_id' => $hotel->id,
            'name' => 'Test Guest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $bookingId = DB::table('bookings')->insertGetId([
            'hotel_id' => $hotel->id,
            'room_id' => $roomId,
            'guest_id' => $guestId,
            'created_by' => $receptionist->id,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
            'total_price' => 300,
            'status' => 'checked_in',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($receptionist)
            ->get(route('bookings.checkout.form', $bookingId))
            ->assertOk()
            ->assertSee('قيمة الخدمات الإضافية');

        $this->post(route('bookings.checkout', $bookingId), [
            'service_amount' => 75,
            'service_description' => 'وجبات وخدمة غرف',
            'tax_name' => 'ضريبة القيمة المضافة',
            'tax_rate' => 10,
            'payment_method' => 'cash',
        ])->assertRedirect(route('invoices.show', 1));

        $invoice = Invoice::firstOrFail();
        $this->assertSame('paid', $invoice->status);
        $this->assertEquals(75, $invoice->service_amount);
        $this->assertEquals('ضريبة القيمة المضافة', $invoice->tax_name);
        $this->assertEquals(10, $invoice->tax_rate);
        $this->assertEquals(37.5, $invoice->tax);
        $this->assertEquals(412.5, $invoice->total);
        $this->assertSame('hilton/invoices/' . $invoice->id . '.pdf', $invoice->pdf_path);
        $this->assertDatabaseHas('payments', ['invoice_id' => $invoice->id, 'amount' => 412.5, 'status' => 'success']);
        $this->assertDatabaseHas('accounts', ['invoice_id' => $invoice->id, 'amount' => 412.5, 'type' => 'income']);
        $this->assertDatabaseHas('rooms', ['id' => $roomId, 'status' => 'cleaning', 'needs_cleaning' => true]);
        Storage::disk('public')->assertExists($invoice->fresh()->pdf_path);
    }

    public function test_manager_invoice_and_maintenance_tabs_load_and_maintenance_saves(): void
    {
        $hotel = Hotel::create([
            'name' => 'Hilton Hotel',
            'slug' => 'hilton',
            'province' => 'الخبر',
            'stars' => 5,
        ]);
        $manager = User::create([
            'name' => 'Hotel Manager',
            'email' => 'tabs-manager@example.test',
            'password' => 'password123',
            'role' => 'admin',
            'hotel_id' => $hotel->id,
        ]);

        $this->actingAs($manager)->get(route('invoices.index'))->assertOk()->assertSee('إدارة الفواتير');
        $this->get(route('maintenance.index'))->assertOk()->assertSee('إدارة الصيانة');
        $roomId = DB::table('rooms')->insertGetId([
            'hotel_id' => $hotel->id,
            'room_number' => 'M-1',
            'status' => 'available',
            'needs_cleaning' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->get(route('maintenance.create'))->assertOk()->assertSee('نوع العطل');
        $this->post(route('maintenance.store'), [
            'room_id' => $roomId,
            'issue_type' => 'مكيف الغرفة',
            'priority' => 'high',
            'notes' => 'يحتاج فحصًا',
        ])->assertRedirect(route('maintenance.index'));

        $this->assertDatabaseHas('maintenance', [
            'hotel_id' => $hotel->id,
            'room_id' => $roomId,
            'issue_type' => 'مكيف الغرفة',
            'status' => 'open',
        ]);
    }

    public function test_hotel_manager_can_view_and_update_public_complaints(): void
    {
        $hotel = Hotel::create([
            'name' => 'Hilton Hotel',
            'slug' => 'hilton',
            'province' => 'الخبر',
            'stars' => 5,
        ]);
        $manager = User::create([
            'name' => 'Hotel Manager',
            'email' => 'complaint-manager@example.test',
            'password' => 'password123',
            'role' => 'admin',
            'hotel_id' => $hotel->id,
        ]);
        $complaintId = DB::table('complaints')->insertGetId([
            'hotel_id' => $hotel->id,
            'title' => 'تأخر الخدمة',
            'message' => 'وصل الطلب متأخرًا',
            'status' => 'new',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($manager)
            ->get(route('complaints.index'))
            ->assertOk()
            ->assertSee('تأخر الخدمة');
        $this->get(route('complaints.show', $complaintId))->assertOk();
        $this->put(route('complaints.update', $complaintId), [
            'status' => 'in_progress',
            'response' => 'تم استلام الشكوى',
        ])->assertRedirect(route('complaints.index'));

        $this->assertDatabaseHas('complaints', [
            'id' => $complaintId,
            'hotel_id' => $hotel->id,
            'status' => 'in_progress',
            'response' => 'تم استلام الشكوى',
        ]);
    }

    public function test_platform_admin_can_edit_and_archive_a_hotel_without_deleting_its_history(): void
    {
        Storage::fake('public');
        $hotel = Hotel::create([
            'name' => 'Old Hotel',
            'slug' => 'old-hotel',
            'province' => 'دمشق',
            'stars' => 3,
        ]);
        $owner = User::create([
            'name' => 'Platform Owner',
            'email' => 'archive-owner@example.test',
            'password' => 'password123',
            'role' => 'super_admin',
            'hotel_id' => null,
        ]);

        $this->actingAs($owner)->put(route('platform.hotels.update', $hotel), [
            'name' => 'Updated Hotel',
            'slug' => 'updated-hotel',
            'province' => 'حلب',
            'stars' => 4,
            'email' => 'updated@example.test',
            'phones' => ['0210000000', '0991111111'],
        ])->assertRedirect(route('platform.hotels.index'));

        $hotel->refresh();
        $this->assertSame('Updated Hotel', $hotel->name);
        $this->assertSame(['0210000000', '0991111111'], json_decode($hotel->phones, true));

        $this->delete(route('platform.hotels.destroy', $hotel))
            ->assertRedirect(route('platform.hotels.index'));
        $this->assertSoftDeleted('hotels', ['id' => $hotel->id]);
    }

    public function test_accountant_can_filter_and_download_date_range_report(): void
    {
        Storage::fake('local');
        $hotel = Hotel::create([
            'name' => 'Hilton Hotel',
            'slug' => 'hilton',
            'province' => 'الخبر',
            'stars' => 5,
        ]);
        $accountant = User::create([
            'name' => 'Accountant',
            'email' => 'report-accountant@example.test',
            'password' => 'password123',
            'role' => 'accountant',
            'hotel_id' => $hotel->id,
        ]);
        DB::table('accounts')->insert([
            ['hotel_id' => $hotel->id, 'type' => 'income', 'title' => 'دفعة إقامة', 'amount' => 500, 'transaction_date' => '2026-10-02', 'created_at' => now(), 'updated_at' => now()],
            ['hotel_id' => $hotel->id, 'type' => 'expense', 'title' => 'صيانة', 'amount' => 100, 'transaction_date' => '2026-10-02', 'created_at' => now(), 'updated_at' => now()],
            ['hotel_id' => $hotel->id, 'type' => 'income', 'title' => 'دخل خارج الفترة', 'amount' => 900, 'transaction_date' => '2026-09-01', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $query = ['from' => '2026-10-01', 'to' => '2026-10-03', 'type' => 'all'];
        $this->actingAs($accountant)
            ->get(route('accounts.reports', $query))
            ->assertOk()
            ->assertSee('500.00')
            ->assertSee('100.00')
            ->assertDontSee('دخل خارج الفترة');

        $this->get(route('accounts.reports.pdf', $query))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_bank_statement_separates_syp_and_usd_currencies_without_combining(): void
    {
        Storage::fake('local');
        $hotel = Hotel::create([
            'name' => 'Dual Currency Hotel',
            'slug' => 'dual-hotel',
            'province' => 'دمشق',
            'stars' => 5,
        ]);
        $accountant = User::create([
            'name' => 'Statement Accountant',
            'email' => 'stmt-acc@example.test',
            'password' => 'password123',
            'role' => 'accountant',
            'hotel_id' => $hotel->id,
        ]);

        // Prior transactions (Opening Balance)
        DB::table('accounts')->insert([
            ['hotel_id' => $hotel->id, 'type' => 'income', 'title' => 'رصيد سابق سوري', 'amount' => 10000, 'currency' => 'SYP', 'transaction_date' => '2026-09-20', 'created_at' => now(), 'updated_at' => now()],
            ['hotel_id' => $hotel->id, 'type' => 'income', 'title' => 'رصيد سابق دولار', 'amount' => 50, 'currency' => 'USD', 'transaction_date' => '2026-09-20', 'created_at' => now(), 'updated_at' => now()],
            // Period transactions
            ['hotel_id' => $hotel->id, 'type' => 'income', 'title' => 'دفعة حجز سوري', 'amount' => 70000, 'currency' => 'SYP', 'transaction_date' => '2026-10-02', 'created_at' => now(), 'updated_at' => now()],
            ['hotel_id' => $hotel->id, 'type' => 'income', 'title' => 'دفعة حجز دولار', 'amount' => 150, 'currency' => 'USD', 'transaction_date' => '2026-10-02', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $query = ['from' => '2026-10-01', 'to' => '2026-10-03', 'currency' => 'all'];

        $response = $this->actingAs($accountant)->get(route('accounts.reports', $query));
        $response->assertOk();

        // Must see Syrian pound cumulative balance: 10,000 + 70,000 = 80,000.00
        $response->assertSee('80,000.00');
        // Must see USD cumulative balance: 50 + 150 = 200.00
        $response->assertSee('200.00');
        // Must NOT combine SYP + USD together (e.g. 80,200.00 or 70,150.00)
        $response->assertDontSee('80,200.00');
        $response->assertDontSee('70,150.00');

        // Test PDF output
        $pdfResponse = $this->get(route('accounts.reports.pdf', $query));
        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('content-type', 'application/pdf');
    }

    public function test_public_room_search_excludes_overlapping_reservations(): void
    {
        $hotel = Hotel::create([
            'name' => 'Hilton Hotel',
            'slug' => 'hilton',
            'province' => 'الخبر',
            'stars' => 5,
        ]);
        $typeId = DB::table('room_types')->insertGetId([
            'name' => 'مزدوجة',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $unavailableRoomId = DB::table('rooms')->insertGetId([
            'hotel_id' => $hotel->id,
            'room_number' => '101',
            'room_type_id' => $typeId,
            'status' => 'booked',
            'needs_cleaning' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $availableRoomId = DB::table('rooms')->insertGetId([
            'hotel_id' => $hotel->id,
            'room_number' => '102',
            'room_type_id' => $typeId,
            'status' => 'booked',
            'needs_cleaning' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('bookings')->insert([
            ['hotel_id' => $hotel->id, 'room_id' => $unavailableRoomId, 'check_in' => '2026-11-01', 'check_out' => '2026-11-04', 'total_price' => 300, 'status' => 'confirmed', 'created_at' => now(), 'updated_at' => now()],
            ['hotel_id' => $hotel->id, 'room_id' => $availableRoomId, 'check_in' => '2026-11-10', 'check_out' => '2026-11-12', 'total_price' => 200, 'status' => 'confirmed', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->get('/hotel/hilton/rooms?check_in=2026-11-02&check_out=2026-11-05')
            ->assertOk()
            ->assertSee('102')
            ->assertDontSee('101');
    }

    public function test_super_admin_can_manage_site_settings_and_hero(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.test',
            'password' => 'password123',
            'role' => 'super_admin',
            'hotel_id' => null,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('platform.settings.edit'))
            ->assertOk();

        $this->put(route('platform.settings.update'), [
            'site_name' => 'منصة المسافر العالمية',
            'site_icon' => '🏨',
            'hero_badge' => '✨ بوابتك لحجز أرقى الفنادق والمنتجعات السياحية',
            'hero_title' => 'إقامتك المثالية تبدأ هنا',
            'hero_subtitle' => 'بأعلى معايير الضيافة العالمية',
            'hero_description' => 'اختر من بين أفخم الفنادق والوجهات السياحية، استعرض الغرف المتوفرة لحظياً، واستمتع بخيارات تسعير منفصلة بالليرة السورية والدولار الأمريكي مع تأكيد فوري للحجز.',
            'footer_text' => 'جميع الحقوق محفوظة 2026',
        ])->assertRedirect(route('platform.settings.edit'));

        $this->assertSame('منصة المسافر العالمية', \App\Models\SiteSetting::get('site_name'));
        $this->assertSame('إقامتك المثالية تبدأ هنا', \App\Models\SiteSetting::get('hero_title'));
    }

    public function test_platform_admin_with_granular_permissions(): void
    {
        $assistant = User::create([
            'name' => 'Assistant Admin',
            'email' => 'assistant@example.test',
            'password' => 'password123',
            'role' => 'platform_admin',
            'hotel_id' => null,
            'permissions' => json_encode(['hotels_create', 'appearance']),
        ]);

        $this->assertTrue($assistant->hasPermission('hotels_create'));
        $this->assertTrue($assistant->hasPermission('appearance'));
        $this->assertFalse($assistant->hasPermission('admins'));
        $this->assertFalse($assistant->hasPermission('backups'));

        $this->actingAs($assistant)
            ->get(route('platform.settings.edit'))
            ->assertOk();

        $this->get(route('platform.admins.index'))
            ->assertForbidden();
    }

    public function test_hotel_admin_can_update_printer_settings(): void
    {
        $hotel = Hotel::create([
            'name' => 'Test Hotel',
            'slug' => 'test-hotel',
            'province' => 'دمشق',
            'stars' => 4,
        ]);

        $manager = User::create([
            'name' => 'Hotel Manager',
            'email' => 'printer-manager@example.test',
            'password' => 'password123',
            'role' => 'hotel_admin',
            'hotel_id' => $hotel->id,
        ]);

        $this->actingAs($manager)
            ->get(route('admin.printer-settings.edit'))
            ->assertOk();

        $this->put(route('admin.printer-settings.update'), [
            'invoice_printer' => 'EPSON TM-T20',
            'report_printer' => 'HP LaserJet Pro',
            'invoice_format' => 'thermal_80mm',
            'scanner_source' => 'camera',
            'auto_print' => '1',
        ])->assertRedirect(route('admin.printer-settings.edit'));

        $hotel->refresh();
        $this->assertSame('EPSON TM-T20', $hotel->printer_settings_list['invoice_printer']);
        $this->assertTrue($hotel->printer_settings_list['auto_print']);
    }

    public function test_hotel_backup_download_and_restore_cycle(): void
    {
        $hotel = Hotel::create([
            'name' => 'Backup Test Hotel',
            'slug' => 'backup-hotel',
            'province' => 'دمشق',
            'stars' => 5,
        ]);

        $manager = User::create([
            'name' => 'Backup Manager',
            'email' => 'backup-manager@example.test',
            'password' => 'password123',
            'role' => 'hotel_admin',
            'hotel_id' => $hotel->id,
        ]);

        $room = \App\Models\Room::create([
            'hotel_id' => $hotel->id,
            'room_number' => '999',
            'status' => 'available',
            'price_syp' => 50000,
            'price_usd' => 50,
        ]);

        $response = $this->actingAs($manager)->get(route('admin.backup.download'));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/json');

        $content = json_decode($response->getContent(), true);
        $this->assertSame('Backup Test Hotel', $content['hotel']['name']);
        $this->assertCount(1, $content['data']['rooms']);
        $this->assertSame('999', $content['data']['rooms'][0]['room_number']);

        // Now test restore with an extra room
        $content['data']['rooms'][] = [
            'id' => 9999,
            'room_number' => '888',
            'room_type_id' => null,
            'price_syp' => 40000,
            'price_usd' => 40,
            'status' => 'available',
        ];

        $uploadedJson = UploadedFile::fake()->createWithContent('backup.json', json_encode($content));

        $this->post(route('admin.backup.restore'), [
            'restore_mode' => 'merge',
            'backup_file' => $uploadedJson,
        ])->assertRedirect();

        $this->assertDatabaseHas('rooms', [
            'hotel_id' => $hotel->id,
            'room_number' => '888',
        ]);
    }

    public function test_hotel_admin_can_view_reports_dashboard_with_separated_currencies(): void
    {
        $hotel = Hotel::create([
            'name' => 'Analytics Hotel',
            'slug' => 'analytics-hotel',
            'province' => 'دمشق',
            'stars' => 5,
        ]);

        $admin = User::create([
            'name' => 'Hotel Manager',
            'email' => 'analytics-manager@example.test',
            'password' => 'password123',
            'role' => 'hotel_admin',
            'hotel_id' => $hotel->id,
        ]);

        // Create SYP invoice and USD invoice
        DB::table('invoices')->insert([
            [
                'hotel_id' => $hotel->id,
                'booking_id' => 1,
                'room_id' => 1,
                'invoice_number' => 'INV-SYP-01',
                'amount' => 150000,
                'service_amount' => 0,
                'tax' => 0,
                'total' => 150000,
                'currency' => 'SYP',
                'status' => 'paid',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'hotel_id' => $hotel->id,
                'booking_id' => 2,
                'room_id' => 2,
                'invoice_number' => 'INV-USD-01',
                'amount' => 300,
                'service_amount' => 0,
                'tax' => 0,
                'total' => 300,
                'currency' => 'USD',
                'status' => 'paid',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports'));
        $response->assertOk();

        // Must see both separated wallets and metrics
        $response->assertSee('محفظة الليرة السورية');
        $response->assertSee('محفظة الدولار الأمريكي');
        $response->assertSee('150,000.00');
        $response->assertSee('300.00');
        $response->assertSee('نسبة الإشغال والطاقة الاستيعابية للغرف');
        $response->assertSee('حركة وتدفق الحجوزات');
        $response->assertSee('آخر الفواتير المالية الصادرة');
    }

    public function test_hotel_admin_dashboard_separates_syp_and_usd_currencies_strictly(): void
    {
        $hotel = Hotel::create([
            'name' => 'Dashboard Currency Hotel',
            'slug' => 'dash-currency-hotel',
            'province' => 'حمص',
            'stars' => 4,
        ]);

        $admin = User::create([
            'name' => 'Dash Manager',
            'email' => 'dash-currency-mgr@example.test',
            'password' => 'password123',
            'role' => 'hotel_admin',
            'hotel_id' => $hotel->id,
        ]);

        // Insert paid invoices today in both currencies
        DB::table('invoices')->insert([
            [
                'hotel_id' => $hotel->id,
                'booking_id' => 10,
                'room_id' => 10,
                'amount' => 85000,
                'total' => 85000,
                'currency' => 'SYP',
                'status' => 'paid',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'hotel_id' => $hotel->id,
                'booking_id' => 11,
                'room_id' => 11,
                'amount' => 120,
                'total' => 120,
                'currency' => 'USD',
                'status' => 'paid',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        // Insert accounts in both currencies
        DB::table('accounts')->insert([
            ['hotel_id' => $hotel->id, 'type' => 'income', 'title' => 'دخل سوري', 'amount' => 95000, 'currency' => 'SYP', 'transaction_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()],
            ['hotel_id' => $hotel->id, 'type' => 'expense', 'title' => 'صرف سوري', 'amount' => 15000, 'currency' => 'SYP', 'transaction_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()],
            ['hotel_id' => $hotel->id, 'type' => 'income', 'title' => 'دخل دولار', 'amount' => 250, 'currency' => 'USD', 'transaction_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()],
            ['hotel_id' => $hotel->id, 'type' => 'expense', 'title' => 'صرف دولار', 'amount' => 50, 'currency' => 'USD', 'transaction_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();

        // Must see today revenue separated by currency
        $response->assertSee('85,000.00');
        $response->assertSee('120.00');

        // Must see accounting center separated
        $response->assertSee('محفظة الليرة السورية (SYP)');
        $response->assertSee('محفظة الدولار الأمريكي (USD)');
        $response->assertSee('95,000.00');
        $response->assertSee('80,000.00 ل.س'); // net profit SYP
        $response->assertSee('250.00');
        $response->assertSee('200.00'); // net profit USD
    }

    public function test_language_switch_and_responsive_elements(): void
    {
        // 1. Initial page load in default Arabic
        $resAr = $this->get('/');
        $resAr->assertOk();
        $resAr->assertSee('lang="ar"', false);
        $resAr->assertSee('dir="rtl"', false);
        $resAr->assertSee('id="themeToggleBtn"', false);
        $resAr->assertSee('id="mobileNavDrawer"', false);

        // 2. Switch to English
        $switchRes = $this->get('/locale/en');
        $switchRes->assertRedirect();
        $switchRes->assertSessionHas('locale', 'en');

        // 3. Page with English session
        $resEn = $this->withSession(['locale' => 'en'])->get('/');
        $resEn->assertOk();
        $resEn->assertSee('lang="en"', false);
        $resEn->assertSee('dir="ltr"', false);
        $resEn->assertSee('Home');
    }

    public function test_visitor_registration_and_email_verification_flow(): void
    {
        // 1. Visit registration page
        $this->get('/register')->assertOk()->assertSee('إنشاء حساب جديد');

        // 2. Submit valid registration
        $res = $this->post('/register', [
            'name' => 'سامر الحلبي',
            'email' => 'samer@example.test',
            'phone' => '0988776655',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', [
            'email' => 'samer@example.test',
            'role' => 'guest',
            'is_verified' => false,
        ]);

        $user = User::where('email', 'samer@example.test')->first();
        $this->assertNotNull($user->verification_code);

        // 3. Unverified user redirected to verify-code
        $this->actingAs($user)->get('/my-bookings')->assertRedirect(route('verification.notice'));

        // 4. Submit verification code
        $verifyRes = $this->actingAs($user)->post(route('verification.verify'), [
            'code' => $user->verification_code,
        ]);

        $verifyRes->assertRedirect(route('home'));
        $this->assertTrue($user->fresh()->is_verified);
    }

    public function test_google_auth_simulation_creates_and_logs_in_verified_guest(): void
    {
        $res = $this->get(route('auth.google', ['email' => 'google.user@example.test', 'name' => 'زائر جوجل']));
        $res->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', [
            'email' => 'google.user@example.test',
            'role' => 'guest',
            'is_verified' => true,
        ]);
        $this->assertAuthenticated();
    }

    public function test_platform_admin_can_toggle_hotel_visibility_with_eye_button(): void
    {
        $hotel = Hotel::create([
            'name' => 'فندق دمشق الدولي',
            'slug' => 'damascus-hotel',
            'province' => 'دمشق',
            'stars' => 5,
            'is_visible' => true,
        ]);

        $admin = User::create([
            'name' => 'Platform Super Admin',
            'email' => 'super-eye@example.test',
            'password' => 'password123',
            'role' => 'super_admin',
            'hotel_id' => null,
        ]);

        // Visible initially
        $this->get('/')->assertSee('فندق دمشق الدولي');

        // Toggle visibility
        $toggleRes = $this->actingAs($admin)->post(route('platform.hotels.toggle-visibility', $hotel));
        $toggleRes->assertRedirect();
        $this->assertFalse($hotel->fresh()->is_visible);

        // Hidden from public homepage
        $this->get('/')->assertDontSee('فندق دمشق الدولي');
    }

    public function test_guest_profile_and_hotel_guest_record_two_way_sync(): void
    {
        $hotel = Hotel::create([
            'name' => 'فندق حلب الشهباء',
            'slug' => 'aleppo-hotel',
            'province' => 'حلب',
            'stars' => 4,
        ]);

        $guestUser = User::create([
            'name' => 'كريم النجار',
            'email' => 'kareem@example.test',
            'password' => 'password123',
            'role' => 'guest',
            'is_verified' => true,
            'first_name' => 'كريم',
            'last_name' => 'النجار',
            'nationality' => 'سوري',
        ]);

        // 1. Guest updates their profile in guest portal
        $this->actingAs($guestUser)->put(route('guest.profile.update'), [
            'first_name' => 'كريم',
            'father_name' => 'عدنان',
            'last_name' => 'النجار',
            'phone' => '0933221100',
            'nationality' => 'سوري',
            'id_type' => 'national_id',
            'national_id' => '01020304050',
            'address' => 'حلب - الجميلية',
        ])->assertRedirect();

        $this->assertEquals('عدنان', $guestUser->fresh()->father_name);
        $this->assertEquals('01020304050', $guestUser->fresh()->national_id);

        // 2. Create hotel receptionist and guest record linked to user
        $reception = User::create([
            'name' => 'موظف الاستقبال',
            'email' => 'rec-sync@example.test',
            'password' => 'password123',
            'role' => 'reception',
            'hotel_id' => $hotel->id,
        ]);

        $hotelGuest = \App\Models\Guest::create([
            'hotel_id' => $hotel->id,
            'user_id' => $guestUser->id,
            'name' => 'كريم النجار',
            'email' => 'kareem@example.test',
            'phone' => '0933221100',
            'father_name' => 'عدنان',
            'nationality' => 'سوري',
        ]);

        // 3. Receptionist updates guest record from dashboard
        $this->actingAs($reception)->put(route('guests.update', $hotelGuest), [
            'name' => 'كريم عدنان النجار',
            'first_name' => 'كريم',
            'father_name' => 'عدنان فوزي',
            'last_name' => 'النجار',
            'nationality' => 'سوري',
            'phone' => '0944556677',
            'national_id' => '01020304050',
            'address' => 'حلب - العزيزية',
        ])->assertRedirect();

        // 4. Check two-way sync reflected on user profile
        $freshUser = $guestUser->fresh();
        $this->assertEquals('عدنان فوزي', $freshUser->father_name);
        $this->assertEquals('0944556677', $freshUser->phone);
        $this->assertEquals('حلب - العزيزية', $freshUser->address);
    }

    public function test_promotional_offers_management_and_public_view(): void
    {
        $hotel = Hotel::create([
            'name' => 'فندق اللاذقية ريزورت',
            'slug' => 'latakia-resort',
            'province' => 'اللاذقية',
            'stars' => 5,
        ]);

        $manager = User::create([
            'name' => 'مدير المنتجع',
            'email' => 'latakia-mgr@example.test',
            'password' => 'password123',
            'role' => 'hotel_admin',
            'hotel_id' => $hotel->id,
        ]);

        // Create offer
        $this->actingAs($manager)->post(route('admin.offers.store'), [
            'title' => 'عرض الصيف الذهبي',
            'description' => 'خصم خاص 25% مع إفطار مجاني',
            'discount_percentage' => 25,
            'special_price' => 500000,
            'special_price_usd' => 50,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'is_active' => 1,
        ])->assertRedirect(route('admin.offers.index'));

        $this->assertDatabaseHas('offers', [
            'hotel_id' => $hotel->id,
            'title' => 'عرض الصيف الذهبي',
            'discount_percentage' => 25,
        ]);

        // Public visitor views offers
        $this->get(route('offers.index'))
            ->assertOk()
            ->assertSee('عرض الصيف الذهبي')
            ->assertSee('500,000')
            ->assertSee('50.00');
    }

    public function test_role_based_notifications_system_and_triggers(): void
    {
        $hotel = Hotel::create([
            'name' => 'فندق دمشق الدولي',
            'slug' => 'damascus-international',
            'province' => 'دمشق',
            'stars' => 5,
        ]);

        $manager = User::create([
            'name' => 'مدير الفندق',
            'email' => 'mgr-notif@example.test',
            'password' => 'password123',
            'role' => 'hotel_admin',
            'hotel_id' => $hotel->id,
        ]);

        $reception = User::create([
            'name' => 'موظف استقبال',
            'email' => 'reception-notif@example.test',
            'password' => 'password123',
            'role' => 'reception',
            'hotel_id' => $hotel->id,
        ]);

        $cleaner = User::create([
            'name' => 'عامل التدبير',
            'email' => 'cleaner-notif@example.test',
            'password' => 'password123',
            'role' => 'cleaner',
            'hotel_id' => $hotel->id,
        ]);

        $maintenance = User::create([
            'name' => 'فني الصيانة',
            'email' => 'maint-notif@example.test',
            'password' => 'password123',
            'role' => 'maintenance',
            'hotel_id' => $hotel->id,
        ]);

        $guestUser = User::create([
            'name' => 'النزيل سامر',
            'email' => 'samer-guest@example.test',
            'password' => 'password123',
            'role' => 'guest',
            'is_verified' => true,
        ]);

        $room = Room::create([
            'hotel_id' => $hotel->id,
            'room_number' => '501',
            'price' => 200000,
            'price_syp' => 200000,
            'price_usd' => 50,
            'status' => 'available',
        ]);

        // 1. Guest creates a booking -> manager & reception receive notification
        $this->actingAs($guestUser)->post(route('hotel.book', $hotel->slug), [
            'room_id' => $room->id,
            'guest_name' => 'سامر الأحمد',
            'guest_phone' => '0933111222',
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
        ])->assertRedirect(route('guest.bookings'));

        $this->assertEquals(1, $manager->unreadNotificationsCount());
        $this->assertEquals(1, $reception->unreadNotificationsCount());
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $manager->id,
            'type' => 'booking_created',
        ]);

        $booking = Booking::where('hotel_id', $hotel->id)->latest('id')->firstOrFail();

        // 2. Manager confirms booking -> guest receives notification
        $this->actingAs($manager)->post(route('bookings.confirm', $booking->id), [
            'currency' => 'USD',
        ])->assertRedirect(route('bookings.index'));

        $this->assertEquals(1, $guestUser->unreadNotificationsCount());
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $guestUser->id,
            'type' => 'booking_confirmed',
        ]);

        // 3. Reception checks in guest -> guest receives notification
        $this->actingAs($reception)->post(route('bookings.checkin', $booking->id))
            ->assertSessionHas('status');

        $this->assertEquals(2, $guestUser->unreadNotificationsCount());
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $guestUser->id,
            'type' => 'check_in',
        ]);

        // 4. Reception submits cleaning request for active room -> cleaner receives notification
        $this->actingAs($reception)->post(route('cleaning.request'), [
            'room_id' => $room->id,
            'notes' => 'تنظيف دوري مستعجل',
        ])->assertSessionHas('status');

        $this->assertEquals(1, $cleaner->unreadNotificationsCount());
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $cleaner->id,
            'type' => 'cleaning_request',
        ]);

        // 4.5. Reception checks out guest -> guest receives invoice notification & cleaner receives checkout cleaning alert
        $this->actingAs($reception)->post(route('bookings.checkout', $booking->id), [
            'service_amount' => 0,
            'payment_method' => 'cash',
        ])->assertRedirect();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $guestUser->id,
            'type' => 'check_out',
        ]);
        $this->assertEquals(2, $cleaner->unreadNotificationsCount());

        // 5. Maintenance request created -> maintenance staff receives notification
        $this->actingAs($manager)->post(route('maintenance.store'), [
            'room_id' => $room->id,
            'issue_type' => 'عطل تكييف',
            'priority' => 'high',
            'notes' => 'صوت مرتفع في الوحدة الداخلية',
        ])->assertRedirect(route('maintenance.index'));

        $this->assertEquals(1, $maintenance->unreadNotificationsCount());
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $maintenance->id,
            'type' => 'maintenance_request',
        ]);

        // 6. Promotional offer created -> all guests receive notification
        $this->actingAs($manager)->post(route('admin.offers.store'), [
            'title' => 'عرض نهاية الأسبوع الاستثنائي',
            'description' => 'خصم 30% لجميع الغرف',
            'discount_percentage' => 30,
            'special_price' => 150000,
            'special_price_usd' => 35,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
        ])->assertRedirect(route('admin.offers.index'));

        $this->assertEquals(4, $guestUser->unreadNotificationsCount());
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $guestUser->id,
            'type' => 'promo_offer',
        ]);

        // 7. Guest reads notifications and marks all as read
        $notif = AppNotification::where('user_id', $guestUser->id)->firstOrFail();
        $this->actingAs($guestUser)->get(route('notifications.read', $notif->id));
        $this->assertEquals(3, $guestUser->fresh()->unreadNotificationsCount());

        $this->actingAs($guestUser)->post(route('notifications.read-all'));
        $this->assertEquals(0, $guestUser->fresh()->unreadNotificationsCount());

        $this->actingAs($guestUser)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('مركز الإشعارات والتنبيهات');
    }

    public function test_booking_com_style_homepage_search_and_filters(): void
    {
        $hotelA = Hotel::create([
            'name' => 'فندق النيربين الفاخر',
            'slug' => 'nairabeen-luxury',
            'province' => 'حلب',
            'stars' => 5,
            'amenities' => '["واي فاي", "مسبح", "إفطار"]',
        ]);

        $hotelB = Hotel::create([
            'name' => 'فندق الكورنيش الاقتصادي',
            'slug' => 'corniche-economy',
            'province' => 'طرطوس',
            'stars' => 3,
            'amenities' => '["واي فاي"]',
        ]);

        $roomA = Room::create([
            'hotel_id' => $hotelA->id,
            'room_number' => '101',
            'price' => 500000,
            'price_syp' => 500000,
            'price_usd' => 100,
            'status' => 'available',
        ]);

        $roomB = Room::create([
            'hotel_id' => $hotelB->id,
            'room_number' => '201',
            'price' => 150000,
            'price_syp' => 150000,
            'price_usd' => 20,
            'status' => 'available',
        ]);

        // Overlapping booking on Room A between day 5 and day 10
        Booking::create([
            'hotel_id' => $hotelA->id,
            'room_id' => $roomA->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
            'status' => 'confirmed',
            'total_price' => 500,
            'total_price_usd' => 500,
            'currency' => 'USD',
        ]);

        // Search dates overlapping with Hotel A's only room -> Hotel A excluded, Hotel B returned
        $response = $this->get(route('hotels.list', [
            'check_in' => now()->addDays(6)->toDateString(),
            'check_out' => now()->addDays(8)->toDateString(),
        ]));
        $response->assertOk()
            ->assertSee('فندق الكورنيش الاقتصادي')
            ->assertDontSee('فندق النيربين الفاخر');

        // Search dates with no overlap -> Both returned
        $response2 = $this->get(route('hotels.list', [
            'check_in' => now()->addDays(12)->toDateString(),
            'check_out' => now()->addDays(15)->toDateString(),
        ]));
        $response2->assertOk()
            ->assertSee('فندق الكورنيش الاقتصادي')
            ->assertSee('فندق النيربين الفاخر');

        // Sort by price ascending (from lowest to highest)
        $responseSort = $this->get(route('hotels.list', ['sort' => 'price_asc']));
        $responseSort->assertOk();
        $content = $responseSort->getContent();
        $posEconomy = strpos($content, 'فندق الكورنيش الاقتصادي');
        $posLuxury = strpos($content, 'فندق النيربين الفاخر');
        $this->assertTrue($posEconomy < $posLuxury, 'Hotel B ($20) should appear before Hotel A ($100) when sorted by price asc');

        // Filter by amenity: "مسبح" -> Only Hotel A returned
        $responseAmenity = $this->get(route('hotels.list', ['amenity' => 'مسبح']));
        $responseAmenity->assertOk()
            ->assertSee('فندق النيربين الفاخر')
            ->assertDontSee('فندق الكورنيش الاقتصادي');
    }

    public function test_hotel_owner_role_read_only_and_access_permissions(): void
    {
        $hotel = Hotel::create([
            'name' => 'Royal Palace Hotel',
            'slug' => 'royal-palace',
            'province' => 'دمشق',
            'stars' => 5,
        ]);

        $manager = User::create([
            'name' => 'Hotel Manager',
            'email' => 'manager-royal@example.test',
            'password' => 'password123',
            'role' => 'admin',
            'hotel_id' => $hotel->id,
        ]);

        // 1. Hotel manager creates hotel_owner user
        $response = $this->actingAs($manager)->post(route('employees.store'), [
            'name' => 'Sheikh Sultan (Owner)',
            'email' => 'owner-royal@example.test',
            'password' => 'owner123456',
            'password_confirmation' => 'owner123456',
            'role' => 'hotel_owner',
            'phone' => '0999888777',
        ]);
        $response->assertRedirect(route('employees.index'));

        $owner = User::where('email', 'owner-royal@example.test')->firstOrFail();
        $this->assertSame('hotel_owner', $owner->role);
        $this->assertTrue($owner->isHotelOwner());

        // 2. Hotel owner redirect goes to admin.dashboard
        $this->actingAs($owner)->get(route('dashboard.redirect'))
            ->assertRedirect(route('admin.dashboard'));

        // 3. Hotel owner can read/view all dashboard sections
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('وضع اطلاع مالك الفندق');
        $this->get(route('bookings.index'))->assertOk();
        $this->get(route('rooms.index'))->assertOk();
        $this->get(route('room-types.index'))->assertOk();
        $this->get(route('accounts.index'))->assertOk();
        $this->get(route('invoices.index'))->assertOk();
        $this->get(route('guests.index'))->assertOk();
        $this->get(route('employees.index'))->assertOk();
        $this->get(route('complaints.index'))->assertOk();
        $this->get(route('maintenance.index'))->assertOk();
        $this->get(route('cleaning.index'))->assertOk();
        $this->get(route('admin.reports'))->assertOk();
        $this->get(route('admin.backup.index'))->assertOk();
        $this->get(route('admin.printer-settings.edit'))->assertOk();
        $this->get(route('admin.hotel-profile.edit'))->assertOk();

        // 4. Hotel owner attempting mutating operations is blocked with 403
        $this->post(route('rooms.store'), [
            'room_number' => '999',
            'price_per_night' => 100,
        ])->assertForbidden();

        $this->post(route('employees.store'), [
            'name' => 'Hacker',
            'email' => 'hacker@example.test',
            'password' => '12345678',
            'role' => 'reception',
        ])->assertForbidden();

        $this->post(route('accounts.store'), [
            'amount' => 500,
            'type' => 'expense',
            'category' => 'other',
            'description' => 'Test expense',
        ])->assertForbidden();
    }

    public function test_message_notification_and_unread_counter_with_red_badge(): void
    {
        $hotel = Hotel::create([
            'name' => 'Damascus Grand Hotel',
            'slug' => 'damascus-grand',
            'province' => 'دمشق',
            'stars' => 5,
        ]);

        $manager = User::create([
            'name' => 'Manager Damascus',
            'email' => 'damascus-manager@example.test',
            'password' => 'password123',
            'role' => 'admin',
            'hotel_id' => $hotel->id,
        ]);

        $guestUser = User::create([
            'name' => 'Guest User Ahmad',
            'email' => 'ahmad-guest@example.test',
            'password' => 'guest123',
            'role' => 'guest',
            'is_verified' => true,
        ]);

        $guest = \App\Models\Guest::create([
            'hotel_id' => $hotel->id,
            'user_id' => $guestUser->id,
            'name' => 'Guest User Ahmad',
            'email' => 'ahmad-guest@example.test',
            'phone' => '0933333333',
        ]);

        // Initially no unread messages
        $this->assertEquals(0, $manager->unreadMessagesCount());
        $this->assertEquals(0, $guestUser->unreadMessagesCount());

        // Guest sends a message to the hotel
        $this->actingAs($guestUser)->post(route('guest.messages.send'), [
            'hotel_id' => $hotel->id,
            'subject' => 'استفسار بخصوص موعد الوصول',
            'body' => 'مرحباً، هل يمكن تسجيل الوصول في تمام الساعة 11 صباحاً؟',
        ])->assertSessionHas('success');

        // Manager should now have 1 unread message
        $this->assertEquals(1, $manager->unreadMessagesCount());

        // Manager receives an AppNotification with type 'message_received'
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $manager->id,
            'type' => 'message_received',
            'title' => 'رسالة جديدة من نزيل',
        ]);

        // Manager views the page with header: should render mail icon with unread badge count
        $response = $this->actingAs($manager)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('messagesMailboxBtn');
        $response->assertSee('bg-rose-600'); // Red badge class

        // Manager views the message detail -> marks as read
        $message = \App\Models\Message::where('receiver_id', $manager->id)->firstOrFail();
        $this->actingAs($manager)->get(route('messages.show', $message->id))->assertOk();
        $this->assertEquals(0, $manager->fresh()->unreadMessagesCount());

        // Manager sends a reply message to the guest
        $this->actingAs($manager)->post(route('messages.store'), [
            'guest_id' => $guest->id,
            'subject' => 'رد: استفسار بخصوص موعد الوصول',
            'body' => 'أهلاً بك، نعم الغرفة ستكون جاهزة بانتظاركم.',
        ])->assertRedirect(route('messages.sent'));

        // Guest user now has 1 unread message and an AppNotification
        $this->assertEquals(1, $guestUser->unreadMessagesCount());
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $guestUser->id,
            'type' => 'message_received',
            'title' => 'رسالة جديدة',
        ]);

        // Guest opens mailbox -> marks messages as read
        $this->actingAs($guestUser)->get(route('guest.messages'))->assertOk();
        $this->assertEquals(0, $guestUser->fresh()->unreadMessagesCount());
    }

    public function test_desktop_client_rejects_guest_logins_with_message(): void
    {
        $guest = User::create([
            'name' => 'Guest User',
            'email' => 'guest_app@example.com',
            'password' => bcrypt('password123'),
            'role' => 'guest',
            'is_verified' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'guest_app@example.com',
            'password' => 'password123',
            'client_type' => 'desktop',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
        ]);
        $this->assertStringContainsString('راجع المسؤول', $response->json('message'));
    }

    public function test_desktop_client_allows_hotel_staff_and_manager(): void
    {
        $hotel = Hotel::create([
            'name' => 'فندق الياسمين',
            'slug' => 'al-yasmin',
            'province' => 'دمشق',
        ]);

        $staff = User::create([
            'name' => 'موظف الاستقبال',
            'hotel_id' => $hotel->id,
            'role' => 'reception',
            'email' => 'reception_app@hotel.com',
            'password' => bcrypt('password123'),
            'is_verified' => true,
            'permissions' => ['accounts', 'invoices'],
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'reception_app@hotel.com',
            'password' => 'password123',
            'client_type' => 'desktop',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'token',
            'user' => [
                'id',
                'name',
                'role',
                'permissions',
            ],
        ]);
        $this->assertContains('accounts', $response->json('user.permissions'));
    }

    public function test_mobile_client_allows_guest_login_and_browsing(): void
    {
        $guest = User::create([
            'name' => 'نزيل الجوال',
            'email' => 'guest_mobile@example.com',
            'password' => bcrypt('password123'),
            'role' => 'guest',
            'is_verified' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'guest_mobile@example.com',
            'password' => 'password123',
            'client_type' => 'mobile',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'token',
            'user',
        ]);
    }

    public function test_public_can_browse_hotels_and_destinations_api_without_auth(): void
    {
        Hotel::create([
            'name' => 'فندق الشام بالاس',
            'slug' => 'cham-palace',
            'province' => 'دمشق',
            'is_visible' => true,
        ]);

        $response = $this->getJson('/api/hotels');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data',
        ]);

        $destinationsResponse = $this->getJson('/api/destinations');
        $destinationsResponse->assertStatus(200);
    }

    public function test_manager_can_grant_extra_permissions_to_staff_via_api(): void
    {
        $hotel = Hotel::create([
            'name' => 'فندق الفردوس',
            'slug' => 'al-ferdous',
            'province' => 'حلب',
        ]);

        $manager = User::create([
            'name' => 'المدير العام',
            'hotel_id' => $hotel->id,
            'role' => 'hotel_admin',
            'email' => 'manager_api@hotel.com',
            'password' => bcrypt('password123'),
        ]);

        $employee = User::create([
            'name' => 'موظف الاستقبال',
            'hotel_id' => $hotel->id,
            'role' => 'reception',
            'email' => 'staff_api@hotel.com',
            'password' => bcrypt('password123'),
            'permissions' => [],
        ]);

        $this->actingAs($manager, 'sanctum');

        $response = $this->putJson("/api/staff/employees/{$employee->id}/permissions", [
            'permissions' => ['accounts', 'invoices', 'rooms'],
        ]);

        $response->assertStatus(200);
        $this->assertEquals(['accounts', 'invoices', 'rooms'], $employee->fresh()->permissions);
    }

    public function test_download_routes_exist_and_respond(): void
    {
        $home = $this->get('/');
        $home->assertStatus(200);
        $home->assertSee('downloads');
        $home->assertSee('تطبيق الكمبيوتر (Windows)');
        $home->assertSee('تطبيق الأندرويد (Android APK)');
        $home->assertSee('تطبيق الآيفون (Apple iOS)');
        $home->assertSee('قريباً');

        $windowsZip = $this->get('/downloads/windows');
        $windowsZip->assertStatus(200);

        $windowsExe = $this->get('/downloads/windows-exe');
        $windowsExe->assertStatus(200);

        $androidApk = $this->get('/downloads/android');
        $androidApk->assertStatus(200);
    }

    public function test_desktop_workstation_full_operational_suite(): void
    {
        $hotel = Hotel::create([
            'name' => 'فندق دمشق الدولي',
            'slug' => 'damascus-international',
            'province' => 'دمشق',
            'is_visible' => true,
        ]);

        $manager = User::create([
            'name' => 'مدير المحطة',
            'email' => 'station_mgr@hotel.test',
            'password' => bcrypt('password123'),
            'role' => 'hotel_admin',
            'hotel_id' => $hotel->id,
            'is_verified' => true,
        ]);

        $roomType = \App\Models\RoomType::create([
            'name' => 'جناح تنفيذي',
        ]);

        $this->actingAs($manager, 'sanctum');

        // 1. Create Room from Desktop
        $roomRes = $this->postJson('/api/staff/rooms', [
            'room_number' => '405',
            'room_type_id' => $roomType->id,
            'price_usd' => 120,
            'price_syp' => 1500000,
            'status' => 'available',
        ]);
        $roomRes->assertStatus(200);
        $roomId = $roomRes->json('room.id');
        $this->assertDatabaseHas('rooms', ['id' => $roomId, 'room_number' => '405']);

        // 2. Walk-in Booking directly from Desktop with Check-in
        $bookingRes = $this->postJson('/api/staff/bookings', [
            'room_id' => $roomId,
            'guest_name' => 'خالد السعيد',
            'guest_phone' => '0933112233',
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'currency' => 'USD',
            'status' => 'checked_in',
        ]);
        $bookingRes->assertStatus(200);
        $bookingId = $bookingRes->json('booking.id');

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => 'checked_in',
            'currency' => 'USD',
        ]);
        $this->assertDatabaseHas('rooms', [
            'id' => $roomId,
            'status' => 'occupied',
        ]);

        // 3. Check-out Booking from Desktop (auto moves room to cleaning, issues paid invoice & accounting entry)
        $checkoutRes = $this->postJson("/api/staff/bookings/{$bookingId}/check-out", [
            'service_amount' => 30,
            'service_description' => 'ميني بار وخدمة غسيل',
        ]);
        $checkoutRes->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => 'checked_out',
        ]);
        $this->assertDatabaseHas('rooms', [
            'id' => $roomId,
            'status' => 'cleaning',
            'needs_cleaning' => true,
        ]);
        $this->assertDatabaseHas('invoices', [
            'booking_id' => $bookingId,
            'status' => 'paid',
            'currency' => 'USD',
        ]);
        $this->assertDatabaseHas('accounts', [
            'hotel_id' => $hotel->id,
            'type' => 'income',
            'currency' => 'USD',
        ]);

        // 4. Housekeeping Cleaning Queue & Complete Cleaning
        $cleanListRes = $this->getJson('/api/staff/cleaning');
        $cleanListRes->assertStatus(200);
        $this->assertTrue(collect($cleanListRes->json('data'))->contains('id', $roomId));

        $completeCleanRes = $this->postJson("/api/staff/cleaning/{$roomId}/complete", [
            'notes' => 'تم تعقيم الجناح بالكامل وتجهيزه',
        ]);
        $completeCleanRes->assertStatus(200);

        $this->assertDatabaseHas('rooms', [
            'id' => $roomId,
            'status' => 'available',
            'needs_cleaning' => false,
        ]);

        // 5. Messenger 1-on-1 Chat Contacts & History
        $guestUser = User::create([
            'name' => 'نزيل مراسل',
            'email' => 'guest_chat@test.com',
            'password' => bcrypt('password123'),
            'role' => 'guest',
            'is_verified' => true,
        ]);

        // Send 1-on-1 message
        $sendRes = $this->postJson('/api/chat/send', [
            'receiver_id' => $guestUser->id,
            'body' => 'أهلاً بك في فندقنا، نرجو لك إقامة سعيدة!',
        ]);
        $sendRes->assertStatus(200);

        // Contacts list returns the guest
        $contactsRes = $this->getJson('/api/chat/contacts');
        $contactsRes->assertStatus(200);
        $this->assertTrue(collect($contactsRes->json('data'))->contains('id', $guestUser->id));

        // 1-on-1 message stream
        $msgRes = $this->getJson("/api/chat/messages?contact_id={$guestUser->id}");
        $msgRes->assertStatus(200);
        $this->assertNotEmpty($msgRes->json('data'));
    }

    public function test_desktop_enhanced_modules_and_dashboard_charts(): void
    {
        $hotel = Hotel::create([
            'name' => 'فندق الفخامة الملكية',
            'slug' => 'royal-luxury-hotel',
            'stars' => 5,
            'is_visible' => true,
        ]);

        $manager = User::create([
            'name' => 'مدير المحطة',
            'email' => 'station_mgr@hotel.test',
            'password' => bcrypt('password123'),
            'role' => 'hotel_admin',
            'hotel_id' => $hotel->id,
            'is_verified' => true,
        ]);

        $this->actingAs($manager, 'sanctum');

        // 1. Room Types CRUD
        $storeTypeRes = $this->postJson('/api/staff/room-types', [
            'name' => 'جناح ملكي سوبريور',
            'description' => 'إطلالة بانورامية مع جاكوزي خاص',
        ]);
        $storeTypeRes->assertStatus(200);
        $typeId = $storeTypeRes->json('data.id');
        $this->assertDatabaseHas('room_types', ['id' => $typeId, 'name' => 'جناح ملكي سوبريور']);

        $updateTypeRes = $this->putJson("/api/staff/room-types/{$typeId}", [
            'name' => 'جناح ملكي ديلوكس',
            'description' => 'تحديث الوصف',
        ]);
        $updateTypeRes->assertStatus(200);
        $this->assertDatabaseHas('room_types', ['id' => $typeId, 'name' => 'جناح ملكي ديلوكس']);

        // 2. Comprehensive Reports API
        $reportsRes = $this->getJson('/api/staff/reports');
        $reportsRes->assertStatus(200);
        $reportsRes->assertJsonStructure([
            'status',
            'data' => [
                'periods' => ['today', 'this_week', 'this_month', 'this_year'],
                'rooms',
                'bookings_summary',
                'operations',
            ],
        ]);

        // 3. Printer & Scanner Settings
        $printerSettingsRes = $this->getJson('/api/staff/printer-settings');
        $printerSettingsRes->assertStatus(200);

        $updatePrinterRes = $this->postJson('/api/staff/printer-settings', [
            'invoice_printer' => 'Epson TM-T88VI Thermal',
            'report_printer' => 'HP LaserJet Pro MFP',
            'invoice_format' => 'thermal_80mm',
            'scanner_source' => 'scanner_hardware',
            'auto_print' => true,
        ]);
        $updatePrinterRes->assertStatus(200);
        $updatePrinterRes->assertJsonFragment(['invoice_format' => 'thermal_80mm']);

        // 4. Complaints & Feedback CRUD
        $complaintRes = $this->postJson('/api/staff/complaints', [
            'title' => 'طلب وسائد إضافية',
            'message' => 'يرجى تزويد الغرفة بوسائد ريش إضافية',
        ]);
        $complaintRes->assertStatus(200);
        $complaintId = $complaintRes->json('data.id');

        $updateComplaintRes = $this->putJson("/api/staff/complaints/{$complaintId}", [
            'status' => 'closed',
            'response' => 'تم تلبية الطلب من قبل طاقم الإشراف الداخلي فوراً.',
        ]);
        $updateComplaintRes->assertStatus(200);
        $this->assertDatabaseHas('complaints', ['id' => $complaintId, 'status' => 'closed']);

        // 5. Deletion Logs API & Summary
        \App\Models\DeletionLog::create([
            'hotel_id' => $hotel->id,
            'user_id' => $manager->id,
            'record_type' => 'invoice',
            'reference_number' => 'INV-TEST-009',
            'guest_name' => 'سعيد مراد',
            'amount' => 450000,
            'currency' => 'SYP',
            'reason' => 'تم إلغاء الفاتورة لخطأ في حساب الخدمات',
            'deleted_at' => now(),
        ]);

        $deletionLogsRes = $this->getJson('/api/staff/deletion-logs');
        $deletionLogsRes->assertStatus(200);
        $deletionLogsRes->assertJsonFragment(['reference_number' => 'INV-TEST-009']);

        // 6. Barcode & QR Scanner Lookup
        $lookupRes = $this->getJson('/api/staff/scanner/lookup?code=INV-TEST-999999');
        $lookupRes->assertStatus(404);

        // 7. Rich Dashboard Payload with Charts Data
        $dashRes = $this->getJson('/api/staff/dashboard');
        $dashRes->assertStatus(200);
        $dashRes->assertJsonStructure([
            'status',
            'data' => [
                'hotel_name',
                'rooms' => [
                    'total',
                    'available',
                    'occupied',
                    'cleaning',
                    'maintenance',
                    'occupancy_rate',
                ],
                'wallets' => [
                    'syp' => ['income', 'expense', 'profit'],
                    'usd' => ['income', 'expense', 'profit'],
                ],
                'charts' => [
                    'months_labels',
                    'monthly_revenue_syp',
                    'monthly_revenue_usd',
                    'monthly_bookings',
                    'occupancy_rate',
                ],
                'deletion_logs_summary',
            ],
        ]);
    }

    public function test_hotel_manager_cannot_access_app_manager_while_super_admin_can()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('app_banners')) {
            \Illuminate\Support\Facades\Schema::create('app_banners', function ($table) {
                $table->id();
                $table->string('title');
                $table->string('subtitle')->nullable();
                $table->string('image')->nullable();
                $table->string('target_type')->default('none');
                $table->unsignedBigInteger('target_id')->nullable();
                $table->string('link_url')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        if (!\Illuminate\Support\Facades\Schema::hasTable('app_broadcasts')) {
            \Illuminate\Support\Facades\Schema::create('app_broadcasts', function ($table) {
                $table->id();
                $table->unsignedBigInteger('sender_id')->nullable();
                $table->string('type')->default('message');
                $table->string('title');
                $table->text('body');
                $table->text('attachment')->nullable();
                $table->string('target_audience')->default('all');
                $table->integer('recipients_count')->default(0);
                $table->timestamps();
            });
        }
        $hotel = Hotel::create([
            'name' => 'فندق دمشق للشام',
            'slug' => 'damascus-sham',
            'province' => 'دمشق',
            'is_visible' => true,
        ]);

        $manager = User::create([
            'name' => 'مدير فندق',
            'email' => 'hotel_manager_test@example.com',
            'password' => bcrypt('password123'),
            'role' => 'hotel_manager',
            'hotel_id' => $hotel->id,
            'is_verified' => true,
        ]);

        $superAdmin = User::create([
            'name' => 'سوبر ادمن',
            'email' => 'super_admin_test@example.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'is_verified' => true,
        ]);

        // Hotel manager gets 403 Forbidden
        $resForbidden = $this->actingAs($manager)->get(route('admin.app-manager.index'));
        $resForbidden->assertStatus(403);

        // Super admin gets 200 OK
        $resAllowed = $this->actingAs($superAdmin)->get(route('admin.app-manager.index'));
        $resAllowed->assertStatus(200);
    }

    public function test_guest_stays_page_and_api_show_accurate_night_rate_and_currency()
    {
        $hotel = Hotel::create([
            'name' => 'فندق الياسمين الدمشقي',
            'slug' => 'jasmine-damascus',
            'province' => 'دمشق',
            'is_visible' => true,
        ]);

        $staff = User::create([
            'name' => 'موظف استقبال',
            'email' => 'reception_stays@example.com',
            'password' => bcrypt('password123'),
            'role' => 'hotel_admin',
            'hotel_id' => $hotel->id,
            'is_verified' => true,
        ]);

        $room = Room::create([
            'hotel_id' => $hotel->id,
            'room_number' => '404',
            'price' => 450000,
            'price_usd' => 90,
            'status' => 'available',
        ]);

        $guest = \App\Models\Guest::create([
            'hotel_id' => $hotel->id,
            'name' => 'سامر الخطيب',
            'phone' => '0944001122',
            'id_number' => '01020304050',
            'nationality' => 'سوري',
        ]);

        // 1. USD Stay: 2 nights at $90/night = $180
        $bookingUsd = Booking::create([
            'hotel_id' => $hotel->id,
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'created_by' => $staff->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'currency' => 'USD',
            'total_price' => 180,
            'total_price_usd' => 180,
            'status' => 'checked_out',
        ]);

        $invoiceUsd = Invoice::create([
            'hotel_id' => $hotel->id,
            'booking_id' => $bookingUsd->id,
            'room_id' => $room->id,
            'amount' => 180,
            'total' => 180,
            'currency' => 'USD',
            'status' => 'paid',
        ]);

        \App\Models\Payment::create([
            'invoice_id' => $invoiceUsd->id,
            'amount' => 180,
            'currency' => 'USD',
            'status' => 'success',
            'paid_at' => now(),
        ]);

        // 2. SYP Stay: 3 nights at 450,000 SYP/night = 1,350,000 SYP
        $bookingSyp = Booking::create([
            'hotel_id' => $hotel->id,
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'created_by' => $staff->id,
            'check_in' => now()->subDays(10)->toDateString(),
            'check_out' => now()->subDays(7)->toDateString(),
            'currency' => 'SYP',
            'total_price' => 1350000,
            'total_price_usd' => 0,
            'status' => 'checked_out',
        ]);

        $invoiceSyp = Invoice::create([
            'hotel_id' => $hotel->id,
            'booking_id' => $bookingSyp->id,
            'room_id' => $room->id,
            'amount' => 1350000,
            'total' => 1350000,
            'currency' => 'SYP',
            'status' => 'paid',
        ]);

        \App\Models\Payment::create([
            'invoice_id' => $invoiceSyp->id,
            'amount' => 1350000,
            'currency' => 'SYP',
            'status' => 'success',
            'paid_at' => now()->subDays(7),
        ]);

        // Verify Web View
        $viewRes = $this->actingAs($staff)->get(route('guests.history', $guest));
        $viewRes->assertStatus(200);
        $viewRes->assertSee('$ 90.00');
        $viewRes->assertSee('USD / ليلة');
        $viewRes->assertSee('450,000');
        $viewRes->assertSee('ل.س / ليلة');

        // Verify API Stays endpoint
        $apiRes = $this->actingAs($staff, 'sanctum')->getJson("/api/staff/guests/{$guest->id}/stays");
        $apiRes->assertStatus(200);
        $stays = $apiRes->json('stays');
        $this->assertCount(2, $stays);

        $usdStay = collect($stays)->firstWhere('currency', 'USD');
        $this->assertEquals(90.0, (float)$usdStay['nightly_rate']);
        $this->assertEquals(180.0, (float)$usdStay['invoice_total']);
        $this->assertEquals(180.0, (float)$usdStay['actual_paid']);
        $this->assertTrue($usdStay['is_paid']);

        $sypStay = collect($stays)->firstWhere('currency', 'SYP');
        $this->assertEquals(450000.0, (float)$sypStay['nightly_rate']);
        $this->assertEquals(1350000.0, (float)$sypStay['invoice_total']);
        $this->assertEquals(1350000.0, (float)$sypStay['actual_paid']);
        $this->assertTrue($sypStay['is_paid']);
    }

    public function test_staff_can_update_booking_dates_and_status()
    {
        $hotel = Hotel::create([
            'name' => 'فندق دمشق التراثي',
            'slug' => 'damascus-heritage',
            'city' => 'دمشق',
            'phone' => '+96311223344',
            'is_visible' => true,
        ]);

        $user = User::create([
            'name' => 'موظف الاستقبال',
            'email' => 'reception@damascus-hotel.sy',
            'password' => bcrypt('password123'),
            'role' => 'reception',
            'hotel_id' => $hotel->id,
            'is_verified' => true,
        ]);

        $roomType = RoomType::create([
            'hotel_id' => $hotel->id,
            'name' => 'جناح ملكي',
            'base_price' => 100,
        ]);

        $room = Room::create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'floor' => 1,
            'price' => 1500000,
            'price_usd' => 100,
            'status' => 'available',
        ]);

        $guest = Guest::create([
            'hotel_id' => $hotel->id,
            'name' => 'فادي الأحمد',
            'phone' => '+963944112233',
        ]);

        $booking = Booking::create([
            'hotel_id' => $hotel->id,
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'status' => 'pending',
            'currency' => 'USD',
            'total_price' => 200,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $newCheckIn = now()->addDay()->toDateString();
        $newCheckOut = now()->addDays(4)->toDateString();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->putJson("/api/staff/bookings/{$booking->id}", [
            'check_in' => $newCheckIn,
            'check_out' => $newCheckOut,
            'status' => 'confirmed',
            'total_price' => 300,
            'currency' => 'USD',
            'notes' => 'تم تعديل الموعد بناء على طلب النزيل',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $booking->refresh();
        $this->assertEquals($newCheckIn, $booking->check_in);
        $this->assertEquals($newCheckOut, $booking->check_out);
        $this->assertEquals('confirmed', $booking->status);
        $this->assertEquals(300, (float) $booking->total_price);
    }

    public function test_staff_can_delete_account_voucher_and_it_records_in_deletion_logs()
    {
        $hotel = Hotel::create([
            'name' => 'فندق دمشق التراثي 2',
            'slug' => 'damascus-heritage-2',
            'city' => 'دمشق',
            'phone' => '+96311223344',
            'is_visible' => true,
        ]);

        $user = User::create([
            'name' => 'المحاسب',
            'email' => 'accountant@damascus-hotel.sy',
            'password' => bcrypt('password123'),
            'role' => 'accountant',
            'hotel_id' => $hotel->id,
            'is_verified' => true,
        ]);

        $account = Account::create([
            'hotel_id' => $hotel->id,
            'title' => 'مصاريف صيانة',
            'type' => 'expense',
            'category' => 'مصاريف صيانة',
            'amount' => 50,
            'currency' => 'USD',
            'description' => 'شراء قطع غيار للتكييف',
            'transaction_date' => now()->toDateString(),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/staff/accounts/{$account->id}", [
            'reason' => 'تم إدخال القيد بالخطأ مرتين',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);

        $this->assertDatabaseHas('deletion_logs', [
            'hotel_id' => $hotel->id,
            'record_type' => 'account',
            'record_id' => $account->id,
            'reason' => 'تم إدخال القيد بالخطأ مرتين',
            'amount' => 50,
            'currency' => 'USD',
        ]);
    }

    public function test_hotel_slug_public_invoice_route_renders_by_number_and_id()
    {
        $hotel = Hotel::create([
            'name' => 'فندق دمشق التراثي 3',
            'slug' => 'damascus-heritage-3',
            'city' => 'دمشق',
            'phone' => '+96311223344',
            'is_visible' => true,
        ]);

        $user = User::create([
            'name' => 'موظف',
            'email' => 'staff3@damascus-hotel.sy',
            'password' => bcrypt('password123'),
            'role' => 'reception',
            'hotel_id' => $hotel->id,
            'is_verified' => true,
        ]);

        $roomType = RoomType::create([
            'hotel_id' => $hotel->id,
            'name' => 'جناح ملكي',
            'base_price' => 100,
        ]);

        $room = Room::create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomType->id,
            'room_number' => '301',
            'floor' => 3,
            'price' => 1500000,
            'price_usd' => 100,
            'status' => 'available',
        ]);

        $guest = Guest::create([
            'hotel_id' => $hotel->id,
            'name' => 'زياد خوري',
            'phone' => '+963944887766',
        ]);

        $booking = Booking::create([
            'hotel_id' => $hotel->id,
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'status' => 'checked_out',
            'currency' => 'USD',
            'total_price' => 200,
        ]);

        $invoice = Invoice::create([
            'hotel_id' => $hotel->id,
            'booking_id' => $booking->id,
            'room_id' => $room->id,
            'invoice_number' => 'INV-2026-DH001',
            'amount' => 200,
            'total' => 200,
            'currency' => 'USD',
            'status' => 'paid',
        ]);

        $resByNumber = $this->get("/{$hotel->slug}/invoices/{$invoice->invoice_number}");
        $resByNumber->assertStatus(200);
        $resByNumber->assertSee('INV-2026-DH001');
        $resByNumber->assertSee('فندق دمشق التراثي 3');

        $resById = $this->get("/{$hotel->slug}/invoices/{$invoice->id}");
        $resById->assertStatus(200);
        $resById->assertSee('INV-2026-DH001');

        $resWrongSlug = $this->get("/other-hotel/invoices/{$invoice->id}");
        $resWrongSlug->assertStatus(404);
    }

    public function test_multi_tenant_notification_isolation_super_admin_staff_and_guest(): void
    {
        // 1. Super Admin (Site Manager)
        $superAdmin = User::create([
            'name' => 'مدير الموقع الرئيسي',
            'email' => 'superadmin-iso@example.test',
            'password' => 'password123',
            'role' => 'super_admin',
            'hotel_id' => null,
            'is_verified' => true,
        ]);

        // 2. Hotel A & Staff
        $hotelA = Hotel::create([
            'name' => 'فندق دمشق بلازا',
            'slug' => 'damascus-plaza-iso',
            'province' => 'دمشق',
            'stars' => 4,
        ]);
        $managerA = User::create([
            'name' => 'مدير فندق دمشق',
            'email' => 'mgr-a-iso@example.test',
            'password' => 'password123',
            'role' => 'hotel_admin',
            'hotel_id' => $hotelA->id,
            'is_verified' => true,
        ]);
        $receptionA = User::create([
            'name' => 'استقبال فندق دمشق',
            'email' => 'rec-a-iso@example.test',
            'password' => 'password123',
            'role' => 'reception',
            'hotel_id' => $hotelA->id,
            'is_verified' => true,
        ]);

        // 3. Hotel B & Staff
        $hotelB = Hotel::create([
            'name' => 'فندق حلب الشهباء',
            'slug' => 'aleppo-shahbaa-iso',
            'province' => 'حلب',
            'stars' => 5,
        ]);
        $managerB = User::create([
            'name' => 'مدير فندق حلب',
            'email' => 'mgr-b-iso@example.test',
            'password' => 'password123',
            'role' => 'hotel_admin',
            'hotel_id' => $hotelB->id,
            'is_verified' => true,
        ]);
        $receptionB = User::create([
            'name' => 'استقبال فندق حلب',
            'email' => 'rec-b-iso@example.test',
            'password' => 'password123',
            'role' => 'reception',
            'hotel_id' => $hotelB->id,
            'is_verified' => true,
        ]);

        // 4. Guests
        $guestUser1 = User::create([
            'name' => 'النزيل سامر دمشق',
            'email' => 'samer-iso@example.test',
            'password' => 'password123',
            'role' => 'guest',
            'hotel_id' => null,
            'is_verified' => true,
        ]);
        $guestProfile1 = Guest::create([
            'hotel_id' => $hotelA->id,
            'user_id' => $guestUser1->id,
            'name' => 'سامر دمشق',
            'phone' => '0933111000',
        ]);

        $guestUser2 = User::create([
            'name' => 'النزيل طارق حلب',
            'email' => 'tarek-iso@example.test',
            'password' => 'password123',
            'role' => 'guest',
            'hotel_id' => null,
            'is_verified' => true,
        ]);
        $guestProfile2 = Guest::create([
            'hotel_id' => $hotelB->id,
            'user_id' => $guestUser2->id,
            'name' => 'طارق حلب',
            'phone' => '0933222000',
        ]);

        // Create rooms
        $roomA = Room::create([
            'hotel_id' => $hotelA->id,
            'room_number' => '101',
            'price' => 150000,
            'price_syp' => 150000,
            'price_usd' => 40,
            'status' => 'available',
        ]);

        // --- Step A: Trigger Hotel A operational notifications ---
        // 1. sendToRole for Hotel A
        \App\Services\NotificationService::sendToRole(
            ['reception', 'hotel_admin'],
            $hotelA->id,
            'حجز جديد فندق دمشق',
            'تفاصيل الحجز في فندق دمشق',
            'booking_created'
        );

        // 2. sendToHotelStaff for Hotel A
        \App\Services\NotificationService::sendToHotelStaff(
            $hotelA->id,
            'تسجيل دخول فندق دمشق',
            'تم تسجيل الدخول في الغرفة 101',
            'check_in'
        );

        // 3. sendToUser for Guest 1 (personal booking notification)
        \App\Services\NotificationService::sendToUser(
            $guestUser1,
            'تأكيد حجزك في فندق دمشق',
            'تم تأكيد حجزك رقم #501',
            'booking_confirmed',
            '🛎️',
            '/guest/bookings',
            $hotelA->id
        );

        // --- VERIFY ISOLATION AFTER HOTEL A EVENTS ---
        // Super Admin MUST have 0 notifications
        $this->assertEquals(0, $superAdmin->fresh()->unreadNotificationsCount(), 'Super Admin should have 0 hotel notifications');
        $this->assertEquals(0, $superAdmin->fresh()->notifications()->count(), 'Super Admin should query 0 hotel notifications');

        // Hotel A staff must have their notifications
        $this->assertEquals(2, $managerA->fresh()->unreadNotificationsCount(), 'Manager A should receive Hotel A notifications');
        $this->assertEquals(2, $receptionA->fresh()->unreadNotificationsCount(), 'Reception A should receive Hotel A notifications');

        // Hotel B staff MUST have 0 notifications
        $this->assertEquals(0, $managerB->fresh()->unreadNotificationsCount(), 'Manager B must NOT receive Hotel A notifications');
        $this->assertEquals(0, $receptionB->fresh()->unreadNotificationsCount(), 'Reception B must NOT receive Hotel A notifications');

        // Guest 1 must have 1 notification
        $this->assertEquals(1, $guestUser1->fresh()->unreadNotificationsCount(), 'Guest 1 should receive their booking notification');

        // Guest 2 MUST have 0 notifications
        $this->assertEquals(0, $guestUser2->fresh()->unreadNotificationsCount(), 'Guest 2 must NOT receive Guest 1 notification');

        // --- Step B: Trigger Hotel B operational notifications ---
        \App\Services\NotificationService::sendToHotelStaff(
            $hotelB->id,
            'طلب تنظيف فندق حلب',
            'الغرفة 202 بحاجة لتنظيف',
            'cleaning_request'
        );

        \App\Services\NotificationService::sendToUser(
            $guestUser2,
            'فاتورتك في فندق حلب جاهزة',
            'فاتورة رقم #990 جاهزة للاطلاع',
            'check_out',
            '🚪',
            '/guest/bookings',
            $hotelB->id
        );

        // --- VERIFY ISOLATION AFTER HOTEL B EVENTS ---
        // Super Admin still MUST have 0 notifications
        $this->assertEquals(0, $superAdmin->fresh()->unreadNotificationsCount(), 'Super Admin should still have 0 notifications');

        // Hotel A staff count must NOT have increased
        $this->assertEquals(2, $managerA->fresh()->unreadNotificationsCount(), 'Manager A count should remain 2');

        // Hotel B staff receives Hotel B notification
        $this->assertEquals(1, $managerB->fresh()->unreadNotificationsCount(), 'Manager B should receive Hotel B notification');
        $this->assertEquals(1, $receptionB->fresh()->unreadNotificationsCount(), 'Reception B should receive Hotel B notification');

        // Guest 2 receives their own notification
        $this->assertEquals(1, $guestUser2->fresh()->unreadNotificationsCount(), 'Guest 2 should receive their notification');

        // Guest 1 still has only 1 notification
        $this->assertEquals(1, $guestUser1->fresh()->unreadNotificationsCount(), 'Guest 1 should still have 1 notification');

        // --- Step C: Trigger Platform-Level Alert for Super Admin ---
        \App\Services\NotificationService::sendToRole(
            'super_admin',
            null,
            '🚨 إنذار أمني للمنصة',
            'رصد محاولات دخول مشبوهة',
            'security_alert'
        );

        // Super Admin now receives the platform alert
        $this->assertEquals(1, $superAdmin->fresh()->unreadNotificationsCount(), 'Super Admin should receive platform security alert');
        $this->assertEquals(1, $superAdmin->fresh()->notifications()->count());
        $this->assertEquals('🚨 إنذار أمني للمنصة', $superAdmin->fresh()->notifications()->first()->title);

        // Neither Hotel A staff, Hotel B staff, nor Guests received the platform security alert
        $this->assertEquals(2, $managerA->fresh()->unreadNotificationsCount());
        $this->assertEquals(1, $managerB->fresh()->unreadNotificationsCount());
        $this->assertEquals(1, $guestUser1->fresh()->unreadNotificationsCount());
        $this->assertEquals(1, $guestUser2->fresh()->unreadNotificationsCount());
    }
}

