<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$reviews = [
    ['hotel_id' => 1, 'guest_name' => 'طارق العمري', 'rating' => 5, 'comment' => 'تجربة إقامة استثنائية! الاستقبال راقٍ جداً، والغرف واسعة ونظيفة إلى أبعد حد، والإطلالة ساحرة.'],
    ['hotel_id' => 1, 'guest_name' => 'د. سارة المحمد', 'rating' => 5, 'comment' => 'أفضل فندق جربته في المنطقة. خدمة الغرف سريعة ووجبة الإفطار متنوعة وغنية. أنصح به بشدة.'],
    ['hotel_id' => 1, 'guest_name' => 'باسل الحلبي', 'rating' => 4, 'comment' => 'مكان هادئ ومريح جداً للأعمال والعائلات. الإنترنت سريع والتكييف ممتاز.'],
    ['hotel_id' => 2, 'guest_name' => 'خالد النعيمي', 'rating' => 5, 'comment' => 'فندق ممتاز بموقع استراتيجي قريب من كافة الخدمات، الطاقم متعاون لأبعد الحدود.'],
    ['hotel_id' => 2, 'guest_name' => 'رنا صباغ', 'rating' => 4, 'comment' => 'غرف نظيفة ومريحة وقيمة ممتازة مقابل السعر. سأكرر الزيارة بالتأكيد.'],
    ['hotel_id' => 3, 'guest_name' => 'ماهر القدسي', 'rating' => 5, 'comment' => 'هدوء تام وخدمة فندقية فاخرة ومسبح نظيف. استمتعنا كثيراً بإقامتنا.'],
    ['hotel_id' => 4, 'guest_name' => 'سامر الخطيب', 'rating' => 4, 'comment' => 'إقامة طيبة وموظفو الاستقبال على أعلى مستوى من الاحترافية والبشاشة.']
];

foreach ($reviews as $r) {
    \App\Models\HotelReview::firstOrCreate(
        ['hotel_id' => $r['hotel_id'], 'guest_name' => $r['guest_name']],
        $r
    );
}

echo 'Total reviews now: ' . \App\Models\HotelReview::count() . PHP_EOL;

