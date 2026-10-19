@extends('layouts.app')

@section('content')
<div class="row justify-content-center my-4">
    <div class="col-12 col-lg-9">
        <div class="card border-0 shadow-xl rounded-3xl overflow-hidden bg-white dark:bg-slate-800">
            <div class="card-header bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-900 text-white p-4 sm:p-5 border-0">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <img src="{{ $user->avatar_url }}" alt="avatar" class="w-14 h-14 rounded-2xl object-cover border-2 border-white/40 shadow bg-white/20">
                        <div>
                            <span class="badge bg-white/20 text-white text-[11px] mb-1">بيانات النزيل المعتمدة</span>
                            <h3 class="font-black text-xl sm:text-2xl mb-0 text-white">{{ $user->full_name }}</h3>
                            <span class="text-xs text-blue-100 block">{{ $user->email }}</span>
                        </div>
                    </div>
                    <span class="badge bg-emerald-400/20 text-emerald-200 border border-emerald-400/30 text-xs px-3 py-1.5 rounded-xl">
                        {{ $user->is_verified ? '✅ حساب موثق ومفعل' : '⚠️ قيد التوثيق' }}
                    </span>
                </div>
            </div>

            <div class="card-body p-4 sm:p-5">
                <div class="p-3.5 rounded-2xl bg-blue-50 dark:bg-slate-900/60 border border-blue-200 dark:border-slate-700 text-xs text-blue-900 dark:text-blue-200 mb-4 d-flex align-items-center gap-2.5">
                    <span class="text-xl">🔗</span>
                    <div>
                        <strong>الربط المباشر مع سجلات الفنادق:</strong> هذه البيانات ووثائق الهوية والجواز المرفوعة تُربط تلقائياً بحجوزاتك وسجلات النزلاء في الفندق. وأي تحديث يجريه قسم الاستقبال بالفندق سيظهر هنا وفي سجل النزيل مباشرة.
                    </div>
                </div>

                <form action="{{ route('guest.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @if($errors->any())
                        <div class="alert alert-danger rounded-2xl text-xs font-bold mb-4">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    {{-- 1. Personal & Contact Information --}}
                    <h5 class="font-black text-sm uppercase text-slate-700 dark:text-slate-300 tracking-wider mb-3 pb-2 border-b border-slate-100 dark:border-slate-700 flex items-center gap-2">
                        <span>👤</span> <span>البيانات الشخصية وبيانات الاتصال</span>
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">الاسم الأول <span class="text-rose-500">*</span></label>
                            <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" class="form-control rounded-xl text-sm py-2.5" placeholder="أحمد">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">اسم الأب <span class="text-rose-500">*</span></label>
                            <input type="text" name="father_name" value="{{ old('father_name', $user->father_name) }}" class="form-control rounded-xl text-sm py-2.5" placeholder="محمد">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">النسبة / الكنية <span class="text-rose-500">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="form-control rounded-xl text-sm py-2.5" placeholder="الحلبي">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">الاسم الكامل للعرض</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control rounded-xl text-sm py-2.5" placeholder="أحمد محمد الحلبي">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">رقم الهاتف / الجوال <span class="text-rose-500">*</span></label>
                            <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control rounded-xl text-sm py-2.5" placeholder="+963 9XXXXXXXX">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">الجنسية</label>
                            <input type="text" name="nationality" value="{{ old('nationality', $user->nationality ?? 'سوري') }}" class="form-control rounded-xl text-sm py-2.5" placeholder="سوري">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">تاريخ الميلاد</label>
                            <input type="date" name="birth_date" value="{{ old('birth_date', $user->birth_date?->format('Y-m-d') ?? ($user->birth_date ?: '')) }}" class="form-control rounded-xl text-sm py-2.5">
                        </div>
                        <div class="col-12">
                            <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">عنوان السكن / الإقامة الدائم</label>
                            <input type="text" name="address" value="{{ old('address', $user->address) }}" class="form-control rounded-xl text-sm py-2.5" placeholder="المدينة، الحي، الشارع...">
                        </div>
                    </div>

                    {{-- 2. Official Identity & Documents --}}
                    <h5 class="font-black text-sm uppercase text-slate-700 dark:text-slate-300 tracking-wider mb-3 pb-2 border-b border-slate-100 dark:border-slate-700 flex items-center gap-2">
                        <span>🪪</span> <span>الوثائق الثبوتية وصور الهوية / الجواز</span>
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">نوع الوثيقة الرسمية</label>
                            <select name="id_type" class="form-select rounded-xl text-sm py-2.5">
                                <option value="national_id" @selected(old('id_type', $user->id_type) === 'national_id')>بطاقة شخصية / هوية وطنية</option>
                                <option value="passport" @selected(old('id_type', $user->id_type) === 'passport')>جواز سفر دولي</option>
                                <option value="residence" @selected(old('id_type', $user->id_type) === 'residence')>بطاقة إقامة</option>
                                <option value="driver_license" @selected(old('id_type', $user->id_type) === 'driver_license')>شهادة قيادة</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">رقم الوثيقة / الجواز / الرقم الوطني</label>
                            <input type="text" name="id_number" value="{{ old('id_number', $user->id_number ?: $user->national_id) }}" class="form-control rounded-xl text-sm py-2.5" placeholder="0101XXXXXXXX">
                        </div>

                        {{-- Existing Uploaded Document Scans --}}
                        <div class="col-12">
                            <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300 mb-2">الوثائق والمستندات المحفوظة حالياً</label>
                            @php $attachments = $user->attachments_list; @endphp
                            @if(!empty($attachments))
                                <div class="row g-2 mb-3">
                                    @foreach($attachments as $att)
                                        <div class="col-6 col-sm-4 col-md-3">
                                            <div class="p-2 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 position-relative group text-center">
                                                <a href="{{ $att['url'] }}" target="_blank" class="block overflow-hidden rounded-xl h-24 mb-1">
                                                    @if(in_array(strtolower($att['type']), ['jpg', 'jpeg', 'png', 'webp']))
                                                        <img src="{{ $att['url'] }}" class="w-full h-full object-cover group-hover:scale-105 transition">
                                                    @else
                                                        <div class="w-full h-full bg-slate-200 dark:bg-slate-800 d-flex align-items-center justify-content-center text-xs font-bold text-slate-700">
                                                            📄 {{ strtoupper($att['type']) }}
                                                        </div>
                                                    @endif
                                                </a>
                                                <div class="text-[11px] truncate text-slate-500 font-semibold">{{ $att['name'] }}</div>
                                                <label class="d-flex align-items-center justify-content-center gap-1 text-[10px] text-rose-600 mt-1 cursor-pointer">
                                                    <input type="checkbox" name="deleted_attachments[]" value="{{ $att['path'] }}" class="rounded text-rose-600">
                                                    <span>حذف الوثيقة</span>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-xs text-slate-400 mb-3">
                                    لم يتم رفع صور لوثيقة الهوية أو الجواز بعد. ارفع الصور أدناه لتسريع إجراءات التسكين وتسجيل الدخول في الفندق.
                                </div>
                            @endif

                            {{-- Upload New Document Scans --}}
                            <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">رفع صور وثيقة جديدة (بطاقة شخصية أو جواز سفر)</label>
                            <input type="file" name="attachments[]" multiple class="form-control rounded-xl text-sm py-2" accept="image/*,.pdf">
                            <span class="text-[11px] text-slate-400 mt-1 block">يمكنك تحديد صور متعددة لوجهي الهوية أو صفحات الجواز (JPG, PNG, PDF) حتى 8MB لكل ملف.</span>
                        </div>

                        <div class="col-12 col-md-6 mt-3">
                            <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">تحديث الصورة الشخصية (Avatar)</label>
                            <input type="file" name="avatar" class="form-control rounded-xl text-sm py-2" accept="image/*">
                        </div>
                    </div>

                    <div class="pt-3 border-top border-slate-100 dark:border-slate-700 text-end">
                        <button type="submit" class="btn btn-primary py-2.5 px-5 rounded-2xl font-bold shadow-lg shadow-blue-600/25 text-sm">
                            💾 حفظ ومزامنة البيانات مع سجل النزلاء
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

