<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Hotel;
use App\Models\HotelReview;
use App\Models\Offer;
use App\Models\Room;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    /**
     * List hotels with Booking.com-style filters.
     */
    public function index(Request $request)
    {
        $query = Hotel::query()->where('is_visible', true);

        // Search term (name, province, city)
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('province', 'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%");
            });
        }

        // City / Province filter
        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }

        // Amenity filter
        if ($request->filled('amenity')) {
            $amenity = $request->amenity;
            $query->where(function ($q) use ($amenity) {
                $q->where('amenities', 'like', "%{$amenity}%")
                  ->orWhere('features', 'like', "%{$amenity}%");
            });
        }

        // Price range
        if ($request->filled('min_price')) {
            $query->where('min_price_usd', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('min_price_usd', '<=', (float) $request->max_price);
        }

        // Dates availability check (Booking.com style)
        if ($request->filled('check_in') && $request->filled('check_out')) {
            $checkIn = $request->check_in;
            $checkOut = $request->check_out;

            $query->whereHas('rooms', function ($roomQuery) use ($checkIn, $checkOut) {
                $roomQuery->whereNotIn('status', ['maintenance', 'cleaning', 'reserved'])
                    ->whereDoesntHave('bookings', function ($bQuery) use ($checkIn, $checkOut) {
                        $bQuery->where('status', '!=', 'cancelled')
                               ->where('check_in', '<', $checkOut)
                               ->where('check_out', '>', $checkIn);
                    });
            });
        }

        // Sorting
        $sort = $request->input('sort', 'default');
        match ($sort) {
            'price_asc' => $query->orderBy('min_price_usd', 'asc'),
            'price_desc' => $query->orderBy('min_price_usd', 'desc'),
            'stars_desc' => $query->orderBy('stars', 'desc'),
            'rating_desc' => $query->orderBy('rating', 'desc'),
            default => $query->latest(),
        };

        $hotels = $query->withCount([
            'rooms' => function ($q) use ($request) {
                if ($request->filled('check_in') && $request->filled('check_out')) {
                    $cIn = $request->check_in;
                    $cOut = $request->check_out;
                    $q->whereNotIn('status', ['maintenance', 'cleaning', 'reserved'])
                      ->whereDoesntHave('bookings', function ($bQ) use ($cIn, $cOut) {
                          $bQ->where('status', '!=', 'cancelled')
                             ->where('check_in', '<', $cOut)
                             ->where('check_out', '>', $cIn);
                      });
                } else {
                    $q->where('status', 'available');
                }
            },
            'reviews',
        ])
            ->withAvg('reviews', 'rating')
            ->with(['reviews' => fn ($q) => $q->latest()->take(3)])
            ->paginate($request->input('per_page', 12));

        $formatted = $hotels->through(function ($hotel) {
            $avgRating = $hotel->reviews_avg_rating ? round((float) $hotel->reviews_avg_rating, 1) : (float) ($hotel->rating ?? 4.8);
            $reviewsCount = (int) ($hotel->reviews_count > 0 ? $hotel->reviews_count : 14);

            $recentReviews = $hotel->reviews->map(function ($r) {
                return [
                    'id' => $r->id,
                    'guest_name' => $r->guest_name ?: 'نزيل تم التحقق منه',
                    'rating' => (int) $r->rating,
                    'comment' => $r->comment,
                    'time_ago' => $r->created_at ? $r->created_at->diffForHumans() : 'مؤخراً',
                ];
            });

            return [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'slug' => $hotel->slug,
                'province' => $hotel->province,
                'city' => $hotel->city ?: $hotel->province,
                'stars' => $hotel->stars,
                'rating' => $avgRating,
                'reviews_count' => $reviewsCount,
                'recent_reviews' => $recentReviews,
                'logo_url' => $hotel->logo_url,
                'cover_image' => $hotel->cover_image_url ?: $hotel->logo_url,
                'google_maps_url' => $hotel->google_maps_url,
                'address' => $hotel->address,
                'phone' => $hotel->phone,
                'tagline' => $hotel->tagline ?: 'إقامة مريحة بأفضل الأسعار',
                'min_price_syp' => $hotel->min_price_syp,
                'min_price_usd' => $hotel->min_price_usd,
                'amenities' => $hotel->amenities_list ?? ['واي فاي مجاني', 'مكيف', 'خدمة غرف'],
                'available_rooms' => $hotel->rooms_count,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $formatted,
        ]);
    }

    /**
     * Get single hotel details with room availability filtered by dates.
     */
    public function show(Request $request, $id)
    {
        $checkIn = $request->input('check_in');
        $checkOut = $request->input('check_out');

        $hotel = Hotel::where('is_visible', true)
            ->with([
                'rooms' => function ($roomQuery) use ($checkIn, $checkOut) {
                    if ($checkIn && $checkOut) {
                        $roomQuery->whereNotIn('status', ['maintenance', 'cleaning', 'reserved'])
                            ->whereDoesntHave('bookings', function ($bQuery) use ($checkIn, $checkOut) {
                                $bQuery->where('status', '!=', 'cancelled')
                                       ->where('check_in', '<', $checkOut)
                                       ->where('check_out', '>', $checkIn);
                            });
                    } else {
                        $roomQuery->where('status', 'available');
                    }
                    $roomQuery->with('roomType')->orderBy('room_number');
                },
                'offers' => fn ($q) => $q->active(),
                'reviews' => fn ($q) => $q->latest()->take(30),
            ])
            ->findOrFail($id);

        $availableRooms = $hotel->rooms->map(function ($room) {
            return [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'room_type' => $room->roomType?->name ?? 'غرفة قياسية',
                'price_syp' => (float) ($room->price_syp ?: 0),
                'price_usd' => (float) ($room->price_per_night ?: 0),
                'status' => $room->status,
                'is_available' => true,
                'image_url' => $room->image_url,
                'features' => $room->features_list ?? ['تكييف', 'حمام خاص', 'شاشة تلفزيون'],
            ];
        });

        $offers = $hotel->offers->map(function ($offer) {
            return [
                'id' => $offer->id,
                'title' => $offer->title,
                'description' => $offer->description,
                'discount_percentage' => (int) $offer->discount_percentage,
                'special_price' => $offer->special_price,
                'special_price_usd' => $offer->special_price_usd,
                'image_url' => $offer->image_url,
                'end_date' => $offer->end_date?->format('Y-m-d'),
            ];
        });

        $reviews = $hotel->reviews->map(function ($r) {
            return [
                'id' => $r->id,
                'guest_name' => $r->guest_name ?: 'نزيل تم التحقق منه',
                'rating' => (int) $r->rating,
                'comment' => $r->comment,
                'hotel_reply' => $r->hotel_reply,
                'replied_at' => $r->replied_at ? $r->replied_at->format('Y-m-d H:i') : null,
                'date' => $r->created_at ? $r->created_at->format('Y-m-d') : '',
                'time_ago' => $r->created_at ? $r->created_at->diffForHumans() : 'مؤخراً',
            ];
        });

        $avgRating = $hotel->reviews()->avg('rating') ? round((float) $hotel->reviews()->avg('rating'), 1) : (float) ($hotel->rating ?? 4.8);
        $reviewsCount = (int) max($hotel->reviews->count(), 14);

        return response()->json([
            'status' => 'success',
            'hotel' => [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'slug' => $hotel->slug,
                'province' => $hotel->province,
                'city' => $hotel->city ?: $hotel->province,
                'address' => $hotel->address,
                'google_maps_url' => $hotel->google_maps_url,
                'phone' => $hotel->phone,
                'email' => $hotel->email,
                'stars' => $hotel->stars,
                'rating' => $avgRating,
                'reviews_count' => $reviewsCount,
                'reviews' => $reviews,
                'tagline' => $hotel->tagline,
                'description' => $hotel->description,
                'logo_url' => $hotel->logo_url,
                'cover_image' => $hotel->cover_image_url ?: $hotel->logo_url,
                'gallery' => $hotel->gallery_urls ?? [],
                'amenities' => $hotel->amenities_list ?? ['واي فاي مجاني', 'مكيف', 'خدمة غرف', 'موقف سيارات'],
                'min_price_syp' => $hotel->min_price_syp,
                'min_price_usd' => $hotel->min_price_usd,
                'rooms' => $availableRooms,
                'offers' => $offers,
            ],
        ]);
    }

    /**
     * Get list of reviews for a hotel.
     */
    public function reviews($id)
    {
        $hotel = Hotel::findOrFail($id);
        $paginator = $hotel->reviews()->latest()->paginate(20);

        $formatted = $paginator->through(function ($r) {
            return [
                'id' => $r->id,
                'guest_name' => $r->guest_name ?: 'نزيل تم التحقق منه',
                'rating' => (int) $r->rating,
                'comment' => $r->comment,
                'hotel_reply' => $r->hotel_reply,
                'replied_at' => $r->replied_at ? $r->replied_at->format('Y-m-d H:i') : null,
                'date' => $r->created_at ? $r->created_at->format('Y-m-d') : '',
                'time_ago' => $r->created_at ? $r->created_at->diffForHumans() : 'مؤخراً',
            ];
        });

        return response()->json([
            'status' => 'success',
            'average_rating' => (float) ($hotel->reviews()->avg('rating') ? round((float) $hotel->reviews()->avg('rating'), 1) : 4.8),
            'reviews_count' => $hotel->reviews()->count(),
            'data' => $formatted,
        ]);
    }

    /**
     * Reply to a review (Hotel staff or administration).
     */
    public function replyToReview(Request $request, $id, $reviewId)
    {
        $hotel = Hotel::findOrFail($id);
        $review = $hotel->reviews()->findOrFail($reviewId);

        $validated = $request->validate([
            'hotel_reply' => 'required|string|max:2000',
        ]);

        $review->update([
            'hotel_reply' => $validated['hotel_reply'],
            'replied_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم حفظ ونشر رد إدارة الفندق بنجاح.',
            'data' => [
                'id' => $review->id,
                'hotel_reply' => $review->hotel_reply,
                'replied_at' => $review->replied_at ? $review->replied_at->format('Y-m-d H:i') : null,
            ],
        ]);
    }

    /**
     * Get primary contact user for hotel messaging.
     */
    public function contactUser($id)
    {
        $hotel = Hotel::findOrFail($id);
        $staff = \App\Models\User::where('hotel_id', $hotel->id)
            ->whereIn('role', ['hotel_admin', 'admin', 'reception'])
            ->first() ?? \App\Models\User::where('role', 'super_admin')->first();

        return response()->json([
            'status' => 'success',
            'hotel_id' => $hotel->id,
            'hotel_name' => $hotel->name,
            'user' => [
                'id' => $staff?->id,
                'name' => $staff?->name ?? 'إدارة ' . $hotel->name,
                'role' => $staff?->role ?? 'hotel_admin',
                'role_label' => 'إدارة ' . $hotel->name,
                'avatar_url' => $staff?->avatar_url,
            ],
        ]);
    }

    /**
     * Store new review for a hotel from guest.
     */
    public function storeReview(Request $request, $id)
    {
        $hotel = Hotel::findOrFail($id);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'guest_name' => 'nullable|string|max:120',
        ]);

        $user = $request->user();
        $guestName = $validated['guest_name'] ?? ($user ? $user->name : 'نزيل موثوق');

        $review = $hotel->reviews()->create([
            'guest_name' => $guestName,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? '',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'شكراً لك! تم إضافة تقييمك بنجاح.',
            'data' => $review,
        ]);
    }

    /**
     * Get destinations with photos for mobile home screen.
     */
    public function destinations()
    {
        $destinations = Destination::where('is_active', true)->orderBy('sort_order')->get();

        $defaultPhotos = [
            'دمشق' => 'https://images.unsplash.com/photo-1578895101408-1a36b834405b?auto=format&fit=crop&w=800&q=80',
            'اللاذقية' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
            'طرطوس' => 'https://images.unsplash.com/photo-1544644181-1484b3fdfc62?auto=format&fit=crop&w=800&q=80',
            'حلب' => 'https://images.unsplash.com/photo-1565008447742-97f6f38c985c?auto=format&fit=crop&w=800&q=80',
            'حمص' => 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?auto=format&fit=crop&w=800&q=80',
            'الساحل السوري' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
        ];

        if ($destinations->isEmpty()) {
            $defaultDestinations = [
                ['id' => 1, 'name' => 'دمشق', 'title' => 'دمشق', 'subtitle' => 'عاصمة الياسمين وعراقة الشرق', 'image' => $defaultPhotos['دمشق'], 'count' => Hotel::where('province', 'دمشق')->count()],
                ['id' => 2, 'name' => 'اللاذقية', 'title' => 'اللاذقية', 'subtitle' => 'عروس الساحل والشواطئ الذهبية', 'image' => $defaultPhotos['اللاذقية'], 'count' => Hotel::where('province', 'اللاذقية')->count()],
                ['id' => 3, 'name' => 'طرطوس', 'title' => 'طرطوس', 'subtitle' => 'الجزيرة والهدوء والإطلالات البحرية', 'image' => $defaultPhotos['طرطوس'], 'count' => Hotel::where('province', 'طرطوس')->count()],
                ['id' => 4, 'name' => 'حلب', 'title' => 'حلب', 'subtitle' => 'الشهباء وحصون التاريخ الأصيل', 'image' => $defaultPhotos['حلب'], 'count' => Hotel::where('province', 'حلب')->count()],
                ['id' => 5, 'name' => 'حمص', 'title' => 'حمص', 'subtitle' => 'أم الحجارة السود وقلعة الحصن', 'image' => $defaultPhotos['حمص'], 'count' => Hotel::where('province', 'حمص')->count()],
            ];
            return response()->json(['status' => 'success', 'data' => $defaultDestinations]);
        }

        $data = $destinations->map(function ($d) use ($defaultPhotos) {
            $rawTitle = $d->title ?? $d->name ?? 'مدينة';
            // Extract clean province name if title is "مدينة دمشق" -> "دمشق"
            $cleanName = trim(str_replace(['مدينة', 'محافظة', 'منطقة'], '', $rawTitle));
            if (empty($cleanName)) $cleanName = $rawTitle;

            $image = $d->image_url;
            if (empty($image)) {
                $image = $defaultPhotos[$cleanName] ?? $defaultPhotos[$rawTitle] ?? 'https://images.unsplash.com/photo-1578895101408-1a36b834405b?auto=format&fit=crop&w=800&q=80';
            }

            return [
                'id' => $d->id,
                'name' => $cleanName,
                'title' => $rawTitle,
                'subtitle' => $d->subtitle ?? '',
                'image' => $image,
                'count' => Hotel::where('province', 'LIKE', '%' . $cleanName . '%')->count(),
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    /**
     * Active promotional banners & ads for mobile app ("أشهر الواجهات والإعلانات").
     */
    public function appBanners()
    {
        $banners = \App\Models\AppBanner::active()->get();

        $data = $banners->map(function ($b) {
            return [
                'id' => $b->id,
                'title' => $b->title,
                'subtitle' => $b->subtitle,
                'badge' => $b->badge ?: 'أشهر الواجهات',
                'image' => $b->image_url,
                'link_type' => $b->link_type,
                'link_value' => $b->link_value,
                'bg_gradient' => $b->bg_gradient,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    /**
     * Active promotional offers.
     */
    public function offers()
    {
        $offers = Offer::with('hotel')->active()->latest()->take(20)->get();

        $data = $offers->map(function ($offer) {
            return [
                'id' => $offer->id,
                'hotel_id' => $offer->hotel_id,
                'hotel_name' => $offer->hotel?->name,
                'hotel_province' => $offer->hotel?->province,
                'title' => $offer->title,
                'description' => $offer->description,
                'discount_percentage' => (int) $offer->discount_percentage,
                'special_price' => $offer->special_price,
                'special_price_usd' => $offer->special_price_usd,
                'image_url' => $offer->image_url,
                'end_date' => $offer->end_date?->format('Y-m-d'),
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }
}

