@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl py-6 px-4">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-blue-600 block mb-1">الحساب الشخصي</span>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900">الملف الشخصي وكلمة المرور</h1>
            <p class="text-sm text-slate-500 mt-1">تعديل بياناتك الشخصية، الصورة الرمزية، وتأمين حسابك بكلمة مرور جديدة</p>
        </div>
        <a href="{{ route('dashboard.redirect') }}" class="btn btn-secondary btn-sm font-medium">العودة للوحة التحكم</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Personal Info Card --}}
        <div class="card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <h2 class="text-lg font-extrabold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>👤</span>
                <span>المعلومات الشخصية والمهنية</span>
            </h2>

            <div class="flex flex-col sm:flex-row items-center gap-6 mb-6 p-4 rounded-xl bg-slate-50 border border-slate-100">
                <div class="relative">
                    <img id="avatar_preview" src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md bg-white">
                </div>
                <div class="flex-1 text-center sm:text-right">
                    <span class="block text-sm font-bold text-slate-800 mb-1">الصورة الشخصية</span>
                    <p class="text-xs text-slate-500 mb-3">تظهر صورتك في الشريط العلوي وجوار اسمك في سجل العمليات والرسائل</p>
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                        <label class="btn btn-sm btn-outline-primary cursor-pointer mb-0">
                            <span>📷 اختيار صورة جديدة</span>
                            <input type="file" name="avatar" id="avatar_input" accept="image/png,image/jpeg,image/webp" class="hidden">
                        </label>
                        @if($user->avatar)
                            <label class="flex items-center gap-1.5 text-xs text-red-600 font-bold cursor-pointer">
                                <input type="checkbox" name="remove_avatar" value="1" class="rounded text-red-600 focus:ring-red-500">
                                <span>حذف الصورة والرجوع للافتراضية</span>
                            </label>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">الاسم الكامل <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">البريد الإلكتروني (اسم المستخدم لتسجيل الدخول)</label>
                    <input type="email" value="{{ $user->email }}" disabled class="w-full rounded-xl border border-slate-200 bg-slate-100 p-2.5 text-sm text-slate-500 cursor-not-allowed">
                    <span class="text-[11px] text-slate-400 mt-0.5 block">البريد الإلكتروني مرتبط بتسجيل الدخول ولا يمكن تعديله ذاتياً.</span>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">رقم الهاتف / الجوال</label>
                    <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="09xxxxxxxx" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">المسمى الوظيفي</label>
                    <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}" placeholder="مثال: موظف استقبال، محاسب أول، مدير إشراف" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-slate-700 mb-1">صلاحيات الدور الوظيفي الحالي (Role)</label>
                    @php
                        $roleLabels = [
                            'super_admin' => 'مدير النظام العام (Super Admin)',
                            'hotel_admin' => 'مدير الفندق (Hotel Admin)',
                            'admin' => 'مدير الفندق (Admin)',
                            'reception' => 'موظف استقبال (Reception)',
                            'accountant' => 'محاسب مالي (Accountant)',
                            'maintenance' => 'مسؤول الصيانة (Maintenance)',
                            'cleaner' => 'خدمات الغرف والتنظيف (Housekeeping)',
                        ];
                    @endphp
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                        <span class="font-extrabold text-blue-700">{{ $roleLabels[$user->role] ?? $user->role }}</span>
                        <span class="text-slate-400">🔒 الصلاحيات والأدوار محددة وثابتة من قبل إدارة النظام</span>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-slate-700 mb-1">نبذة / معلومات إضافية عن صاحب الحساب</label>
                    <textarea name="bio" rows="3" placeholder="اكتب نبذة مختصرة عنك أو عن مهامك..." class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">{{ old('bio', $user->bio) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Change Password Card --}}
        <div class="card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <h2 class="text-lg font-extrabold text-slate-900 mb-2 pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>🔑</span>
                <span>تغيير كلمة المرور</span>
            </h2>
            <p class="text-xs text-slate-500 mb-4">اترك هذه الحقول فارغة إذا كنت لا ترغب بتغيير كلمة المرور الحالية.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">كلمة المرور الحالية</label>
                    <input type="password" name="current_password" autocomplete="current-password" placeholder="••••••••" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">كلمة المرور الجديدة</label>
                    <input type="password" name="password" autocomplete="new-password" placeholder="8 أحرف على الأقل" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">تأكيد كلمة المرور الجديدة</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="أعد كتابة كلمة المرور" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('dashboard.redirect') }}" class="btn btn-light px-4 py-2 text-sm font-bold">إلغاء</a>
            <button type="submit" class="btn btn-primary px-6 py-2.5 text-sm font-bold shadow-sm">
                💾 حفظ التعديلات
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('avatar_input')?.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar_preview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endsection

