@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl py-6 px-4">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-blue-600 block mb-1">لوحة تحكم الفندق</span>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900">بروفايل ومعلومات الفندق</h1>
            <p class="text-sm text-slate-500 mt-1">تخصيص البيانات الترويجية، الشعار، الصور، والخدمات والميزات التي تظهر للزوار</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('hotel.front', $hotel->slug) }}" target="_blank" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1.5">
                <span>👁️</span>
                <span>معاينة صفحة الفندق</span>
            </a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary btn-sm">العودة للوحة الفندق</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(auth()->user()->role === 'hotel_owner')
        <div class="alert alert-warning border border-warning/40 rounded-2xl d-flex align-items-center gap-3 p-3.5 mb-4 shadow-sm" role="alert">
            <span class="fs-4">👑</span>
            <div>
                <strong class="d-block text-amber-950 font-bold">حساب مالك الفندق (وضع الاطلاع والمعاينة فقط)</strong>
                <span class="text-xs text-amber-900">يحق لك معاينة كافة بيانات البروفايل، المعرض، الخدمات، والأسعار بدون صلاحية التعديل أو الحفظ.</span>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.hotel-profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Basic Info Card --}}
        <div class="card p-4 sm:p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <h2 class="text-lg font-extrabold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>🏨</span>
                <span>المعلومات الأساسية والترويجية</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">اسم الفندق <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $hotel->name) }}" required class="w-full rounded-xl border border-slate-300 p-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">المحافظة <span class="text-red-500">*</span></label>
                    <select name="province" required class="w-full rounded-xl border border-slate-300 p-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        @foreach($provinces as $province)
                            <option value="{{ $province }}" @selected(old('province', $hotel->province) === $province)>{{ $province }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-slate-700 mb-1">
                        الجملة الترويجية / السلوجان (Tagline)
                        <span class="text-xs font-normal text-slate-500 mr-2">(تظهر بخط مميز على بطاقة الفندق وصفحة الحجز)</span>
                    </label>
                    <input type="text" name="tagline" value="{{ old('tagline', $hotel->tagline) }}" placeholder="مثال: فندق خمس نجوم بإطلالة بحرية ساحرة وخدمات ملكية" class="w-full rounded-xl border border-slate-300 p-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">تصنيف النجوم <span class="text-red-500">*</span></label>
                    <select name="stars" class="w-full rounded-xl border border-slate-300 p-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        @for($stars = 1; $stars <= 5; $stars++)
                            <option value="{{ $stars }}" @selected(old('stars', $hotel->stars) == $stars)>{{ $stars }} نجوم ({{ str_repeat('★', $stars) }})</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">البريد الإلكتروني للفندق</label>
                    <input type="email" name="email" value="{{ old('email', $hotel->email) }}" class="w-full rounded-xl border border-slate-300 p-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-slate-700 mb-1">العنوان التفصيلي</label>
                    <input type="text" name="address" value="{{ old('address', $hotel->address) }}" placeholder="مثال: دمشق، شارع شكري القوتلي، قرب ساحة الأمويين" class="w-full rounded-xl border border-slate-300 p-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-slate-700 mb-1">📍 رابط الموقع على خرائط جوجل (Google Maps Link)</label>
                    <input type="url" name="google_maps_url" value="{{ old('google_maps_url', $hotel->google_maps_url) }}" placeholder="https://maps.google.com/?q=... أو رابط الموقع من تطبيق الخرائط" class="w-full rounded-xl border border-slate-300 p-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <p class="text-xs text-slate-400 mt-1 mb-0">يمكنك نسخ رابط الموقع مباشرة من تطبيق Google Maps لتسهيل وصول النزلاء إلى موقع الفندق عبر الخرائط والاتجاهات.</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-slate-700 mb-1">نبذة ووصف الفندق</label>
                    <textarea name="description" rows="3" class="w-full rounded-xl border border-slate-300 p-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">{{ old('description', $hotel->description) }}</textarea>
                </div>
            </div>

            {{-- Phone Numbers --}}
            <div class="mt-4 pt-4 border-t border-slate-100">
                <span class="block text-sm font-bold text-slate-700 mb-2">📞 أرقام هواتف التواصل والاستقبال</span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @for($i = 0; $i < 3; $i++)
                        <input type="tel" name="phones[]" value="{{ old('phones.' . $i, $phones[$i] ?? '') }}" placeholder="رقم هاتف {{ $i + 1 }}" class="rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    @endfor
                </div>
            </div>
        </div>

        {{-- Amenities Card --}}
        <div class="card p-4 sm:p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <h2 class="text-lg font-extrabold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>✨</span>
                <span>الميزات والخدمات (Amenities)</span>
            </h2>
            <p class="text-xs text-slate-500 mb-4">اختر الخدمات المتوفرة في فندقك لتظهر كشارات جذابة أمام الضيوف في الموقع العام:</p>

            @php
                $currentAmenities = $hotel->amenities_list;
                $presetAmenities = [
                    '📶 واي فاي مجاني',
                    '❄️ تكييف مركزي',
                    '🍳 إفطار فاخر',
                    '🚗 مواقف سيارات مجانية',
                    '🏊 مسبح خارجي/داخلي',
                    '🌊 إطلالة بحرية ساحرة',
                    '🍽️ مطعم وبوفيه مفتوح',
                    '🏋️ صالة لياقة بدنية (جيم)',
                    '☕ مقهى وتراس',
                    '🛗 مصعد كهربائي',
                    '🛎️ خدمة غرف 24/7',
                    '🛡️ حراسة وأمان 24/7',
                    '🧺 خدمة غسيل وكوي',
                    '🚕 خدمة نقل وتوصيل مطار'
                ];
            @endphp

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mb-4">
                @foreach($presetAmenities as $amenity)
                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                        <input type="checkbox" name="amenities[]" value="{{ $amenity }}" 
                            @checked(in_array($amenity, (array)old('amenities', $currentAmenities)))
                            class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4">
                        <span class="text-xs font-semibold text-slate-800">{{ $amenity }}</span>
                    </label>
                @endforeach
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">خدمات ومميزات إضافية مخصصة (مفصولة بفاصلة):</label>
                <input type="text" name="custom_amenity" placeholder="مثال: سبا وجاكوزي, حديقة أطفال, قاعة مؤتمرات" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>
        </div>

        {{-- Logo Card --}}
        <div class="card p-4 sm:p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <h2 class="text-lg font-extrabold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>🏷️</span>
                <span>شعار الفندق (Logo)</span>
            </h2>

            <input type="hidden" name="remove_logo" id="remove_logo_input" value="0">
            @if($hotel->logo_url)
                <div id="hotel_logo_container" class="relative inline-block mb-3">
                    <img src="{{ $hotel->logo_url }}" alt="شعار {{ $hotel->name }}" class="h-24 w-24 object-contain rounded-xl border border-slate-200 bg-slate-50 p-1.5 shadow-sm">
                    <button type="button" id="btn_delete_hotel_logo"
                            class="absolute -top-2 -right-2 bg-red-600 hover:bg-red-700 text-white rounded-full w-6 h-6 flex items-center justify-center shadow text-xs font-bold"
                            title="حذف الشعار">
                        ×
                    </button>
                </div>
                <div id="logo_delete_notice" class="hidden mb-3 text-xs text-red-600 bg-red-50 p-2.5 rounded-xl flex items-center justify-between border border-red-200">
                    <span>سيتم إزالة الشعار فور حفظ التعديلات.</span>
                    <button type="button" id="btn_undo_delete_logo" class="text-blue-600 font-bold hover:underline">تراجع عن الحذف</button>
                </div>
            @endif

            <label class="block text-sm">
                <span class="font-bold text-slate-700">{{ $hotel->logo_url ? 'استبدال الشعار:' : 'رفع شعار الفندق:' }}</span>
                <input type="file" name="logo" id="hotel_logo_input" accept="image/png,image/jpeg,image/webp" class="mt-1 w-full rounded-xl border border-slate-300 p-2 bg-white text-sm">
            </label>
        </div>

        {{-- Image Gallery Card with delete icon per picture --}}
        <div class="card p-4 sm:p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <h2 class="text-lg font-extrabold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>🖼️</span>
                <span>معرض صور الفندق (Gallery)</span>
            </h2>

            @if(count($images))
                <span class="text-xs text-slate-600 block mb-3 font-medium">الصور المعروضة حالياً (انقر على أيقونة × الحمراء على أي صورة لإزالتها):</span>
                <div class="flex flex-wrap gap-4 mb-5" id="existing_images_gallery">
                    @foreach($images as $image)
                        @php $imgUrl = $hotel->image_urls[$loop->index] ?? asset('storage/' . $image); @endphp
                        <div class="relative group hotel-gallery-item rounded-2xl overflow-hidden border border-slate-200 shadow-sm" data-image-path="{{ $image }}" style="width: 140px; height: 105px;">
                            <img src="{{ $imgUrl }}" alt="صورة الفندق" class="w-full h-full object-cover">

                            <!-- أيقونة حذف الصورة الصغيرة فوق الصورة -->
                            <button type="button"
                                    class="btn-delete-gallery-img absolute top-1.5 right-1.5 bg-red-600 hover:bg-red-700 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold shadow-md transition-transform hover:scale-110"
                                    title="حذف هذه الصورة">
                                ×
                            </button>

                            <!-- شارة تأكيد الحذف المؤقت مع إمكانية التراجع -->
                            <div class="deleted-overlay hidden absolute inset-0 bg-red-900/80 backdrop-blur-sm text-white text-xs rounded-2xl flex flex-col items-center justify-center p-2 text-center">
                                <span class="font-bold">سيتم حذفها</span>
                                <button type="button" class="btn-undo-gallery-img text-amber-300 font-bold underline text-[11px] mt-1 hover:text-white">تراجع</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-slate-400 mb-4 bg-slate-50 p-3 rounded-xl border border-dashed border-slate-200">لا توجد صور في المعرض حالياً. يمكنك رفع مجموعة صور مميزة ليراها النزلاء.</p>
            @endif

            <div id="deleted_images_inputs_container"></div>

            <label class="block text-sm">
                <span class="font-bold text-slate-700">إضافة صور جديدة للمعرض:</span>
                <input type="file" name="images[]" id="new_gallery_files" accept="image/png,image/jpeg,image/webp" multiple class="mt-1 w-full rounded-xl border border-slate-300 p-2 bg-white text-sm">
            </label>

            <!-- معاينة الصور الجديدة فور اختيارها -->
            <div id="new_gallery_preview_container" class="hidden mt-4">
                <span class="text-xs text-emerald-700 font-bold block mb-2">معاينة الصور الجديدة المختارة:</span>
                <div id="new_gallery_preview_grid" class="flex flex-wrap gap-3"></div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-light px-4 py-2 text-sm font-bold">العودة للوحة الفندق</a>
            @if(auth()->user()->role !== 'hotel_owner')
                <button type="submit" class="btn btn-primary px-6 py-2.5 text-sm font-bold shadow">
                    💾 حفظ كافة التعديلات
                </button>
            @else
                <span class="badge bg-warning text-dark font-bold px-4 py-2.5 rounded-xl">👑 مالك الفندق - وضع الاطلاع فقط</span>
            @endif
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // إدارة حذف وتراجع شعار الفندق
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

    // إدارة حذف وتراجع صور المعرض الفردية
    const deletedContainer = document.getElementById('deleted_images_inputs_container');
    document.querySelectorAll('.hotel-gallery-item').forEach(function(item) {
        const path = item.getAttribute('data-image-path');
        const btnDel = item.querySelector('.btn-delete-gallery-img');
        const btnUndo = item.querySelector('.btn-undo-gallery-img');
        const overlay = item.querySelector('.deleted-overlay');

        if (btnDel) {
            btnDel.addEventListener('click', function() {
                overlay.classList.remove('hidden');
                // إنشاء input hidden لإرساله للسيرفر
                let hiddenInput = deletedContainer.querySelector('input[value="' + path + '"]');
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'deleted_images[]';
                    hiddenInput.value = path;
                    deletedContainer.appendChild(hiddenInput);
                }
            });
        }

        if (btnUndo) {
            btnUndo.addEventListener('click', function() {
                overlay.classList.add('hidden');
                const hiddenInput = deletedContainer.querySelector('input[value="' + path + '"]');
                if (hiddenInput) {
                    hiddenInput.remove();
                }
            });
        }
    });

    // معاينة الصور الجديدة محلياً قبل الرفع
    const newFilesInput = document.getElementById('new_gallery_files');
    const previewContainer = document.getElementById('new_gallery_preview_container');
    const previewGrid = document.getElementById('new_gallery_preview_grid');

    if (newFilesInput) {
        newFilesInput.addEventListener('change', function(e) {
            previewGrid.innerHTML = '';
            if (this.files && this.files.length > 0) {
                previewContainer.classList.remove('hidden');
                Array.from(this.files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        const div = document.createElement('div');
                        div.className = 'w-24 h-20 rounded-xl overflow-hidden border border-emerald-300 shadow-sm relative';
                        div.innerHTML = `<img src="${evt.target.result}" class="w-full h-full object-cover">`;
                        previewGrid.appendChild(div);
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
@endsection

