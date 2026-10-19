<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hotel;

class PublicHotelController extends Controller
{
    public function index(Request $request)
    {
        $visibleHotels = Hotel::query();

        $user = $request->user();
        if ($user && !in_array($user->role, ['super_admin', 'platform_admin', 'guest'], true)) {
            abort_unless($user->hotel_id, 403);
            $visibleHotels->whereKey($user->hotel_id);
        } else {
            $visibleHotels->where('is_visible', true);
        }

        $query = clone $visibleHotels;

        // فلتر البحث بالاسم أو العنوان أو الوصف
        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($sub) use ($keyword) {
                $sub->where('name', 'like', "%{$keyword}%")
                    ->orWhere('address', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // فلتر المحافظة / الوجهة
        if ($request->filled('province') && $request->province !== 'all') {
            $query->where('province', $request->province);
        }

        // فلتر التصنيف بالنجوم
        if ($request->filled('stars') && $request->stars !== 'all') {
            $query->where('stars', $request->stars);
        }

        // فلتر المرافق والخدمات (واي فاي، إفطار، مسبح...)
        if ($request->filled('amenity') && $request->amenity !== 'all') {
            $amenity = trim($request->amenity);
            $query->where('amenities', 'like', "%{$amenity}%");
        }

        // فلتر التواريخ (يوم الوصول والمغادرة) مع التحقق من وجود غرف غير محجوزة بحجز متداخل
        $checkIn = $request->input('check_in');
        $checkOut = $request->input('check_out');
        if ($checkIn && $checkOut && $checkIn < $checkOut) {
            $query->whereHas('rooms', function ($roomQ) use ($checkIn, $checkOut) {
                $roomQ->whereDoesntHave('bookings', function ($bQ) use ($checkIn, $checkOut) {
                    $bQ->whereIn('status', ['confirmed', 'checked_in'])
                        ->where('check_in', '<', $checkOut)
                        ->where('check_out', '>', $checkIn);
                });
            });
        }

        // جلب الفنادق مع متوسط التقييم وعدد المراجعات والغرف
        $query->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->with(['rooms' => function ($q) {
                $q->select('id', 'hotel_id', 'room_type_id', 'price', 'price_usd', 'status');
            }]);

        // ترتيب النتائج (من الأقل للأعلى سعراً، أو الأعلى سعراً، أو الأعلى تقييماً)
        $sortBy = $request->input('sort', 'name');
        if ($sortBy === 'price_asc') {
            $hotels = $query->get()->sortBy(function ($hotel) {
                $rooms = $hotel->rooms;
                return $rooms->count() > 0 ? ((float) $rooms->min('price_usd') ?: ((float) $rooms->min('price') / 15000)) : 99999999;
            })->values();
        } elseif ($sortBy === 'price_desc') {
            $hotels = $query->get()->sortByDesc(function ($hotel) {
                $rooms = $hotel->rooms;
                return $rooms->count() > 0 ? ((float) $rooms->min('price_usd') ?: ((float) $rooms->min('price') / 15000)) : 0;
            })->values();
        } elseif ($sortBy === 'rating_desc') {
            $hotels = $query->orderByDesc('reviews_avg_rating')->latest('id')->get();
        } elseif ($sortBy === 'stars_desc') {
            $hotels = $query->orderByDesc('stars')->latest('id')->get();
        } else {
            $hotels = $query->orderBy('name')->get();
        }

        $provinces = $visibleHotels->whereNotNull('province')->distinct()->orderBy('province')->pluck('province');
        $destinations = \App\Models\Destination::where('is_active', true)->orderBy('sort_order')->latest('id')->get();

        return view('public.hotels', compact('hotels', 'provinces', 'destinations', 'checkIn', 'checkOut'));
    }
    public function show($slug)
{
    $hotel = Hotel::where('slug', $slug)->firstOrFail();

    // الصور مخزنة كـ JSON داخل الحقل images
    $images = $hotel->images ? json_decode($hotel->images, true) : [];

    return view('public.hotel', compact('hotel', 'images'));
}
public function rooms($slug)
{
    $hotel = Hotel::where('slug', $slug)->firstOrFail();

    // جلب الغرف المرتبطة بالفندق
    $rooms = \App\Models\Room::where('hotel_id', $hotel->id)->get();

    return view('public.rooms', compact('hotel', 'rooms'));
}


}
