@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl py-6 px-4">
    <div class="mb-6 flex items-center justify-between border-b border-slate-200 pb-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-blue-600 block mb-1">لوحة إدارة المنصة</span>
            <h1 class="text-2xl font-black text-slate-900">إضافة مدير مساعد جديد</h1>
            <p class="text-xs text-slate-500 mt-1">إنشاء حساب مدير منصة مساعد وتحديد الصلاحيات الممنوحة له بدقة</p>
        </div>
        <a href="{{ route('platform.admins.index') }}" class="btn btn-secondary btn-sm">العودة للمدراء</a>
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

    <div class="card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
        <form method="POST" action="{{ route('platform.admins.store') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">اسم المدير <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="مثال: حسام العلي" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">البريد الإلكتروني (لتسجيل الدخول) <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="admin2@example.com" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">رقم الهاتف / الجوال</label>
                <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="09xxxxxxxx" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">كلمة المرور <span class="text-red-500">*</span></label>
                    <input type="password" name="password" required placeholder="8 أحرف على الأقل" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">تأكيد كلمة المرور <span class="text-red-500">*</span></label>
                    <input type="password" name="password_confirmation" required placeholder="أعد إدخال كلمة المرور" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
            </div>

            {{-- Permissions Selection --}}
            <div class="pt-3 border-t border-slate-100">
                <span class="block text-sm font-bold text-slate-800 mb-1">الصلاحيات والمهام الممنوحة لهذا المدير:</span>
                <p class="text-xs text-slate-500 mb-3">اختر المهام المسموح لهذا المدير القيام بها داخل لوحة المنصة:</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($permissions as $permKey => $permLabel)
                        <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                            <input type="checkbox" name="permissions[]" value="{{ $permKey }}" @checked(is_array(old('permissions')) && in_array($permKey, old('permissions'))) class="rounded text-blue-600 focus:ring-blue-500 mt-0.5 h-4 w-4">
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">{{ $permLabel }}</span>
                                <span class="text-[11px] text-slate-400 block font-mono">perm: {{ $permKey }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <a href="{{ route('platform.admins.index') }}" class="btn btn-light px-4 py-2 text-sm font-bold">إلغاء</a>
                <button type="submit" class="btn btn-primary px-6 py-2.5 text-sm font-bold shadow-sm">
                    💾 حفظ وإنشاء الحساب
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

