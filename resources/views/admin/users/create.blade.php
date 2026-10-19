@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-xl py-8">
    <h1 class="text-2xl font-bold">إضافة موظف للفندق</h1>
    @if($errors->any())
        <div class="alert alert-danger mt-4">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('employees.store') }}" class="mt-5 space-y-4">
        @csrf
        <label class="block">الاسم
            <input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded border p-2">
        </label>
        <label class="block">البريد الإلكتروني
            <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded border p-2">
        </label>
        <label class="block">الدور الوظيفي
            <select name="role" required class="mt-1 w-full rounded border p-2">
                <option value="">اختر الدور</option>
                @foreach($roles as $role)
                    @php
                        $roleLabels = [
                            'hotel_owner' => 'مالك الفندق (اطلاع وقراءة كاملة فقط)',
                            'reception' => 'استقبال',
                            'accountant' => 'محاسبة',
                            'maintenance' => 'صيانة',
                            'cleaner' => 'تنظيف',
                        ];
                    @endphp
                    <option value="{{ $role }}" @selected(old('role') === $role)>{{ $roleLabels[$role] ?? $role }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">كلمة المرور
            <input type="password" name="password" minlength="8" required class="mt-1 w-full rounded border p-2">
        </label>
        <label class="block">تأكيد كلمة المرور
            <input type="password" name="password_confirmation" minlength="8" required class="mt-1 w-full rounded border p-2">
        </label>

        {{-- صلاحيات إضافية اختيارية --}}
        <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-4 mt-4">
            <h3 class="text-sm font-bold text-blue-900 mb-2 flex items-center gap-1.5">
                <span>🛡️</span>
                <span>صلاحيات وميزات إضافية للموظف (اختياري)</span>
            </h3>
            <p class="text-xs text-slate-600 mb-3">
                يمكنك هنا منح هذا الموظف ميزات إضافية بجانب دوره الأساسي (مثال: منح موظف الاستقبال صلاحيات المحاسبة أو الصيانة).
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                @foreach($availablePermissions as $permKey => $permLabel)
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="{{ $permKey }}" @checked(in_array($permKey, old('permissions', []))) class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="font-medium text-slate-800">{{ $permLabel }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <button class="rounded-xl bg-blue-600 hover:bg-blue-700 px-5 py-2.5 font-bold text-white shadow">إنشاء الحساب وحفظ الصلاحيات</button>
    </form>
</div>
@endsection