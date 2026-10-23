<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Subscription Plans (خطط وباقات الاشتراك للمنصة)
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
                $table->integer('max_rooms')->default(-1); // -1 = غير محدود
                $table->integer('max_users')->default(-1);
                $table->text('features')->nullable(); // JSON list
                $table->string('badge')->nullable(); // الأكثر طلباً، الأفضل قيمة
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 2. Hotel Subscriptions (اشتراكات الفنادق المشتركة في المنصة)
        if (!Schema::hasTable('hotel_subscriptions')) {
            Schema::create('hotel_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('hotel_id')->index();
                $table->unsignedBigInteger('plan_id')->index();
                $table->string('status', 30)->default('active'); // active, trial, grace_period, expired, cancelled
                $table->string('billing_cycle', 20)->default('monthly'); // monthly, yearly, lifetime
                $table->string('currency', 10)->default('USD'); // USD, SYP
                $table->decimal('price_paid', 14, 2)->default(0);
                $table->date('starts_at');
                $table->date('expires_at');
                $table->date('trial_ends_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 3. Subscription Invoices (فواتير تحصيل الاشتراكات للمنصة)
        if (!Schema::hasTable('subscription_invoices')) {
            Schema::create('subscription_invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('hotel_id')->index();
                $table->unsignedBigInteger('subscription_id')->nullable()->index();
                $table->unsignedBigInteger('plan_id')->nullable()->index();
                $table->string('invoice_number', 50)->unique();
                $table->decimal('amount', 14, 2);
                $table->string('currency', 10)->default('USD');
                $table->string('status', 30)->default('paid'); // paid, unpaid, cancelled
                $table->string('payment_method', 50)->default('bank_transfer'); // cash, bank_transfer, card, transfer
                $table->timestamp('paid_at')->nullable();
                $table->date('due_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 4. Support Tickets (تذاكر الدعم الفني للمنصة والفنادق)
        if (!Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_number', 50)->unique();
                $table->unsignedBigInteger('hotel_id')->index();
                $table->unsignedBigInteger('user_id')->index(); // الموظف المنشئ
                $table->unsignedBigInteger('assigned_to')->nullable()->index(); // موظف فريق المنصة المسؤول
                $table->string('subject');
                $table->string('department', 50)->default('technical'); // technical, billing, sales, feature_request, general
                $table->string('priority', 20)->default('medium'); // low, medium, high, urgent
                $table->string('status', 30)->default('open'); // open, in_progress, waiting_customer, resolved, closed
                $table->timestamp('last_reply_at')->nullable();
                $table->timestamps();
            });
        }

        // 5. Support Ticket Messages (ردود ومراسلات تذكرة الدعم)
        if (!Schema::hasTable('support_ticket_messages')) {
            Schema::create('support_ticket_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->text('message');
                $table->boolean('is_staff_reply')->default(false); // true إذا كان الرد من فريق دعم المنصة
                $table->text('attachments')->nullable();
                $table->timestamps();
            });
        }

        // 6. CRM Leads & Sales Pipeline (إدارة المبيعات والعملاء المحتملين)
        if (!Schema::hasTable('crm_leads')) {
            Schema::create('crm_leads', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('hotel_name');
                $table->string('phone', 50)->nullable();
                $table->string('email')->nullable();
                $table->string('province', 100)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('source', 50)->default('website'); // website, referral, phone, visit, exhibition
                $table->string('status', 50)->default('new'); // new, contacted, demo_scheduled, proposal_sent, won_converted, lost
                $table->decimal('expected_revenue', 14, 2)->default(0);
                $table->string('currency', 10)->default('USD');
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->unsignedBigInteger('hotel_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 7. Marketing Campaigns & Promo Codes (إدارة التسويق والحملات الترويجية)
        if (!Schema::hasTable('marketing_campaigns')) {
            Schema::create('marketing_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('code', 50)->unique();
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->decimal('discount_fixed', 14, 2)->default(0);
                $table->string('currency', 10)->default('USD');
                $table->string('type', 50)->default('discount_coupon'); // discount_coupon, seasonal, referral
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_campaigns');
        Schema::dropIfExists('crm_leads');
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('hotel_subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};

