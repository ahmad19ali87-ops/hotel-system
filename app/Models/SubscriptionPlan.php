<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly_syp',
        'price_yearly_syp',
        'price_monthly_usd',
        'price_yearly_usd',
        'max_rooms',
        'max_users',
        'features',
        'badge',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price_monthly_syp' => 'float',
        'price_yearly_syp' => 'float',
        'price_monthly_usd' => 'float',
        'price_yearly_usd' => 'float',
        'max_rooms' => 'integer',
        'max_users' => 'integer',
        'sort_order' => 'integer',
        'features' => 'array',
    ];

    public function getFeaturesListAttribute(): array
    {
        if (empty($this->features)) {
            return [
                'إدارة الغرف والحجوزات الفورية',
                'سجل النزلاء وأرشفة الوثائق',
                'الفواتير والمحاسبة بالعملتين (ل.س و $)',
                'تطبيق سطح المكتب المتكامل والطباعة الحرارية',
                'الدعم الفني السريع وتحديثات النظام',
            ];
        }

        if (is_array($this->features)) {
            return $this->features;
        }

        $decoded = json_decode($this->features, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return array_filter(array_map('trim', explode("\n", str_replace("\r", "", $this->features))));
    }

    public function subscriptions()
    {
        return $this->hasMany(HotelSubscription::class, 'plan_id');
    }

    public function invoices()
    {
        return $this->hasMany(SubscriptionInvoice::class, 'plan_id');
    }

    /**
     * Pre-populate default plans if none exist.
     */
    public static function seedDefaultsIfNeeded(): void
    {
        if (self::count() > 0) {
            return;
        }

        self::create([
            'name' => 'باقة البداية (Starter)',
            'slug' => 'starter',
            'description' => 'مثالية للفنادق الصغيرة والنزل والموتيلات التي تبدأ رحلتها الرقمية.',
            'price_monthly_syp' => 450000,
            'price_yearly_syp' => 4500000,
            'price_monthly_usd' => 30,
            'price_yearly_usd' => 300,
            'max_rooms' => 15,
            'max_users' => 3,
            'features' => json_encode([
                'حتى 15 غرفة فندقية',
                '3 حسابات موظفين (مدير، استقبال، محاسب)',
                'نظام الحجوزات وتسجيل الدخول والخروج',
                'فواتير ومحاسبة أساسية وإيصالات حرارية',
                'تطبيق الكومبيوتر المكتبي',
                'دعم فني عبر التذاكر',
            ], JSON_UNESCAPED_UNICODE),
            'badge' => 'اقتصادية',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        self::create([
            'name' => 'الباقة الاحترافية (Professional)',
            'slug' => 'pro',
            'description' => 'الخيار الأفضل للفنادق المتوسطة والراقية التي تبحث عن أتمتة شاملة وزيادة الأرباح.',
            'price_monthly_syp' => 950000,
            'price_yearly_syp' => 9500000,
            'price_monthly_usd' => 65,
            'price_yearly_usd' => 650,
            'max_rooms' => 50,
            'max_users' => 10,
            'features' => json_encode([
                'حتى 50 غرفة فندقية',
                '10 حسابات موظفين مع صلاحيات مخصصة',
                'نظام الحجوزات المتقدم ونظام سكنر الوثائق الذكي',
                'إدارة المحاسبة المزدوجة بالدولار والليرة وفصل الصناديق',
                'لوحة مؤشرات وأداء الفندق وتقارير مالية تفصيلية',
                'تطبيق سطح المكتب + الربط مع تطبيق الجوال',
                'دعم فني ذو أولوية عالية (VIP)',
            ], JSON_UNESCAPED_UNICODE),
            'badge' => 'الأكثر طلباً ⭐',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        self::create([
            'name' => 'باقة المؤسسات والمنتجعات (Enterprise VIP)',
            'slug' => 'enterprise',
            'description' => 'حل شامل وغير محدود للمنتجعات الكبرى وسلاسل الفنادق الفاخرة.',
            'price_monthly_syp' => 1800000,
            'price_yearly_syp' => 18000000,
            'price_monthly_usd' => 120,
            'price_yearly_usd' => 1200,
            'max_rooms' => -1, // غير محدود
            'max_users' => -1,
            'features' => json_encode([
                'غرف وحجوزات غير محدودة بالكامل',
                'حسابات موظفين غير محدودة',
                'كافة مزايا النظام الاحترافي ومحطة العمل الكاملة',
                'أولوية الظهور في واجهات البحث وتطبيق الجوال للمسافرين',
                'نسخ احتياطي تلقائي مشفر واستعادة فورية للبيانات',
                'إحصائيات متقدمة مع تنبيهات لحظية ومحرك الاستجابة الأمني',
                'مدير حساب مخصص ودعم فني على مدار الساعة 24/7',
            ], JSON_UNESCAPED_UNICODE),
            'badge' => 'الأقوى للمنتجعات 👑',
            'is_active' => true,
            'sort_order' => 3,
        ]);
    }
}

