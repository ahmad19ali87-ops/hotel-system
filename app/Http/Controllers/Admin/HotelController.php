<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\User;
use App\Support\StorageFolderManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HotelController extends Controller
{
    private const PROVINCES = [
        'دمشق', 'ريف دمشق', 'حلب', 'حمص', 'حماة', 'اللاذقية', 'طرطوس',
        'إدلب', 'درعا', 'السويداء', 'القنيطرة', 'دير الزور', 'الرقة', 'الحسكة',
    ];

    public function index()
    {
        $hotels = Hotel::withCount(['rooms', 'bookings'])->orderBy('name')->get();

        return view('admin.hotels.index', compact('hotels'));
    }

    public function create()
    {
        abort_unless(auth()->user()?->hasPermission('hotels_create'), 403, 'غير مصرح لك بإضافة فنادق جديدة.');
        return view('admin.hotels.create', ['provinces' => self::PROVINCES]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->hasPermission('hotels_create'), 403, 'غير مصرح لك بإضافة فنادق جديدة.');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('hotels', 'slug')],
            'tagline' => ['nullable', 'string', 'max:255'],
            'amenities' => ['nullable'],
            'custom_amenity' => ['nullable', 'string', 'max:255'],
            'province' => ['required', Rule::in(self::PROVINCES)],
            'stars' => ['nullable', 'integer', 'between:1,5'],
            'phone' => ['nullable', 'string', 'max:40'],
            'google_maps_url' => ['nullable', 'string', 'max:1000'],
            'phones' => ['nullable', 'array'],
            'phones.*' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable'],
            'manager_name' => ['required', 'string', 'max:255'],
            'manager_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'manager_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $slugBase = Str::slug($validated['slug'] ?: $validated['name']) ?: 'hotel';
        $slug = $slugBase;
        $suffix = 2;
        while (Hotel::forSlug($slug)->exists()) {
            $slug = $slugBase . '-' . $suffix++;
        }

        $amenitiesList = [];
        if ($request->has('amenities') && is_array($request->amenities)) {
            $amenitiesList = array_merge($amenitiesList, $request->amenities);
        }
        if ($request->filled('custom_amenity')) {
            $customs = array_filter(array_map('trim', explode(',', $request->custom_amenity)));
            $amenitiesList = array_merge($amenitiesList, $customs);
        }
        $encodedAmenities = !empty($amenitiesList) ? json_encode(array_values(array_unique($amenitiesList)), JSON_UNESCAPED_UNICODE) : null;

        // Ensure hotel directories and upload folders exist in public storage
        StorageFolderManager::ensureHotelDirectories($slug);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('hotels', 'public');
        } elseif (is_string($request->logo)) {
            $logoPath = $request->logo;
        }

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                if ($img instanceof \Illuminate\Http\UploadedFile) {
                    $imagePaths[] = $img->store('hotels', 'public');
                }
            }
        }
        $encodedImages = !empty($imagePaths) ? json_encode($imagePaths, JSON_UNESCAPED_UNICODE) : null;

        $phonesList = [];
        if ($request->has('phones') && is_array($request->phones)) {
            $phonesList = array_values(array_filter(array_map('trim', $request->phones)));
        } elseif ($request->filled('phone')) {
            $phonesList = array_values(array_filter(array_map('trim', explode(',', $request->phone))));
        }
        $encodedPhones = !empty($phonesList) ? json_encode($phonesList, JSON_UNESCAPED_UNICODE) : null;
        $primaryPhone = !empty($phonesList) ? $phonesList[0] : ($validated['phone'] ?? null);

        DB::transaction(function () use ($validated, $slug, $encodedAmenities, $logoPath, $encodedImages, $encodedPhones, $primaryPhone, $request) {
            $hotel = Hotel::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'tagline' => $validated['tagline'] ?? null,
                'amenities' => $encodedAmenities,
                'province' => $validated['province'],
                'stars' => $validated['stars'] ?? 3,
                'phone' => $primaryPhone,
                'phones' => $encodedPhones,
                'email' => $request->input('email') ?? null,
                'address' => $validated['address'] ?? null,
                'google_maps_url' => $validated['google_maps_url'] ?? null,
                'description' => $validated['description'] ?? null,
                'logo' => $logoPath,
                'images' => $encodedImages,
            ]);

            User::create([
                'name' => $validated['manager_name'],
                'email' => $validated['manager_email'],
                'password' => $validated['manager_password'],
                'role' => 'admin',
                'hotel_id' => $hotel->id,
            ]);
        });

        return redirect()->route('platform.hotels.index')->with('status', 'تم إنشاء الفندق وحساب مديره.');
    }
    public function edit(Hotel $hotel)
    {
        abort_unless(auth()->user()?->hasPermission('hotels_edit'), 403, 'غير مصرح لك بتعديل بيانات الفنادق.');

        // جلب مدير الفندق (دعم hotel_admin أو admin)
        $manager = User::where('hotel_id', $hotel->id)
                       ->whereIn('role', ['hotel_admin', 'admin'])
                       ->first();

        // المحافظات
        $provinces = self::PROVINCES;

        // الصور (تحويل JSON إلى array)
        $images = $hotel->images ? json_decode($hotel->images, true) : [];

        // أرقام الهواتف
        $phones = [];
        if ($hotel->phones) {
            $decoded = json_decode($hotel->phones, true);
            $phones = is_array($decoded) ? $decoded : explode(',', $hotel->phones);
        } elseif ($hotel->phone) {
            $phones = explode(',', $hotel->phone);
        }

        return view('admin.hotels.edit', [
            'hotel' => $hotel,
            'manager' => $manager,
            'provinces' => $provinces,
            'images' => $images,
            'phones' => $phones,
        ]);
    }

    public function update(Request $request, Hotel $hotel)
    {
        abort_unless(auth()->user()?->hasPermission('hotels_edit'), 403, 'غير مصرح لك بتعديل بيانات الفنادق.');

        // جلب مدير الفندق الحالي إن وجد
        $manager = User::where('hotel_id', $hotel->id)
                       ->whereIn('role', ['hotel_admin', 'admin'])
                       ->first();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'amenities' => ['nullable'],
            'custom_amenity' => ['nullable', 'string', 'max:255'],
            'province' => ['required', Rule::in(self::PROVINCES)],
            'stars' => ['required', 'integer', 'between:1,5'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'google_maps_url' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:12'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'deleted_images' => ['nullable', 'array'],
            'deleted_images.*' => ['nullable', 'string'],
            'phones' => ['nullable', 'array'],
            'phones.*' => ['nullable', 'string', 'max:40'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'manager_email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($manager?->id),
            ],
            'manager_password' => [
                $manager ? 'nullable' : 'required_with:manager_email',
                'nullable',
                'string',
                'min:8',
            ],
        ]);

        // تحديث بيانات الفندق الأساسية
        $hotel->name = $validated['name'];
        $hotel->slug = $validated['slug'] ?: Str::slug($validated['name']);
        $hotel->tagline = $validated['tagline'] ?? null;
        $hotel->province = $validated['province'];
        $hotel->stars = $validated['stars'];
        $hotel->email = $validated['email'] ?? null;
        $hotel->address = $validated['address'] ?? null;
        $hotel->google_maps_url = $validated['google_maps_url'] ?? null;
        $hotel->description = $validated['description'] ?? null;

        // تحديث خدمات ومميزات الفندق
        $amenitiesList = [];
        if ($request->has('amenities') && is_array($request->amenities)) {
            $amenitiesList = array_merge($amenitiesList, $request->amenities);
        }
        if ($request->filled('custom_amenity')) {
            $customs = array_filter(array_map('trim', explode(',', $request->custom_amenity)));
            $amenitiesList = array_merge($amenitiesList, $customs);
        }
        $hotel->amenities = !empty($amenitiesList) ? json_encode(array_values(array_unique($amenitiesList)), JSON_UNESCAPED_UNICODE) : null;

        // تحديث أرقام الهواتف
        if ($request->has('phones') && is_array($request->phones)) {
            $filteredPhones = array_values(array_filter(array_map('trim', $request->phones)));
            $hotel->phone = $filteredPhones[0] ?? null;
            $hotel->phones = !empty($filteredPhones) ? json_encode($filteredPhones) : null;
        }

        // التأكد من وجود مجلدات الفندق في التخزين العام
        StorageFolderManager::ensureHotelDirectories($hotel->slug);

        // إزالة الشعار إذا طلب المستخدم
        if ($request->boolean('remove_logo')) {
            if ($hotel->logo) {
                Storage::disk('public')->delete($hotel->logo);
            }
            $hotel->logo = null;
        }

        // تحديث الشعار الجديد
        if ($request->hasFile('logo')) {
            if ($hotel->logo) {
                Storage::disk('public')->delete($hotel->logo);
            }
            $hotel->logo = $request->file('logo')->store('hotels/logos', 'public');
        }

        // تحديث معرض الصور مع دعم الحذف الفردي والإضافة
        $existingImages = $hotel->images ? json_decode($hotel->images, true) : [];
        if (!is_array($existingImages)) {
            $existingImages = [];
        }

        // حذف الصور المحددة بواسطة المستخدم
        if ($request->has('deleted_images') && is_array($request->deleted_images)) {
            foreach ($request->deleted_images as $imgToDelete) {
                $idx = array_search($imgToDelete, $existingImages);
                if ($idx !== false) {
                    unset($existingImages[$idx]);
                    Storage::disk('public')->delete($imgToDelete);
                }
            }
            $existingImages = array_values($existingImages);
        }

        // رفع وإضافة الصور الجديدة
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $existingImages[] = $image->store('hotels/gallery', 'public');
            }
        }

        $hotel->images = !empty($existingImages) ? json_encode($existingImages, JSON_UNESCAPED_SLASHES) : null;
        if ($request->has('is_visible')) {
            $hotel->is_visible = $request->boolean('is_visible');
        }

        $hotel->save();

        // تحديث أو إنشاء حساب مدير الفندق
        if ($request->filled('manager_email')) {
            if ($manager) {
                if ($request->filled('manager_name')) {
                    $manager->name = $request->manager_name;
                }
                $manager->email = $request->manager_email;
                if ($request->filled('manager_password')) {
                    $manager->password = $request->manager_password;
                }
                $manager->role = 'hotel_admin';
                $manager->hotel_id = $hotel->id;
                $manager->save();
            } else {
                User::create([
                    'name' => $request->filled('manager_name') ? $request->manager_name : ($hotel->name . ' مدير'),
                    'email' => $request->manager_email,
                    'password' => $request->manager_password,
                    'role' => 'hotel_admin',
                    'hotel_id' => $hotel->id,
                ]);
            }
        } elseif ($manager) {
            if ($request->filled('manager_name')) {
                $manager->name = $request->manager_name;
            }
            if ($request->filled('manager_password')) {
                $manager->password = $request->manager_password;
            }
            $manager->role = 'hotel_admin';
            $manager->save();
        }

        return redirect()->route('platform.hotels.index')
                         ->with('status', 'تم تحديث بيانات الفندق ومدير الفندق بنجاح.');
    }

    public function destroy(Hotel $hotel)
    {
        abort_unless(auth()->user()?->hasPermission('hotels_delete'), 403, 'غير مصرح لك بأرشفة وحذف الفنادق.');

        $hotel->delete();

        return redirect()->route('platform.hotels.index')
                         ->with('status', 'تمت أرشفة الفندق بنجاح.');
    }

    public function toggleVisibility(Hotel $hotel)
    {
        abort_unless(auth()->user()?->hasPermission('hotels_edit') || auth()->user()?->role === 'super_admin', 403);
        $hotel->is_visible = !$hotel->is_visible;
        $hotel->save();

        $statusText = $hotel->is_visible ? 'ظاهراً في الصفحة الرئيسية' : 'مخفياً من الصفحة الرئيسية';
        return back()->with('status', "تم تحديث حالة الفندق: أصبح الفندق الآن {$statusText}.");
    }
}
