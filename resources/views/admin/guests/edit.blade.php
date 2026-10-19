@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl py-6 px-4">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-blue-600 block mb-1">الاستقبال والنزلاء</span>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900">تعديل بيانات النزيل: {{ $guest->full_name }}</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">تحديث البيانات وهوية النزيل والمستندات المرفقة</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('guests.print', $guest) }}" target="_blank" class="btn btn-outline-secondary btn-sm font-bold">
                🖨️ طباعة بطاقة النزيل
            </a>
            <a href="{{ route('guests.index') }}" class="btn btn-secondary btn-sm font-medium">العودة للنزلاء</a>
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

    <form method="POST" action="{{ route('guests.update', $guest) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Personal Details Card --}}
        <div class="card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <h2 class="text-lg font-extrabold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>🪪</span>
                <span>البيانات الشخصية وهوية النزيل</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">الاسم الأول <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name', $guest->first_name ?: $guest->name) }}" required class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">اسم الأب</label>
                    <input type="text" name="father_name" value="{{ old('father_name', $guest->father_name) }}" placeholder="اسم الأب" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">النسبة / الكنية <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name', $guest->last_name) }}" required placeholder="النسبة أو الكنية" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">الجنسية <span class="text-red-500">*</span></label>
                    <input type="text" name="nationality" value="{{ old('nationality', $guest->nationality ?: 'سوري') }}" required class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">تاريخ المواليد</label>
                    <input type="date" name="birth_date" value="{{ old('birth_date', $guest->birth_date) }}" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">نوع الوثيقة <span class="text-red-500">*</span></label>
                    <select name="id_type" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="national_id" @selected(old('id_type', $guest->id_type) === 'national_id')>بطاقة شخصية / هوية</option>
                        <option value="passport" @selected(old('id_type', $guest->id_type) === 'passport')>جواز سفر</option>
                        <option value="residence" @selected(old('id_type', $guest->id_type) === 'residence')>بطاقة إقامة</option>
                        <option value="driver_license" @selected(old('id_type', $guest->id_type) === 'driver_license')>رخصة قيادة</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">رقم الوثيقة / الجواز <span class="text-red-500">*</span></label>
                    <input type="text" name="id_number" value="{{ old('id_number', $guest->id_number ?: $guest->national_id) }}" required class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">رقم الهاتف / الموبايل</label>
                    <input type="tel" name="phone" value="{{ old('phone', $guest->phone) }}" placeholder="09xxxxxxxx" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">البريد الإلكتروني</label>
                    <input type="email" name="email" value="{{ old('email', $guest->email) }}" placeholder="guest@example.com" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-bold text-slate-700 mb-1">ملاحظات إضافية عن النزيل</label>
                <textarea name="notes" rows="2" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">{{ old('notes', $guest->notes) }}</textarea>
            </div>
        </div>

        {{-- Documents & Attachments Card --}}
        <div class="card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-2 border-b border-slate-100">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
                        <span>📷</span>
                        <span>الوثائق والمرفقات المؤرشفة</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">يمكنك معاينة أو حذف الوثائق السابقة وإضافة صور جديدة عبر الرفع أو المسح المباشر</p>
                </div>
                <button type="button" id="btn_toggle_scanner" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1.5 font-bold shadow-xs">
                    <span>📹</span>
                    <span>فتح الكاميرا / السكنر المباشر</span>
                </button>
            </div>

            {{-- Existing Attachments with Individual Delete Icon --}}
            @php $attachments = $guest->attachments_list; @endphp
            @if(count($attachments))
                <div class="mb-5">
                    <span class="text-xs font-bold text-slate-700 block mb-2">الوثائق الحالية (انقر على أيقونة × على أي وثيقة لإزالتها):</span>
                    <div class="flex flex-wrap gap-4" id="existing_attachments_gallery">
                        @foreach($attachments as $att)
                            <div class="relative group guest-att-item rounded-xl overflow-hidden border border-slate-200 shadow-sm" data-att-path="{{ $att['path'] }}" style="width: 130px; height: 100px;">
                                @if(in_array(strtolower($att['type']), ['jpg', 'jpeg', 'png', 'webp']))
                                    <img src="{{ $att['url'] }}" alt="مرفق" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex flex-col items-center justify-center bg-slate-100 text-slate-600 p-1">
                                        <span class="text-2xl">📄</span>
                                        <span class="text-[10px] font-bold uppercase">{{ $att['type'] }}</span>
                                    </div>
                                @endif

                                <a href="{{ $att['url'] }}" target="_blank" class="absolute bottom-1 left-1 bg-slate-900/80 text-white text-[10px] rounded px-1.5 py-0.5" title="معاينة كاملة">
                                    👁️
                                </a>

                                <button type="button"
                                        class="btn-delete-att absolute top-1 right-1 bg-red-600 hover:bg-red-700 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold shadow transition-transform hover:scale-110"
                                        title="حذف هذا المرفق">
                                    ×
                                </button>

                                <div class="deleted-overlay hidden absolute inset-0 bg-red-900/85 text-white text-xs rounded-xl flex flex-col items-center justify-center p-2 text-center">
                                    <span class="font-bold text-[11px]">سيتم حذفها</span>
                                    <button type="button" class="btn-undo-att text-amber-300 font-bold underline text-[10px] mt-1 hover:text-white">تراجع</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div id="deleted_attachments_inputs_container"></div>

            {{-- Direct Scanner / Webcam Section --}}
            <div id="scanner_section" class="hidden mb-6 p-4 rounded-2xl bg-slate-900 text-white border border-slate-800 shadow-inner">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-slate-300 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        كاميرا ومسح ضوئي مباشر للوثائق
                    </span>
                    <button type="button" id="btn_close_scanner" class="text-xs text-slate-400 hover:text-white">✕ إغلاق الكاميرا</button>
                </div>

                {{-- Device Selector --}}
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2 bg-slate-800 p-2.5 rounded-xl border border-slate-700">
                    <div class="flex items-center gap-2 flex-1 min-w-[220px]">
                        <span class="text-xs text-slate-300 font-bold whitespace-nowrap">جهاز الإدخال:</span>
                        <select id="scanner_device_selector" class="bg-slate-900 text-white text-xs rounded-lg p-2 border border-slate-600 w-full focus:outline-none">
                            <option value="">الكاميرا / السكنر التلقائي</option>
                        </select>
                    </div>
                    <button type="button" id="btn_refresh_devices" class="text-xs bg-slate-700 hover:bg-slate-600 text-white px-3 py-1.5 rounded-lg flex items-center gap-1 font-bold transition">
                        <span>🔄</span>
                        <span>تحديث الأجهزة</span>
                    </button>
                </div>

                <div class="relative w-full max-w-md mx-auto aspect-video rounded-xl overflow-hidden bg-black border border-slate-700">
                    <video id="scanner_video" autoplay playsinline class="w-full h-full object-cover"></video>
                    <canvas id="scanner_canvas" class="hidden"></canvas>
                </div>

                <div class="text-center mt-3">
                    <button type="button" id="btn_capture_doc" class="btn btn-primary px-5 py-2 text-sm font-bold shadow-lg">
                        📸 التقاط صورة الوثيقة الآن
                    </button>
                </div>
            </div>

            {{-- Add new files --}}
            <div class="p-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 mb-4">
                <label class="block cursor-pointer text-center">
                    <span class="text-2xl block mb-1">➕</span>
                    <span class="text-sm font-bold text-slate-700 block">رفع وثائق ومرفقات إضافية</span>
                    <span class="text-xs text-slate-400 block mt-0.5">يدعم JPG, PNG, WEBP, PDF حتى 8 ميغابايت لكل ملف</span>
                    <input type="file" name="attachments[]" id="file_attachments_input" multiple accept="image/*,application/pdf" class="hidden">
                </label>
            </div>

            {{-- Container for Captured & Uploaded Previews --}}
            <div>
                <span class="text-xs font-bold text-slate-700 block mb-2">المرفقات الجديدة المختارة:</span>
                <div id="attachments_preview_grid" class="flex flex-wrap gap-3">
                    <span id="no_new_attachments_notice" class="text-xs text-slate-400 italic">لا توجد مرفقات جديدة قيد الإضافة.</span>
                </div>
            </div>

            <div id="scanner_hidden_inputs"></div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('guests.index') }}" class="btn btn-light px-4 py-2 text-sm font-bold">إلغاء</a>
            <button type="submit" class="btn btn-primary px-6 py-2.5 text-sm font-bold shadow-sm">
                💾 حفظ التعديلات والوثائق
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // حذف وتراجع المرفقات السابقة
    const deletedContainer = document.getElementById('deleted_attachments_inputs_container');
    document.querySelectorAll('.guest-att-item').forEach(function(item) {
        const path = item.getAttribute('data-att-path');
        const btnDel = item.querySelector('.btn-delete-att');
        const btnUndo = item.querySelector('.btn-undo-att');
        const overlay = item.querySelector('.deleted-overlay');

        if (btnDel) {
            btnDel.addEventListener('click', function() {
                overlay.classList.remove('hidden');
                let input = deletedContainer.querySelector('input[value="' + path + '"]');
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'deleted_attachments[]';
                    input.value = path;
                    deletedContainer.appendChild(input);
                }
            });
        }

        if (btnUndo) {
            btnUndo.addEventListener('click', function() {
                overlay.classList.add('hidden');
                const input = deletedContainer.querySelector('input[value="' + path + '"]');
                if (input) input.remove();
            });
        }
    });

    // سكنر مباشر
    const btnToggleScanner = document.getElementById('btn_toggle_scanner');
    const btnCloseScanner = document.getElementById('btn_close_scanner');
    const scannerSection = document.getElementById('scanner_section');
    const video = document.getElementById('scanner_video');
    const canvas = document.getElementById('scanner_canvas');
    const btnCapture = document.getElementById('btn_capture_doc');
    const previewGrid = document.getElementById('attachments_preview_grid');
    const hiddenInputsContainer = document.getElementById('scanner_hidden_inputs');
    const noNewNotice = document.getElementById('no_new_attachments_notice');
    const fileInput = document.getElementById('file_attachments_input');

    let stream = null;
    const deviceSelector = document.getElementById('scanner_device_selector');
    const btnRefreshDevices = document.getElementById('btn_refresh_devices');

    async function loadDevices() {
        if (!navigator.mediaDevices?.enumerateDevices) return;
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevs = devices.filter(d => d.kind === 'videoinput');
            if (videoDevs.length > 0 && deviceSelector) {
                const currentVal = deviceSelector.value;
                deviceSelector.innerHTML = '';
                videoDevs.forEach((dev, idx) => {
                    const opt = document.createElement('option');
                    opt.value = dev.deviceId;
                    opt.textContent = (dev.label || `جهاز مسح ضوئي / كاميرا رقم ${idx + 1}`);
                    if (dev.deviceId === currentVal) opt.selected = true;
                    deviceSelector.appendChild(opt);
                });
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function startScanner(deviceId = null) {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }

        try {
            const constraints = {
                video: deviceId 
                    ? { deviceId: { exact: deviceId }, width: { ideal: 1920 }, height: { ideal: 1080 } }
                    : { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } },
                audio: false
            };
            stream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = stream;
            scannerSection.classList.remove('hidden');
            await loadDevices();
        } catch (err) {
            alert('تعذر فتح الكاميرا أو السكنر: ' + err.message);
        }
    }

    function stopScanner() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        scannerSection.classList.add('hidden');
    }

    deviceSelector?.addEventListener('change', function() {
        if (this.value) {
            startScanner(this.value);
        }
    });

    btnRefreshDevices?.addEventListener('click', loadDevices);

    btnToggleScanner?.addEventListener('click', function() {
        if (scannerSection.classList.contains('hidden')) {
            startScanner(deviceSelector?.value || null);
        } else {
            stopScanner();
        }
    });

    btnCloseScanner?.addEventListener('click', stopScanner);

    btnCapture?.addEventListener('click', function() {
        if (!video.videoWidth) return;
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);

        if (noNewNotice) noNewNotice.style.display = 'none';

        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'scanner_images[]';
        hiddenInput.value = dataUrl;
        hiddenInputsContainer.appendChild(hiddenInput);

        const card = document.createElement('div');
        card.className = 'relative w-28 h-24 rounded-xl overflow-hidden border-2 border-emerald-500 shadow-sm group';
        card.innerHTML = `
            <img src="${dataUrl}" class="w-full h-full object-cover">
            <span class="absolute bottom-0 inset-x-0 bg-emerald-600 text-white text-[9px] font-bold text-center py-0.5">سكنر مباشر</span>
            <button type="button" class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold shadow" title="إزالة">×</button>
        `;

        card.querySelector('button').addEventListener('click', function() {
            card.remove();
            hiddenInput.remove();
        });

        previewGrid.appendChild(card);
    });

    fileInput?.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            if (noNewNotice) noNewNotice.style.display = 'none';

            Array.from(this.files).forEach(file => {
                const card = document.createElement('div');
                card.className = 'relative w-28 h-24 rounded-xl overflow-hidden border border-slate-300 shadow-sm bg-slate-100 flex flex-col items-center justify-center p-1 text-center';

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        card.innerHTML = `
                            <img src="${e.target.result}" class="w-full h-full object-cover rounded-lg">
                            <span class="absolute bottom-0 inset-x-0 bg-slate-900/70 text-white text-[9px] truncate px-1 py-0.5">${file.name}</span>
                        `;
                    };
                    reader.readAsDataURL(file);
                } else {
                    card.innerHTML = `
                        <span class="text-2xl">📄</span>
                        <span class="text-[10px] text-slate-700 font-bold truncate max-w-full px-1">${file.name}</span>
                    `;
                }
                previewGrid.appendChild(card);
            });
        }
    });
});
</script>
@endsection
