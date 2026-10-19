@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl py-6 px-4">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-blue-600 block mb-1">لوحة إدارة البيانات والأمان</span>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900">النسخ الاحتياطي والاستعادة (Backup & Restore)</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                تصدير وتحميل كافة بيانات الفندق في ملف مؤمّن واستعادتها في أي وقت: <strong>{{ $hotel->name }}</strong>
            </p>
        </div>
        <div>
            @if($isPlatform)
                <a href="{{ route('platform.hotels.index') }}" class="btn btn-secondary btn-sm">العودة لقائمة الفنادق</a>
            @else
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary btn-sm font-medium">العودة للوحة الفندق</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <span class="font-bold">✓ نجاح:</span> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <span class="font-bold">✕ خطأ:</span> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Live Hotel Data Counts Overview --}}
    <div class="card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl mb-6">
        <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
            <span>📊</span>
            <span>ملخص البيانات الحالية المتوفرة للنسخ في فندق {{ $hotel->name }}</span>
        </h2>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3 text-center">
            <div class="p-3 rounded-xl bg-blue-50 border border-blue-100">
                <span class="text-2xl font-black text-blue-700 block">{{ $counts['rooms'] }}</span>
                <span class="text-xs font-bold text-slate-600">الغرف</span>
            </div>
            <div class="p-3 rounded-xl bg-indigo-50 border border-indigo-100">
                <span class="text-2xl font-black text-indigo-700 block">{{ $counts['room_types'] }}</span>
                <span class="text-xs font-bold text-slate-600">أنواع الغرف</span>
            </div>
            <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-100">
                <span class="text-2xl font-black text-emerald-700 block">{{ $counts['guests'] }}</span>
                <span class="text-xs font-bold text-slate-600">النزلاء والعملاء</span>
            </div>
            <div class="p-3 rounded-xl bg-amber-50 border border-amber-100">
                <span class="text-2xl font-black text-amber-700 block">{{ $counts['bookings'] }}</span>
                <span class="text-xs font-bold text-slate-600">الحجوزات</span>
            </div>
            <div class="p-3 rounded-xl bg-purple-50 border border-purple-100">
                <span class="text-2xl font-black text-purple-700 block">{{ $counts['invoices'] }}</span>
                <span class="text-xs font-bold text-slate-600">الفواتير</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-100 border border-slate-200">
                <span class="text-2xl font-black text-slate-800 block">{{ $counts['accounts'] }}</span>
                <span class="text-xs font-bold text-slate-600">القيود المحاسبية</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Section 1: Export & Download Backup --}}
        <div class="card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center text-2xl mb-4 font-black">
                    📥
                </div>
                <h3 class="text-lg font-black text-slate-900 mb-2">تحميل وتصدير نسخة احتياطية</h3>
                <p class="text-xs text-slate-500 leading-relaxed mb-4">
                    يقوم النظام بحزم كافة بيانات الفندق من غرف، حجوزات، نزلاء، وثائق، فواتير، وحسابات مالية في ملف JSON مؤمّن يمكنك حفظه على جهازك أو نقله.
                </p>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs text-slate-600 mb-6 space-y-1">
                    <div>✓ يشمل جميع الحجوزات والفواتير والدفعات</div>
                    <div>✓ يشمل حسابات وأسماء وسجلات النزلاء والوثائق</div>
                    <div>✓ ملف رقمي بصيغة JSON متوافق مع نظام الاستعادة</div>
                </div>
            </div>

            @php
                $downloadUrl = $isPlatform 
                    ? route('platform.hotels.backup.download', $hotel) 
                    : route('admin.backup.download');
            @endphp
            <a href="{{ $downloadUrl }}" class="btn btn-primary py-3 px-6 text-sm font-bold shadow-md flex items-center justify-center gap-2">
                <span>⬇️</span>
                <span>تحميل النسخة الاحتياطية الآن</span>
            </a>
        </div>

        {{-- Section 2: Restore Backup --}}
        <div class="card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl mb-4 font-black">
                📤
            </div>
            <h3 class="text-lg font-black text-slate-900 mb-2">استعادة بيانات من نسخة سابقة</h3>
            <p class="text-xs text-slate-500 leading-relaxed mb-4">
                اختر ملف النسخة الاحتياطية (.json) الذي قمت بتحميله مسبقاً لاسترجاع كافة البيانات والعمليات.
            </p>

            @php
                $restoreUrl = $isPlatform 
                    ? route('platform.hotels.backup.restore', $hotel) 
                    : route('admin.backup.restore');
            @endphp
            <form method="POST" action="{{ $restoreUrl }}" enctype="multipart/form-data" class="space-y-4" onsubmit="return confirm('تأكيد الاستعادة: هل ترغب بمتابعة عملية استيراد واستعادة البيانات؟');">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">ملف النسخة الاحتياطية (.json) <span class="text-red-500">*</span></label>
                    <input type="file" name="backup_file" required accept=".json,application/json,text/plain" class="w-full rounded-xl border border-slate-300 p-2 text-xs bg-slate-50 focus:bg-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">طريقة الاستعادة:</label>
                    <div class="space-y-2 text-xs">
                        <label class="flex items-start gap-2 p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                            <input type="radio" name="restore_mode" value="merge" checked class="text-blue-600 focus:ring-blue-500 mt-0.5">
                            <div>
                                <strong class="text-slate-800 block">دمج وتحديث (Merge & Update) - مستحسن</strong>
                                <span class="text-slate-500">يحافظ على البيانات المسجلة حديثاً ويقوم بإضافة أو تحديث السجلات الواردة بالنسخة.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-2 p-2.5 rounded-xl border border-red-200 bg-red-50/50 hover:bg-red-50 cursor-pointer">
                            <input type="radio" name="restore_mode" value="replace" class="text-red-600 focus:ring-red-500 mt-0.5">
                            <div>
                                <strong class="text-red-800 block">استبدال كامل (Full Replace)</strong>
                                <span class="text-red-600/80">يمسح بيانات الفندق الحالية ويستبدلها بسجلات النسخة المرفوعة فقط.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-2">
                    @if(auth()->user()->role !== 'hotel_owner')
                        <button type="submit" class="btn btn-outline-success w-full py-3 px-6 text-sm font-bold shadow-sm flex items-center justify-center gap-2">
                            <span>♻️</span>
                            <span>بدء استعادة النسخة الاحتياطية</span>
                        </button>
                    @else
                        <div class="p-3 bg-amber-50 text-amber-900 border border-amber-200 rounded-xl text-center text-xs font-bold">
                            👑 استعادة البيانات تتطلب صلاحيات المدير التنفيذي. حساب المالك للاطلاع وتنزيل النسخ فقط.
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

