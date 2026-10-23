<?php

namespace Tests\Feature;

use App\Models\CrmLead;
use App\Models\Hotel;
use App\Models\HotelSubscription;
use App\Models\MarketingCampaign;
use App\Models\Room;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlatformCrmAndSubscriptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('hotels')) {
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
        }

        if (!Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id');
                $table->string('room_number');
                $table->string('type');
                $table->decimal('price', 10, 2);
                $table->decimal('price_usd', 10, 2)->default(0);
                $table->string('status')->default('available');
                $table->unsignedSmallInteger('floor')->default(1);
                $table->unsignedTinyInteger('capacity')->default(2);
                $table->text('description')->nullable();
                $table->text('features')->nullable();
                $table->text('images')->nullable();
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
                $table->date('check_in')->nullable();
                $table->date('check_out')->nullable();
                $table->decimal('total_price', 10, 2)->default(0);
                $table->decimal('total_price_usd', 10, 2)->default(0);
                $table->string('currency')->default('SYP');
                $table->string('status')->default('confirmed');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('guests')) {
            Schema::create('guests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id');
                $table->string('name');
                $table->string('phone')->nullable();
                $table->string('id_number')->nullable();
                $table->string('nationality')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('app_notifications')) {
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
        }

        if (!Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('group')->default('general');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('role');
                $table->foreignId('hotel_id')->nullable();
                $table->string('phone')->nullable();
                $table->text('permissions')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('personal_access_tokens')) {
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

        if (!Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->decimal('price_monthly_syp', 14, 2)->default(0);
                $table->decimal('price_yearly_syp', 14, 2)->default(0);
                $table->decimal('price_monthly_usd', 10, 2)->default(0);
                $table->decimal('price_yearly_usd', 10, 2)->default(0);
                $table->integer('max_rooms')->default(-1);
                $table->integer('max_users')->default(-1);
                $table->text('features')->nullable();
                $table->string('badge')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hotel_subscriptions')) {
            Schema::create('hotel_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('hotel_id')->index();
                $table->unsignedBigInteger('plan_id')->index();
                $table->string('status', 30)->default('active');
                $table->string('billing_cycle', 20)->default('monthly');
                $table->string('currency', 10)->default('USD');
                $table->decimal('price_paid', 14, 2)->default(0);
                $table->date('starts_at');
                $table->date('expires_at');
                $table->date('trial_ends_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('subscription_invoices')) {
            Schema::create('subscription_invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('hotel_id')->index();
                $table->unsignedBigInteger('subscription_id')->nullable()->index();
                $table->unsignedBigInteger('plan_id')->nullable()->index();
                $table->string('invoice_number', 50)->unique();
                $table->decimal('amount', 14, 2);
                $table->string('currency', 10)->default('USD');
                $table->string('status', 30)->default('paid');
                $table->string('payment_method', 50)->default('bank_transfer');
                $table->timestamp('paid_at')->nullable();
                $table->date('due_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_number', 50)->unique();
                $table->unsignedBigInteger('hotel_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->string('subject');
                $table->string('department', 50)->default('technical');
                $table->string('priority', 20)->default('medium');
                $table->string('status', 30)->default('open');
                $table->timestamp('last_reply_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('support_ticket_messages')) {
            Schema::create('support_ticket_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->text('message');
                $table->boolean('is_staff_reply')->default(false);
                $table->text('attachments')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('crm_leads')) {
            Schema::create('crm_leads', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('hotel_name');
                $table->string('phone', 50)->nullable();
                $table->string('email')->nullable();
                $table->string('province', 100)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('source', 50)->default('website');
                $table->string('status', 50)->default('new');
                $table->decimal('expected_revenue', 14, 2)->default(0);
                $table->string('currency', 10)->default('USD');
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->unsignedBigInteger('hotel_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('marketing_campaigns')) {
            Schema::create('marketing_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('code', 50)->unique();
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->decimal('discount_fixed', 14, 2)->default(0);
                $table->string('currency', 10)->default('USD');
                $table->string('type', 50)->default('discount_coupon');
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->integer('max_uses')->default(-1);
                $table->integer('used_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_super_admin_can_access_platform_crm_dashboard_and_metrics(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@platform.test',
            'password' => bcrypt('secret123'),
            'role' => 'super_admin',
            'is_verified' => true,
        ]);

        $hotel = Hotel::create([
            'name' => 'Sheraton Damascus',
            'slug' => 'sheraton-damascus',
            'province' => 'دمشق',
            'stars' => 5,
        ]);

        SubscriptionPlan::seedDefaultsIfNeeded();
        $proPlan = SubscriptionPlan::where('slug', 'pro')->first();

        HotelSubscription::create([
            'hotel_id' => $hotel->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'currency' => 'USD',
            'price_paid' => 99.00,
            'starts_at' => now()->subDays(10),
            'expires_at' => now()->addDays(20),
        ]);

        $res = $this->actingAs($superAdmin)->get('/platform/crm');
        $res->assertStatus(200);
        $res->assertSee('Platform CRM');
        $res->assertSee('Sheraton Damascus');
        $res->assertSee($proPlan->name);
    }

    public function test_platform_admin_can_manage_subscription_plans_and_assign_to_hotel(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin_plans@platform.test',
            'password' => bcrypt('secret123'),
            'role' => 'super_admin',
            'is_verified' => true,
        ]);

        $hotel = Hotel::create([
            'name' => 'Four Seasons',
            'slug' => 'four-seasons',
            'province' => 'دمشق',
            'stars' => 5,
        ]);

        SubscriptionPlan::seedDefaultsIfNeeded();
        $starterPlan = SubscriptionPlan::where('slug', 'starter')->first();

        // Assign plan to hotel with automatic invoice creation
        $assignRes = $this->actingAs($superAdmin)->post('/platform/crm/subscriptions/assign', [
            'hotel_id' => $hotel->id,
            'plan_id' => $starterPlan->id,
            'billing_cycle' => 'yearly',
            'currency' => 'USD',
            'price_paid' => 490.00,
            'duration_months' => 12,
            'status' => 'active',
            'create_invoice' => '1',
            'payment_method' => 'bank_transfer',
            'notes' => 'العقد السنوي الأول للفندق',
        ]);

        $assignRes->assertRedirect();
        $this->assertDatabaseHas('hotel_subscriptions', [
            'hotel_id' => $hotel->id,
            'plan_id' => $starterPlan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
        ]);
        $this->assertDatabaseHas('subscription_invoices', [
            'hotel_id' => $hotel->id,
            'amount' => 490.00,
            'currency' => 'USD',
            'status' => 'paid',
        ]);
    }

    public function test_platform_crm_leads_and_marketing_campaigns(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'crm_lead_admin@platform.test',
            'password' => bcrypt('secret123'),
            'role' => 'super_admin',
            'is_verified' => true,
        ]);

        // Add lead
        $leadRes = $this->actingAs($superAdmin)->post('/platform/crm/leads', [
            'name' => 'الأستاذ سامر النجار',
            'hotel_name' => 'منتجع الشاطئ الأزرق',
            'phone' => '+963987654321',
            'email' => 'samer@bluebeach.sy',
            'province' => 'اللاذقية',
            'city' => 'اللاذقية',
            'source' => 'referral',
            'status' => 'demo_scheduled',
            'expected_revenue' => 1200.00,
            'currency' => 'USD',
            'notes' => 'مهتم بباقة المؤسسات لـ 120 غرفة',
        ]);

        $leadRes->assertRedirect();
        $this->assertDatabaseHas('crm_leads', [
            'hotel_name' => 'منتجع الشاطئ الأزرق',
            'status' => 'demo_scheduled',
            'expected_revenue' => 1200.00,
        ]);

        // Add marketing campaign
        $campRes = $this->actingAs($superAdmin)->post('/platform/crm/campaigns', [
            'title' => 'عرض بداية الموسم 2026',
            'code' => 'SEASON2026',
            'discount_percent' => 20,
            'currency' => 'USD',
            'type' => 'discount_coupon',
            'max_uses' => 50,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addMonths(2)->toDateString(),
        ]);

        $campRes->assertRedirect();
        $this->assertDatabaseHas('marketing_campaigns', [
            'code' => 'SEASON2026',
            'discount_percent' => 20,
        ]);
    }

    public function test_hotel_admin_can_view_own_subscription_and_request_plan(): void
    {
        $hotel = Hotel::create([
            'name' => 'Cham Palace',
            'slug' => 'cham-palace',
            'province' => 'دمشق',
            'stars' => 5,
        ]);

        $manager = User::create([
            'name' => 'Manager Cham',
            'email' => 'manager@cham.test',
            'password' => bcrypt('secret123'),
            'role' => 'hotel_admin',
            'hotel_id' => $hotel->id,
            'is_verified' => true,
        ]);

        SubscriptionPlan::seedDefaultsIfNeeded();
        $proPlan = SubscriptionPlan::where('slug', 'pro')->first();

        HotelSubscription::create([
            'hotel_id' => $hotel->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'currency' => 'USD',
            'price_paid' => 99.00,
            'starts_at' => now()->subDays(5),
            'expires_at' => now()->addDays(25),
        ]);

        $res = $this->actingAs($manager)->get('/dashboard/hotel/subscription');
        $res->assertStatus(200);
        $res->assertSee('باقة واشتراك الفندق في النظام');
        $res->assertSee($proPlan->name);
        $res->assertSee('نشط وساري');

        // Request plan upgrade creates technical support ticket under billing
        $upgradePlan = SubscriptionPlan::where('slug', 'enterprise')->first();
        $requestRes = $this->actingAs($manager)->post('/dashboard/hotel/subscription/request', [
            'plan_id' => $upgradePlan->id,
            'billing_cycle' => 'yearly',
            'notes' => 'نود الترقية قبل بداية الموسم الصيفي',
        ]);

        $requestRes->assertRedirect(route('admin.subscription.index'));
        $this->assertDatabaseHas('support_tickets', [
            'hotel_id' => $hotel->id,
            'department' => 'billing',
        ]);
    }

    public function test_hotel_admin_and_staff_support_ticket_flow(): void
    {
        $hotel = Hotel::create([
            'name' => 'Grand Hotel',
            'slug' => 'grand-hotel',
            'province' => 'حلب',
            'stars' => 4,
        ]);

        $manager = User::create([
            'name' => 'Grand Manager',
            'email' => 'manager@grand.test',
            'password' => bcrypt('secret123'),
            'role' => 'hotel_admin',
            'hotel_id' => $hotel->id,
            'is_verified' => true,
        ]);

        $superAdmin = User::create([
            'name' => 'Platform Support Specialist',
            'email' => 'support@platform.test',
            'password' => bcrypt('secret123'),
            'role' => 'super_admin',
            'is_verified' => true,
        ]);

        // Manager creates support ticket via web portal
        $createRes = $this->actingAs($manager)->post('/dashboard/hotel/support', [
            'subject' => 'مشكلة في طباعة الباركود على طابعة إبسون الحرارية',
            'department' => 'technical',
            'priority' => 'high',
            'message' => 'عند النقر على طباعة حرارية تظهر هوامش عريضة، نرجو المساعدة في ضبط الهوامش.',
        ]);

        $createRes->assertRedirect();
        $ticket = SupportTicket::where('hotel_id', $hotel->id)->first();
        $this->assertNotNull($ticket);
        $this->assertEquals('high', $ticket->priority);
        $this->assertEquals('technical', $ticket->department);

        // Platform Support replies from Platform CRM
        $replyRes = $this->actingAs($superAdmin)->post("/platform/crm/tickets/{$ticket->id}/reply", [
            'message' => 'تم استلام طلبكم، يرجى ضبط حجم الورق في خيارات الطابعة إلى 80x297mm واختيار بدون هوامش.',
            'status' => 'answered',
        ]);
        $replyRes->assertRedirect();

        $this->assertDatabaseHas('support_ticket_messages', [
            'ticket_id' => $ticket->id,
            'is_staff_reply' => true,
        ]);
        $this->assertEquals('answered', $ticket->fresh()->status);
    }

    public function test_desktop_staff_api_subscription_and_support_tickets(): void
    {
        $hotel = Hotel::create([
            'name' => 'Damas Rose Hotel',
            'slug' => 'damas-rose',
            'province' => 'دمشق',
            'stars' => 5,
        ]);

        $staff = User::create([
            'name' => 'Reception Agent',
            'email' => 'reception@damasrose.test',
            'password' => bcrypt('secret123'),
            'role' => 'reception',
            'hotel_id' => $hotel->id,
            'is_verified' => true,
        ]);

        SubscriptionPlan::seedDefaultsIfNeeded();
        $proPlan = SubscriptionPlan::where('slug', 'pro')->first();

        HotelSubscription::create([
            'hotel_id' => $hotel->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'currency' => 'USD',
            'price_paid' => 99.00,
            'starts_at' => now()->subDays(2),
            'expires_at' => now()->addDays(28),
        ]);

        $token = $staff->createToken('desktop_token')->plainTextToken;

        // 1. Test Subscription Info Endpoint
        $subRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/staff/subscription');

        $subRes->assertStatus(200);
        $subRes->assertJson([
            'status' => 'success',
            'subscription' => [
                'plan_slug' => 'pro',
                'is_expired' => false,
            ],
        ]);

        // 2. Test Create Support Ticket via API
        $ticketRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/staff/support/tickets', [
                'subject' => 'استفسار عن إضافة طابعة فواتير جديدة',
                'department' => 'technical',
                'priority' => 'medium',
                'message' => 'كيف يمكنني ربط طابعة شبكية جديدة من داخل تطبيق سطح المكتب؟',
            ]);

        $ticketRes->assertStatus(200);
        $ticketRes->assertJson([
            'status' => 'success',
        ]);
        $ticketId = $ticketRes->json('ticket.id');

        // 3. Test List Support Tickets via API
        $listRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/staff/support/tickets');

        $listRes->assertStatus(200);
        $this->assertNotEmpty($listRes->json('tickets'));

        // 4. Test Show Support Ticket Thread via API
        $showRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/staff/support/tickets/{$ticketId}");

        $showRes->assertStatus(200);
        $showRes->assertJsonPath('ticket.subject', 'استفسار عن إضافة طابعة فواتير جديدة');

        // 5. Test Reply Support Ticket via API
        $repRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/staff/support/tickets/{$ticketId}/reply", [
                'message' => 'تم تجربة الإعدادات وتعمل بشكل ممتاز، شكراً جزيلاً.',
            ]);

        $repRes->assertStatus(200);
        $this->assertDatabaseHas('support_ticket_messages', [
            'ticket_id' => $ticketId,
            'message' => 'تم تجربة الإعدادات وتعمل بشكل ممتاز، شكراً جزيلاً.',
        ]);
    }

    public function test_unauthorized_user_cannot_access_platform_crm(): void
    {
        $guest = User::create([
            'name' => 'Regular Visitor',
            'email' => 'guest@platform.test',
            'password' => bcrypt('secret123'),
            'role' => 'guest',
            'is_verified' => true,
        ]);

        $res = $this->actingAs($guest)->get('/platform/crm');
        $res->assertStatus(403);
    }
}
