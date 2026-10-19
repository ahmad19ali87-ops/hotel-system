@extends('layouts.app')

@section('content')

<div class="bg-white shadow-md rounded-lg p-6 max-w-xl mx-auto">

    <h2 class="text-2xl font-bold text-gray-700 mb-6">إضافة غرفة جديدة</h2>

    <!-- عرض الأخطاء -->
    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            <ul class="list-disc pr-5">
                @foreach ($errors->all() as $error)
                    <li class="text-sm">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('rooms.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <!-- رقم الغرفة -->
        <div>
            <label class="block mb-1 font-semibold text-gray-700">رقم الغرفة:</label>
            <input type="text" name="room_number" value="{{ old('room_number') }}"
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- نوع الغرفة -->
        <div>
            <label class="block mb-1 font-semibold text-gray-700">نوع الغرفة:</label>
            <select name="room_type_id"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">اختر النوع</option>
                @foreach($types as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- السعر بالعملتين (منفصلان وغير مرتبطان بسعر الصرف) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block mb-1 font-semibold text-gray-700">السعر الليلي (بالليرة السورية ل.س):</label>
                <div class="relative">
                    <input type="number" step="0.01" name="price" value="{{ old('price') }}" required placeholder="مثال: 350000"
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
                    <span class="absolute left-3 top-2.5 text-xs text-gray-500">ل.س</span>
                </div>
            </div>

            <div>
                <label class="block mb-1 font-semibold text-gray-700">السعر الليلي (بالدولار الأمريكي $):</label>
                <div class="relative">
                    <input type="number" step="0.01" name="price_usd" value="{{ old('price_usd', 0) }}" placeholder="مثال: 50"
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 p-2 border">
                    <span class="absolute left-3 top-2.5 text-xs text-gray-500">$ USD</span>
                </div>
                <small class="text-xs text-gray-500">سعر مستقل مخصص للمحاسبة بالدولار</small>
            </div>
        </div>

        <!-- الحالة -->
        <div>
            <label class="block mb-1 font-semibold text-gray-700">الحالة:</label>
            <select name="status"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="available">متاحة</option>
                <option value="reserved">محجوزة</option>
                <option value="maintenance">صيانة</option>
            </select>
        </div>

        <!-- صورة الغرفة -->
        <div>
            <label class="block mb-1 font-semibold text-gray-700">صورة الغرفة:</label>
            <input type="file" name="image"
                   class="w-full border-gray-300 rounded-lg shadow-sm bg-white">
        </div>

        <!-- زر الحفظ -->
        <div class="text-center">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-6 rounded-lg shadow">
                حفظ الغرفة
            </button>
        </div>

    </form>

</div>

@endsection
