<?php

namespace Database\Seeders;

use App\Models\AppBanner;
use App\Models\Destination;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with production/staging starter data.
     */
    public function run(): void
    {
        // 1. Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'ahmad19ali87@gmail.com'],
            [
                'name' => 'Ahmad Ali',
                'password' => Hash::make('Ah70m10a65d55$'),
                'role' => 'super_admin',
                'phone' => '+963912345678',
                'is_active' => true,
            ]
        );

        // 2. Default Hotels
        $hotel1 = Hotel::firstOrCreate(
            ['name' => 'Hilton Hotel'],
            [
                'slug' => 'hilton-hotel',
                'province' => 'دمشق',
                'city' => 'دمشق',
                'address' => 'ساحة الأمويين - دمشق - سوريا',
                'google_maps_url' => 'https://maps.google.com/?q=33.5138,36.2765',
                'phone' => '+963113334455',
                'email' => 'helton@hotel.com',
                'stars' => 5,
                'description' => 'فندق هيلتون الفاخر يقدم أرقى خدمات الضيافة الفندقية في قلب العاصمة مع إطلالات بانورامية خلابة ومطاعم عالمية.',
                'featured_image' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1200&q=80',
                'base_currency' => 'USD',
                'is_visible' => true,
                'status' => 'active',
            ]
        );

        $hotel2 = Hotel::firstOrCreate(
            ['name' => 'فندق الشام بالاس'],
            [
                'slug' => 'cham-palace',
                'province' => 'دمشق',
                'city' => 'دمشق',
                'address' => 'شارع ميسلون - وسط دمشق',
                'google_maps_url' => 'https://maps.google.com/?q=33.5186,36.2913',
                'phone' => '+963112223344',
                'email' => 'cham@hotel.com',
                'stars' => 5,
                'description' => 'أعرق فنادق دمشق في قلب المدينة التجارية، يجمع بين الفخامة الشرقية والخدمات العصرية.',
                'featured_image' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1200&q=80',
                'base_currency' => 'USD',
                'is_visible' => true,
                'status' => 'active',
            ]
        );

        $hotel3 = Hotel::firstOrCreate(
            ['name' => 'فندق شهباء حلب'],
            [
                'slug' => 'shahba-halap',
                'province' => 'حلب',
                'city' => 'حلب',
                'address' => 'شارع حلب الجديدة - حلب',
                'google_maps_url' => 'https://maps.google.com/?q=36.2021,37.1343',
                'phone' => '+963214445566',
                'email' => 'halap@hotel.com',
                'stars' => 5,
                'description' => 'فندق راقٍ في الشهباء يتميز بأجنحته الفسيحة وموقعه القريب من أهم معالم مدينة حلب.',
                'featured_image' => 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1200&q=80',
                'base_currency' => 'USD',
                'is_visible' => true,
                'status' => 'active',
            ]
        );

        $hotel4 = Hotel::firstOrCreate(
            ['name' => 'منتجع الساحل الأزرق'],
            [
                'slug' => 'blue-coast-resort',
                'province' => 'اللاذقية',
                'city' => 'اللاذقية',
                'address' => 'الشاطئ الأزرق - اللاذقية',
                'google_maps_url' => 'https://maps.google.com/?q=35.5317,35.7901',
                'phone' => '+963417778899',
                'email' => 'latakia@hotel.com',
                'stars' => 5,
                'description' => 'منتجع بحري متكامل على رمال البحر المتوسط الساحرة مع مسابح وألعاب مائية وخصومات حصرية.',
                'featured_image' => 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=1200&q=80',
                'base_currency' => 'USD',
                'is_visible' => true,
                'status' => 'active',
            ]
        );

        // 3. Hotel Admins
        User::firstOrCreate(
            ['email' => 'helton@hotel.com'],
            [
                'name' => 'Hilton Admin',
                'password' => Hash::make('Ah70m10a65d55$'),
                'role' => 'hotel_admin',
                'hotel_id' => $hotel1->id,
                'phone' => '+963113334455',
                'is_active' => true,
            ]
        );

        // Sample Test Guest
        User::firstOrCreate(
            ['email' => 'guest.user@gmail.com'],
            [
                'name' => 'نزيل تجريبي (Demo Guest)',
                'password' => Hash::make('Ah70m10a65d55$'),
                'role' => 'guest',
                'phone' => '+963988776655',
                'is_active' => true,
            ]
        );

        // 4. Room Types
        $types = [
            ['name' => 'غرفة مفردة قياسية (Standard Single)', 'code' => 'SGL', 'capacity' => 1, 'base_price' => 45],
            ['name' => 'غرفة مزدوجة ديلوكس (Deluxe Double)', 'code' => 'DBL', 'capacity' => 2, 'base_price' => 75],
            ['name' => 'جناح ملكي تنفيذي (Royal Suite)', 'code' => 'STE', 'capacity' => 3, 'base_price' => 140],
            ['name' => 'جناح عائلي متصل (Family Suite)', 'code' => 'FAM', 'capacity' => 4, 'base_price' => 180],
        ];

        foreach ([$hotel1, $hotel2, $hotel3, $hotel4] as $h) {
            foreach ($types as $t) {
                $rt = RoomType::firstOrCreate(
                    ['hotel_id' => $h->id, 'name' => $t['name']],
                    [
                        'code' => $t['code'],
                        'capacity' => $t['capacity'],
                        'base_price' => $t['base_price'],
                        'description' => 'غرفة مجهزة بأحدث وسائل الراحة وتكييف وشاشة ذكية وإنترنت مجاني.',
                    ]
                );

                // Create 2-3 rooms per type
                for ($i = 1; $i <= 2; $i++) {
                    $roomNum = ($rt->capacity * 100) + $i;
                    Room::firstOrCreate(
                        ['hotel_id' => $h->id, 'room_number' => (string) $roomNum],
                        [
                            'room_type_id' => $rt->id,
                            'floor' => $rt->capacity,
                            'price' => $t['base_price'],
                            'price_usd' => $t['base_price'],
                            'price_syp' => $t['base_price'] * 15000,
                            'status' => 'available',
                            'is_clean' => true,
                            'view_type' => 'إطلالة على المدينة والحديقة',
                            'image' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=800&q=80',
                        ]
                    );
                }
            }
        }

        // 5. Destinations
        $destinations = [
            [
                'title' => 'دمشق',
                'subtitle' => 'عراقة الشرق وياسمين الشام والأسواق التاريخية العريقة',
                'image' => 'https://images.unsplash.com/photo-1578895101408-1a36b834405b?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 1,
            ],
            [
                'title' => 'اللاذقية',
                'subtitle' => 'شواطئ البحر الأبيض المتوسط والمنتجعات الساحلية الفاخرة',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 2,
            ],
            [
                'title' => 'حلب',
                'subtitle' => 'الشهباء وحصون التاريخ وقلعة حلب الشامخة وأسواقها التراثية',
                'image' => 'https://images.unsplash.com/photo-1565008447742-97f6f38c985c?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 3,
            ],
            [
                'title' => 'طرطوس',
                'subtitle' => 'جزيرة أرواد الساحرة والكورنيش البحري والمنتجعات الجبلية',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 4,
            ],
        ];

        foreach ($destinations as $d) {
            Destination::firstOrCreate(
                ['title' => $d['title']],
                [
                    'subtitle' => $d['subtitle'],
                    'image' => $d['image'],
                    'sort_order' => $d['sort_order'],
                    'is_active' => true,
                    'link' => '/hotels?province=' . urlencode($d['title']),
                ]
            );
        }

        // 6. App Banners (أشهر الواجهات والإعلانات)
        $banners = [
            [
                'title' => 'فندق هيلتون الفاخر',
                'subtitle' => 'أجمل إطلالة بانورامية في قلب العاصمة مع خدمات فندقية راقية ووجبة إفطار مجانية',
                'badge' => 'أشهر الفنادق',
                'image' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1200&q=80',
                'link_type' => 'hotel',
                'link_value' => 'Hilton Hotel',
                'bg_gradient' => 'linear-gradient(135deg, #0284c7, #0369a1)',
                'sort_order' => 1,
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
            ],
            [
                'title' => 'خصومات حصرية تصل إلى 30% 🏷️',
                'subtitle' => 'احجز إقامتك الآن عبر تطبيق الجوال واستفد من عروض نهاية الأسبوع وخدمات النقل المجانية',
                'badge' => 'عرض حصري',
                'image' => 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1200&q=80',
                'link_type' => 'none',
                'link_value' => null,
                'bg_gradient' => 'linear-gradient(135deg, #b45309, #d97706)',
                'sort_order' => 3,
            ],
        ];

        foreach ($banners as $b) {
            AppBanner::firstOrCreate(
                ['title' => $b['title']],
                [
                    'subtitle' => $b['subtitle'],
                    'badge' => $b['badge'],
                    'image' => $b['image'],
                    'link_type' => $b['link_type'],
                    'link_value' => $b['link_value'],
                    'bg_gradient' => $b['bg_gradient'],
                    'sort_order' => $b['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        // 7. Site Settings
        SiteSetting::firstOrCreate(
            ['key' => 'site_name'],
            [
                'value' => 'بوابة حجز الفنادق السورية',
                'group' => 'general',
            ]
        );
        SiteSetting::firstOrCreate(
            ['key' => 'default_currency'],
            [
                'value' => 'USD',
                'group' => 'general',
            ]
        );
        SiteSetting::firstOrCreate(
            ['key' => 'exchange_rate_syp'],
            [
                'value' => '15000',
                'group' => 'general',
            ]
        );
    }
}
