@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-10">

    <h1 class="text-3xl font-bold mb-6">إضافة فندق جديد</h1>

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('platform.hotels.store') }}" enctype="multipart/form-data">
        @csrf

        <label>اسم الفندق</label>
        <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="border p-2 w-full mb-3">

        <label>الرابط (اختياري)</label>
        <input type="text" name="slug" value="{{ old('slug') }}" pattern="[A-Za-z0-9_-]+" class="border p-2 w-full mb-3">

        <label>المحافظة</label>
        <select name="province" required class="border p-2 w-full mb-3">
            <option value="">اختر المحافظة</option>
            @foreach($provinces as $province)
                <option value="{{ $province }}" @selected(old('province') === $province)>{{ $province }}</option>
            @endforeach
        </select>

        <label>شعار الفندق</label>
        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="border p-2 w-full mb-3">

        <label>صور الفندق</label>
        <input type="file" name="images[]" accept="image/png,image/jpeg,image/webp" multiple class="border p-2 w-full mb-3">

        <label>عدد النجوم</label>
        <select name="stars" required class="border p-2 w-full mb-3">
            @for($stars = 1; $stars <= 5; $stars++)
                <option value="{{ $stars }}" @selected(old('stars', 3) == $stars)>{{ $stars }} نجوم</option>
            @endfor
        </select>

        <label>البريد الإلكتروني</label>
        <input type="email" name="email" value="{{ old('email') }}" class="border p-2 w-full mb-3">

        <label>أرقام الهواتف</label>
        @for($index = 0; $index < 3; $index++)
            <input type="tel" name="phones[]" value="{{ old('phones.' . $index) }}" class="border p-2 w-full mb-2" placeholder="رقم هاتف {{ $index + 1 }}">
        @endfor

        <label>العنوان</label>
        <input type="text" name="address" value="{{ old('address') }}" class="border p-2 w-full mb-3" placeholder="العنوان التفصيلي (الشارع، الحي، المعلم القريب)">

        <label>موقع الفندق على خرائط جوجل (Google Maps URL)</label>
        <input type="url" name="google_maps_url" value="{{ old('google_maps_url') }}" class="border p-2 w-full mb-3" placeholder="https://maps.google.com/?q=... أو رابط الموقع من تطبيق الخرائط">

        <label>الوصف</label>
        <textarea name="description" class="border p-2 w-full mb-3">{{ old('description') }}</textarea>

        <h2 class="mt-6 mb-3 text-xl font-bold">حساب مدير الفندق</h2>
        <label>اسم المدير</label>
        <input type="text" name="manager_name" value="{{ old('manager_name') }}" required class="border p-2 w-full mb-3">

        <label>البريد الإلكتروني للمدير</label>
        <input type="email" name="manager_email" value="{{ old('manager_email') }}" required class="border p-2 w-full mb-3">

        <label>كلمة المرور (8 أحرف على الأقل)</label>
        <input type="password" name="manager_password" required class="border p-2 w-full mb-3">

        <label>تأكيد كلمة المرور</label>
        <input type="password" name="manager_password_confirmation" required class="border p-2 w-full mb-3">

        <button class="bg-blue-600 text-white px-4 py-2 rounded">
            حفظ الفندق
        </button>
    </form>

</div>
@endsection
