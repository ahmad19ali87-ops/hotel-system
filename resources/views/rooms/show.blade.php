@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto bg-white shadow p-6 rounded">

    <h2 class="text-2xl font-bold mb-4">Manage Room: {{ $room->name }}</h2>

    {{-- حالة الغرفة --}}
    <div class="mb-6">
        <h3 class="text-xl font-semibold mb-2">Room Status</h3>

        <form action="{{ route('rooms.updateStatus', $room->id) }}" method="POST">
            @csrf

            <select name="status" class="border p-2 rounded w-full">
                <option value="available" {{ $room->status == 'available' ? 'selected' : '' }}>Available</option>
                <option value="booked" {{ $room->status == 'booked' ? 'selected' : '' }}>Booked</option>
                <option value="cleaning" {{ $room->status == 'cleaning' ? 'selected' : '' }}>Cleaning</option>
                <option value="maintenance" {{ $room->status == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
            </select>

            <button class="mt-3 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Update Status
            </button>
        </form>
    </div>

    {{-- تنظيف الغرفة --}}
    <div class="mb-6">
        <h3 class="text-xl font-semibold mb-2">Cleaning</h3>

        <form action="{{ route('rooms.updateCleaning', $room->id) }}" method="POST">
            @csrf

            <label class="flex items-center gap-2">
                <input type="checkbox" name="needs_cleaning" value="1"
                       {{ $room->needs_cleaning ? 'checked' : '' }}>
                <span>Room needs cleaning</span>
            </label>

            <button class="mt-3 px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                Update Cleaning Status
            </button>
        </form>
    </div>
      {{-- الصيانة --}}
    <div class="mb-6">
        <h3 class="text-xl font-semibold mb-2">Maintenance</h3>

        <form action="{{ route('rooms.updateMaintenance', $room->id) }}" method="POST">
            @csrf

            <textarea name="maintenance_notes" class="border p-2 rounded w-full" rows="3"
                      placeholder="Maintenance notes...">{{ $room->maintenance_notes }}</textarea>

            <button class="mt-3 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Save Maintenance
            </button>
        </form>
    </div>

</div>
<div class="max-w-3xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden">
    <!-- صورة الغرفة -->
    @if($room->image)
        <img src="{{ asset('room_images/' . $room->image) }}" class="w-full h-64 object-cover">
    @else
        <div class="w-full h-64 bg-gray-200 flex items-center justify-center text-gray-500">
            لا توجد صورة متاحة
        </div>
    @endif

    <!-- تفاصيل الغرفة -->
    <div class="p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">تفاصيل الغرفة رقم {{ $room->room_number }}</h2>

        <div class="space-y-3 text-gray-700">
            <p><span class="font-semibold">نوع الغرفة:</span> {{ $room->type->name }}</p>
            <p><span class="font-semibold">الوصف:</span> {{ $room->type->description ?? '—' }}</p>
            <p><span class="font-semibold">السعر الليلي:</span> <span class="text-blue-600 font-bold">{{ number_format($room->price, 2) }} ريال</span></p>
            <p><span class="font-semibold">الحالة:</span>
                @if($room->status == 'available')
                    <span class="text-green-600 font-semibold">متاحة</span>
                @elseif($room->status == 'reserved')
                    <span class="text-orange-500 font-semibold">محجوزة</span>
                @else
                    <span class="text-red-600 font-semibold">صيانة</span>
                @endif
            </p>
        </div>

        <!-- أزرار التحكم -->
        <div class="mt-6 flex justify-between">
            <a href="{{ route('rooms.edit', $room->id) }}"
               class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-lg shadow">
                تعديل
            </a>

            <form action="{{ route('rooms.destroy', $room->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <button onclick="return confirm('هل أنت متأكد من حذف هذه الغرفة؟');"
                        class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg shadow">
                    حذف
                </button>
            </form>

            <a href="{{ route('rooms.index') }}"
               class="bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded-lg shadow">
                رجوع للقائمة
            </a>
        </div>
    </div>
</div>

@endsection
