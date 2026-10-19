<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('app_banners')) {
            Schema::create('app_banners', function (Blueprint $table) {
                $table->id();
                $table->string('title', 191); // كلمات إعلانية رئيسية
                $table->text('subtitle')->nullable(); // كلمات إعلانية فرعية وتوضيحية
                $table->string('badge', 60)->nullable()->default('أشهر الواجهات'); // شارة مثل أشهر الواجهات / عرض خاص
                $table->string('image', 255)->nullable(); // صورة الإعلان أو البانر
                $table->string('link_type', 50)->default('destination'); // destination, hotel, url, none
                $table->string('link_value', 255)->nullable(); // اسم المحافظة أو معرف الفندق أو الرابط
                $table->string('bg_gradient', 100)->nullable(); // لون أو تدرج الخلفية
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed default 3 promotional ad banners
            DB::table('app_banners')->insert([
                [
                    'title' => 'أشهر واجهات الساحل السوري 🏖️',
                    'subtitle' => 'عطلة صيفية استثنائية في أرقى منتجعات وفنادق اللاذقية وطرطوس بإطلالات بحرية ساحرة',
                    'badge' => 'أشهر الواجهات',
                    'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1200&q=80',
                    'link_type' => 'destination',
                    'link_value' => 'اللاذقية',
                    'bg_gradient' => 'linear-gradient(135deg, #0284c7, #0369a1)',
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'عراقة الشام والياسمين الدمشقي 🏛️',
                    'subtitle' => 'اكتشف فنادق دمشق القديمة وأفخم أجنحة الضيافة في قلب العاصمة التاريخية',
                    'badge' => 'إعلان مميز',
                    'image' => 'https://images.unsplash.com/photo-1578895101408-1a36b834405b?auto=format&fit=crop&w=1200&q=80',
                    'link_type' => 'destination',
                    'link_value' => 'دمشق',
                    'bg_gradient' => 'linear-gradient(135deg, #003580, #1e3a8a)',
                    'sort_order' => 2,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'خصومات حصرية تصل إلى 30% 🏷️',
                    'subtitle' => 'احجز غرفتك الآن عبر التطبيق واستفد من عروض نهاية الأسبوع ووجبات الإفطار المجانية',
                    'badge' => 'عرض حصري',
                    'image' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1200&q=80',
                    'link_type' => 'none',
                    'link_value' => null,
                    'bg_gradient' => 'linear-gradient(135deg, #b45309, #d97706)',
                    'sort_order' => 3,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_banners');
    }
};

