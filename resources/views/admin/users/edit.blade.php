@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2 class="mb-4">تعديل بيانات الموظف</h2>

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('employees.update', $user->id) }}" method="POST">
        @csrf @method('PUT')

        <div class="mb-3">
            <label class="form-label">الاسم</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">البريد الإلكتروني</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">الدور الوظيفي</label>
            <select name="role" class="form-select" required>
                <option value="hotel_owner" {{ old('role', $user->role) == 'hotel_owner' ? 'selected' : '' }}>مالك الفندق (اطلاع وقراءة كاملة فقط)</option>
                <option value="reception" {{ old('role', $user->role) == 'reception' ? 'selected' : '' }}>استقبال</option>
                <option value="accountant" {{ old('role', $user->role) == 'accountant' ? 'selected' : '' }}>محاسب</option>
                <option value="maintenance" {{ old('role', $user->role) == 'maintenance' ? 'selected' : '' }}>صيانة</option>
                <option value="cleaner" {{ old('role', $user->role) == 'cleaner' ? 'selected' : '' }}>تنظيف</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">كلمة المرور الجديدة (اتركها فارغة إذا لا تريد تغييرها)</label>
            <input type="password" name="password" minlength="8" class="form-control" autocomplete="new-password">
        </div>

        {{-- صلاحيات إضافية اختيارية --}}
        <div class="card bg-light border-primary-subtle p-3 mb-4 rounded-3">
            <h5 class="fw-bold text-primary mb-1 d-flex align-items-center gap-2">
                <span>🛡️</span>
                <span>صلاحيات وميزات إضافية للموظف</span>
            </h5>
            <p class="text-muted small mb-3">
                يمكن لمدير الفندق منح هذا الموظف ميزات إضافية بجانب دوره الوظيفي الأساسي (مثل منح موظف الاستقبال صلاحيات المحاسبة أو الفواتير).
            </p>
            @php
                $userPerms = old('permissions', $user->permissions_list ?? []);
            @endphp
            <div class="row g-2">
                @foreach($availablePermissions as $permKey => $permLabel)
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-2.5 rounded-2 bg-white border cursor-pointer h-100">
                            <input type="checkbox" name="permissions[]" value="{{ $permKey }}" @checked(in_array($permKey, $userPerms)) class="form-check-input mt-0">
                            <span class="small fw-semibold text-dark">{{ $permLabel }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        <button class="btn btn-success w-100 py-2.5 fw-bold shadow-sm">حفظ التعديلات والصلاحيات</button>
    </form>

</div>
@endsection
