@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl py-10">
    <h1 class="mb-6 text-3xl font-bold">تعديل {{ $hotel->name }}</h1>

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('platform.hotels.update', $hotel) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf @method('PUT')
        <label class="block">اسم الفندق
            <input name="name" value="{{ old('name', $hotel->name) }}" required class="mt-1 w-full rounded border p-2">
        </label>
        <label class="block">الرابط (اتركه فارغًا لتوليده من الاسم)
            <input name="slug" value="{{ old('slug') }}" pattern="[A-Za-z0-9_-]+" class="mt-1 w-full rounded border p-2">
        </label>
        <label class="block">المحافظة
            <select name="province" required class="mt-1 w-full rounded border p-2">
                @foreach($provinces as $province)
                    <option value="{{ $province }}" @selected(old('province', $hotel->province) === $province)>{{ $province }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">التصنيف
            <select name="stars" class="mt-1 w-full rounded border p-2">
                @for($stars = 1; $stars <= 5; $stars++)
                    <option value="{{ $stars }}" @selected(old('stars', $hotel->stars) == $stars)>{{ $stars }} نجوم</option>
                @endfor
            </select>
        </label>
        <label class="block">البريد الإلكتروني
            <input type="email" name="email" value="{{ old('email', $hotel->email) }}" class="mt-1 w-full rounded border p-2">
        </label>
        <fieldset>
            <legend>أرقام الهواتف</legend>
            @for($index = 0; $index < 3; $index++)
                <input type="tel" name="phones[]" value="{{ old('phones.' . $index, $phones[$index] ?? '') }}" class="mb-2 w-full rounded border p-2" placeholder="رقم هاتف {{ $index + 1 }}">
            @endfor
        </fieldset>
        <label class="block">الجملة الترويجية / السلوجان (Tagline)
            <input name="tagline" value="{{ old('tagline', $hotel->tagline) }}" placeholder="مثال: فندق خمس نجوم بإطلالة بحرية رائعة" class="mt-1 w-full rounded border p-2">
        </label>
        <label class="block">العنوان
            <input name="address" value="{{ old('address', $hotel->address) }}" placeholder="العنوان التفصيلي" class="mt-1 w-full rounded border p-2">
        </label>
        <label class="block">موقع الفندق على خرائط جوجل (Google Maps URL)
            <input type="url" name="google_maps_url" value="{{ old('google_maps_url', $hotel->google_maps_url) }}" placeholder="https://maps.google.com/?q=... أو رابط المشاركة من تطبيق خرائط جوجل" class="mt-1 w-full rounded border p-2">
        </label>
        <label class="block">الوصف
            <textarea name="description" rows="4" class="mt-1 w-full rounded border p-2">{{ old('description', $hotel->description) }}</textarea>
        </label>

        <!-- الظهور في الصفحة الرئيسية -->
        <div class="border rounded p-3 bg-blue-50 dark:bg-slate-800 border-blue-200">
            <label class="flex items-center gap-2 cursor-pointer font-bold text-sm text-blue-900 dark:text-blue-200">
                <input type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $hotel->is_visible)) class="rounded h-5 w-5 text-blue-600">
                <span>👁️ إظهار الفندق في الصفحة الرئيسية للزوار والجمهور</span>
            </label>
            <p class="text-xs text-slate-500 mt-1 mb-0">عند إلغاء التحديد، يختفي الفندق من قائمة الفنادق في الصفحة الرئيسية، ويبقى فعالاً لمدير الفندق وموظفيه.</p>
        </div>

        <!-- الخدمات والميزات (Amenities) -->
        <div class="border rounded p-4 bg-gray-50">
            <span class="block font-bold mb-2">خدمات ومميزات الفندق (تظهر كشارات في بطاقة الفندق)</span>
            @php
                $currentAmenities = $hotel->amenities_list;
                $presetAmenities = [
                    '📶 واي فاي مجاني',
                    '❄️ تكييف مركزي',
                    '🍳 إفطار فاخر',
                    '🚗 مواقف سيارات مجانية',
                    '🏊 مسبح خارجي/داخلي',
                    '🌊 إطلالة بحرية ساحرة',
                    '🍽️ مطعم فاخر',
                    '🏋️ صالة لياقة بدنية (جيم)',
                    '☕ مقهى وتراس',
                    '🛗 مصعد كهربائي',
                    '🛎️ خدمة غرف 24/7',
                    '🛡️ حراسة 24/7'
                ];
            @endphp
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 mb-3">
                @foreach($presetAmenities as $amenity)
                    <label class="flex items-center gap-2 p-2 rounded border border-gray-200 bg-white hover:bg-blue-50 cursor-pointer text-xs">
                        <input type="checkbox" name="amenities[]" value="{{ $amenity }}" 
                            @checked(in_array($amenity, (array)old('amenities', $currentAmenities)))
                            class="rounded text-blue-600 focus:ring-blue-500">
                        <span>{{ $amenity }}</span>
                    </label>
                @endforeach
            </div>
            <label class="block text-xs font-semibold text-gray-700">
                إضافة مميزات مخصصة (مفصولة بفاصلة):
                <input type="text" name="custom_amenity" placeholder="مثال: سبا وجاكوزي, قاعة مؤتمرات" class="mt-1 w-full rounded border p-2 bg-white text-xs">
            </label>
        </div>
        <!-- إدارة الشعار -->
        <div class="border rounded p-4 bg-gray-50">
            <span class="block font-bold mb-2">شعار الفندق</span>
            <input type="hidden" name="remove_logo" id="remove_logo_input" value="0">
            @if($hotel->logo_url)
                <div id="hotel_logo_container" class="relative inline-block mb-3">
                    <img src="{{ $hotel->logo_url }}" alt="شعار {{ $hotel->name }}" class="h-24 w-24 object-contain rounded border bg-white p-1 shadow-sm">
                    <button type="button" id="btn_delete_hotel_logo"
                            class="absolute -top-2 -right-2 bg-red-600 hover:bg-red-700 text-white rounded-full w-6 h-6 flex items-center justify-center shadow text-xs"
                            title="حذف الشعار">
                        ×
                    </button>
                </div>
                <div id="logo_delete_notice" class="hidden mb-2 text-xs text-red-600 bg-red-50 p-2 rounded flex items-center justify-between">
                    <span>سيتم إزالة الشعار عند الحفظ.</span>
                    <button type="button" id="btn_undo_delete_logo" class="text-blue-600 font-bold hover:underline">تراجع</button>
                </div>
            @endif
            <label class="block text-sm">
                <span>{{ $hotel->logo_url ? 'استبدال الشعار:' : 'رفع شعار الفندق:' }}</span>
                <input type="file" name="logo" id="hotel_logo_input" accept="image/png,image/jpeg,image/webp" class="mt-1 w-full rounded border p-2 bg-white">
            </label>
        </div>

        <!-- معرض صور الفندق الحالية والجديدة -->
        <div class="border rounded p-4 bg-gray-50">
            <span class="block font-bold mb-2">معرض صور الفندق</span>

            {{-- الصور الحالية مع أيقونة حذف صغيرة على كل صورة --}}
            @if(count($images))
                <span class="text-xs text-gray-600 block mb-2">الصور الحالية (انقر على أيقونة × على أي صورة لإزالتها):</span>
                <div class="flex flex-wrap gap-3 mb-4" id="existing_images_gallery">
                    @foreach($images as $image)
                        @php $imgUrl = $hotel->image_urls[$loop->index] ?? asset('storage/' . $image); @endphp
                        <div class="relative group hotel-gallery-item" data-image-path="{{ $image }}" style="width: 120px; height: 90px;">
                            <img src="{{ $imgUrl }}" alt="صورة الفندق" class="w-full h-full rounded object-cover border shadow-sm gallery-thumb">

                            <!-- أيقونة حذف الصورة الصغيرة فوق الصورة -->
                            <button type="button"
                                    class="btn-delete-gallery-img absolute top-1 right-1 bg-red-600 hover:bg-red-700 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs shadow-md transition-transform hover:scale-110"
                                    title="حذف هذه الصورة">
                                ×
                            </button>

                            <!-- شارة محذوف -->
                            <div class="deleted-overlay hidden absolute inset-0 bg-red-900 bg-opacity-70 text-white text-xs rounded flex flex-col items-center justify-center">
                                <span class="font-bold">تم الحذف</span>
                                <button type="button" class="btn-undo-gallery-img text-blue-200 underline text-xs mt-1">تراجع</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-gray-500 mb-3">لا توجد صور في المعرض حالياً.</p>
            @endif

            <div id="deleted_images_inputs_container"></div>

            <!-- رفع صور إضافية جديدة -->
            <label class="block text-sm">
                <span>إضافة صور جديدة للمعرض:</span>
                <input type="file" name="images[]" id="new_gallery_files" accept="image/png,image/jpeg,image/webp" multiple class="mt-1 w-full rounded border p-2 bg-white">
            </label>

            <!-- معاينة الصور الجديدة المختارة فوراً -->
            <div id="new_gallery_preview_container" class="hidden mt-3">
                <span class="text-xs text-green-700 font-semibold block mb-2">معاينة الصور الجديدة المختارة:</span>
                <div id="new_gallery_preview_grid" class="flex flex-wrap gap-2"></div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // حذف وتراجع الشعار
            const btnDeleteLogo = document.getElementById('btn_delete_hotel_logo');
            const btnUndoLogo = document.getElementById('btn_undo_delete_logo');
            const removeLogoInput = document.getElementById('remove_logo_input');
            const logoContainer = document.getElementById('hotel_logo_container');
            const logoNotice = document.getElementById('logo_delete_notice');

            if (btnDeleteLogo) {
                btnDeleteLogo.addEventListener('click', function() {
                    removeLogoInput.value = '1';
                    if (logoContainer) logoContainer.classList.add('opacity-30');
                    if (logoNotice) logoNotice.classList.remove('hidden');
                });
            }

            if (btnUndoLogo) {
                btnUndoLogo.addEventListener('click', function() {
                    removeLogoInput.value = '0';
                    if (logoContainer) logoContainer.classList.remove('opacity-30');
                    if (logoNotice) logoNotice.classList.add('hidden');
                });
            }

            // حذف وتراجع صور المعرض الحالية
            const deletedContainer = document.getElementById('deleted_images_inputs_container');
            document.querySelectorAll('.hotel-gallery-item').forEach(function(item) {
                const path = item.getAttribute('data-image-path');
                const btnDel = item.querySelector('.btn-delete-gallery-img');
                const btnUndo = item.querySelector('.btn-undo-gallery-img');
                const overlay = item.querySelector('.deleted-overlay');

                if (btnDel) {
                    btnDel.addEventListener('click', function() {
                        overlay.classList.remove('hidden');
                        btnDel.classList.add('hidden');

                        // إنشاء input مخفي لإرسال مسار الصورة للحذف
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'deleted_images[]';
                        hiddenInput.value = path;
                        hiddenInput.id = 'del_img_' + btoa(path).replace(/=/g, '');
                        deletedContainer.appendChild(hiddenInput);
                    });
                }

                if (btnUndo) {
                    btnUndo.addEventListener('click', function() {
                        overlay.classList.add('hidden');
                        btnDel.classList.remove('hidden');

                        const inputId = 'del_img_' + btoa(path).replace(/=/g, '');
                        const existingInput = document.getElementById(inputId);
                        if (existingInput) {
                            existingInput.remove();
                        }
                    });
                }
            });

            // معاينة الصور الجديدة المختارة
            const newFilesInput = document.getElementById('new_gallery_files');
            const previewContainer = document.getElementById('new_gallery_preview_container');
            const previewGrid = document.getElementById('new_gallery_preview_grid');

            if (newFilesInput) {
                newFilesInput.addEventListener('change', function(e) {
                    previewGrid.innerHTML = '';
                    const files = Array.from(e.target.files);
                    if (files.length > 0) {
                        previewContainer.classList.remove('hidden');
                        files.forEach(function(file) {
                            const reader = new FileReader();
                            reader.onload = function(evt) {
                                const imgDiv = document.createElement('div');
                                imgDiv.className = 'w-24 h-20 rounded border border-green-400 overflow-hidden shadow-sm';
                                imgDiv.innerHTML = `<img src="${evt.target.result}" class="w-full h-full object-cover">`;
                                previewGrid.appendChild(imgDiv);
                            };
                            reader.readAsDataURL(file);
                        });
                    } else {
                        previewContainer.classList.add('hidden');
                    }
                });
            }
        });
        </script>

     <h3 class="text-lg font-bold mt-6 mb-2">بيانات مدير الفندق</h3>

<label class="block mb-3">
    <span>اسم مدير الفندق</span>
    <input type="text" name="manager_name" value="{{ old('manager_name', $manager->name ?? '') }}"
           class="mt-1 w-full rounded border p-2">
</label>

<label class="block mb-3">
    <span>إيميل مدير الفندق</span>
    <input type="email" name="manager_email" value="{{ old('manager_email', $manager->email ?? '') }}"
           class="mt-1 w-full rounded border p-2">
</label>

<label class="block mb-3">
    <span>باسورد مدير الفندق (اتركه فارغًا إذا لا تريد تغييره)</span>
    <input type="password" name="manager_password"
           class="mt-1 w-full rounded border p-2" autocomplete="new-password">
</label>




        <button class="rounded bg-blue-700 px-5 py-2 font-semibold text-white">حفظ التعديلات</button>
    </form>
</div>
@endsection