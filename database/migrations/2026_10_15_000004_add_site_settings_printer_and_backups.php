<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. جدول إعدادات ومظهر الموقع
        if (!Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->text('value')->nullable();
                $table->string('group', 50)->default('general');
                $table->timestamps();
            });

            // إدراج النصوص والإعدادات الافتراضية
            $defaultSettings = [
                ['key' => 'site_name', 'value' => 'نظام إدارة وحجز الفنادق', 'group' => 'general'],
                ['key' => 'site_icon', 'value' => '🏨', 'group' => 'general'],
                ['key' => 'site_favicon', 'value' => null, 'group' => 'general'],
                ['key' => 'site_logo', 'value' => null, 'group' => 'general'],
                ['key' => 'hero_cover_image', 'value' => null, 'group' => 'hero'],
                ['key' => 'hero_badge', 'value' => '✨ بوابتك لحجز أرقى الفنادق والمنتجعات السياحية', 'group' => 'hero'],
                ['key' => 'hero_title', 'value' => 'إقامتك المثالية تبدأ هنا', 'group' => 'hero'],
                ['key' => 'hero_subtitle', 'value' => 'بأعلى معايير الضيافة العالمية', 'group' => 'hero'],
                ['key' => 'hero_description', 'value' => 'اختر من بين أفخم الفنادق والوجهات السياحية، استعرض الغرف المتوفرة لحظياً، واستمتع بخيارات تسعير منفصلة بالليرة السورية والدولار الأمريكي مع تأكيد فوري للحجز.', 'group' => 'hero'],
                ['key' => 'meta_title', 'value' => 'حجز وإدارة الفنادق في سوريا', 'group' => 'seo'],
                ['key' => 'meta_description', 'value' => 'منصة متكاملة لحجز أرقى الفنادق والمنتجعات السياحية في المحافظات السورية', 'group' => 'seo'],
                ['key' => 'meta_keywords', 'value' => 'حجز فنادق, فنادق دمشق, فنادق حلب, فنادق اللاذقية, سياحة سوريا', 'group' => 'seo'],
                ['key' => 'footer_text', 'value' => '© 2026 جميع الحقوق محفوظة | منصة إدارة وحجز الفنادق', 'group' => 'general'],
            ];

            foreach ($defaultSettings as $setting) {
                DB::table('site_settings')->insert([
                    ...$setting,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2. إضافة حقل الصلاحيات المخصصة لمدراء المنصة
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'permissions')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('permissions')->nullable();
            });
        }

        // 3. إضافة إعدادات الطابعات والسكنر للفندق
        if (Schema::hasTable('hotels') && !Schema::hasColumn('hotels', 'printer_settings')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->text('printer_settings')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'permissions')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('permissions');
            });
        }

        if (Schema::hasTable('hotels') && Schema::hasColumn('hotels', 'printer_settings')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->dropColumn('printer_settings');
            });
        }
    }
};

