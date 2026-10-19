<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class HotelProfileController extends Controller
{
    private const PROVINCES = [
        'دمشق', 'ريف دمشق', 'حلب', 'حمص', 'حماة', 'اللاذقية', 'طرطوس',
        'إدلب', 'درعا', 'السويداء', 'القنيطرة', 'دير الزور', 'الرقة', 'الحسكة',
    ];

    public function edit()
    {
        $user = auth()->user();
        abort_unless($user && $user->hotel_id, 403, 'غير مصرح لك بإدارة هذا الفندق.');

        $hotel = Hotel::findOrFail($user->hotel_id);
        $provinces = self::PROVINCES;

        $images = $hotel->images ? (json_decode($hotel->images, true) ?: []) : [];

        $phones = [];
        if ($hotel->phones) {
            $decoded = json_decode($hotel->phones, true);
            $phones = is_array($decoded) ? $decoded : explode(',', $hotel->phones);
        } elseif ($hotel->phone) {
            $phones = explode(',', $hotel->phone);
        }

        return view('admin.hotel-profile.edit', compact('hotel', 'provinces', 'images', 'phones'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        abort_unless($user && $user->hotel_id, 403, 'غير مصرح لك بإدارة هذا الفندق.');

        $hotel = Hotel::findOrFail($user->hotel_id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'province' => ['required', Rule::in(self::PROVINCES)],
            'stars' => ['required', 'integer', 'between:1,5'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'google_maps_url' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'amenities' => ['nullable'],
            'custom_amenity' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:12'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'deleted_images' => ['nullable', 'array'],
            'deleted_images.*' => ['nullable', 'string'],
            'phones' => ['nullable', 'array'],
            'phones.*' => ['nullable', 'string', 'max:40'],
        ]);

        $hotel->name = $validated['name'];
        $hotel->tagline = $validated['tagline'] ?? null;
        $hotel->province = $validated['province'];
        $hotel->stars = $validated['stars'];
        $hotel->email = $validated['email'] ?? null;
        $hotel->address = $validated['address'] ?? null;
        $hotel->google_maps_url = $validated['google_maps_url'] ?? null;
        $hotel->description = $validated['description'] ?? null;

        // الخدمات والمميزات
        $amenitiesList = [];
        if ($request->has('amenities') && is_array($request->amenities)) {
            $amenitiesList = array_merge($amenitiesList, $request->amenities);
        }
        if ($request->filled('custom_amenity')) {
            $customs = array_filter(array_map('trim', explode(',', $request->custom_amenity)));
            $amenitiesList = array_merge($amenitiesList, $customs);
        }
        $hotel->amenities = !empty($amenitiesList) ? json_encode(array_values(array_unique($amenitiesList)), JSON_UNESCAPED_UNICODE) : null;

        // الهواتف
        if ($request->has('phones') && is_array($request->phones)) {
            $filteredPhones = array_values(array_filter(array_map('trim', $request->phones)));
            $hotel->phone = $filteredPhones[0] ?? null;
            $hotel->phones = !empty($filteredPhones) ? json_encode($filteredPhones) : null;
        }

        // إزالة الشعار
        if ($request->boolean('remove_logo')) {
            if ($hotel->logo) {
                Storage::disk('public')->delete($hotel->logo);
            }
            $hotel->logo = null;
        }

        // رفع الشعار الجديد
        if ($request->hasFile('logo')) {
            if ($hotel->logo) {
                Storage::disk('public')->delete($hotel->logo);
            }
            $hotel->logo = $request->file('logo')->store('hotels/logos', 'public');
        }

        // معرض الصور
        $existingImages = $hotel->images ? (json_decode($hotel->images, true) ?: []) : [];

        // حذف الصور المحددة
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

        // رفع الصور الجديدة
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $existingImages[] = $image->store('hotels/gallery', 'public');
            }
        }

        $hotel->images = !empty($existingImages) ? json_encode($existingImages, JSON_UNESCAPED_SLASHES) : null;
        $hotel->save();

        return redirect()->route('admin.hotel-profile.edit')->with('success', 'تم حفظ وتحديث ملف ومعلومات الفندق بنجاح.');
    }
}

