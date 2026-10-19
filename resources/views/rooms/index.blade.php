@extends('layouts.app')

@section('content')


    <h2 class="text-3xl font-bold mb-6">Room Status Dashboard</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">

        @foreach($rooms as $room)
            @php
                $color = match($room->status) {
                    'available' => 'bg-green-100 text-green-700',
                    'booked' => 'bg-blue-100 text-blue-700',
                    'cleaning' => 'bg-yellow-100 text-yellow-700',
                    'maintenance' => 'bg-red-100 text-red-700',
                    default => 'bg-gray-100 text-gray-700'
                };
            @endphp

            <div class="p-4 shadow rounded bg-white">
                <h3 class="text-xl font-bold mb-2">{{ $room->name }}</h3>

                <span class="px-3 py-1 rounded {{ $color }}">
                    {{ ucfirst($room->status) }}
                </span>
                   @if($room->needs_cleaning)
                    <div class="mt-2 text-yellow-600 font-semibold">
                        Needs Cleaning
                    </div>
                @endif

                @if($room->maintenance_notes)
                    <div class="mt-2 text-red-600">
                        {{ $room->maintenance_notes }}
                    </div>
                @endif

                <div class="mt-4">
                    <a href="{{ route('rooms.show', $room->id) }}"
                       class="px-3 py-2 bg-gray-800 text-white rounded hover:bg-black">
                       Manage
                    </a>
                </div>
            </div>
        @endforeach

    </div>

</div>



<div class="bg-white shadow-md rounded-lg p-6">

    <!-- العنوان + زر إضافة غرفة -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-700">قائمة الغرف</h2>

        <a href="{{ route('rooms.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg shadow">
            + إضافة غرفة جديدة
        </a>
    </div>

    <!-- جدول الغرف -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg">
            <thead>
                <tr class="bg-gray-100 text-gray-700">
                    <th class="py-3 px-4 border">الصورة</th>
                    <th class="py-3 px-4 border">رقم الغرفة</th>
                    <th class="py-3 px-4 border">نوع الغرفة</th>
                    <th class="py-3 px-4 border">السعر الليلي</th>
                    <th class="py-3 px-4 border">الحالة</th>
                    <th class="py-3 px-4 border">التحكم</th>
                </tr>
            </thead>

            <tbody>
                @foreach($rooms as $room)
                <tr class="text-center border-b hover:bg-gray-50">

                    <!-- صورة الغرفة -->
                    <td class="py-3 px-4 border">
                        @if($room->image)
                            <img src="{{ asset('room_images/' . $room->image) }}"
                                 class="w-20 h-16 object-cover rounded-md shadow">
                        @else
                            <span class="text-gray-500">لا توجد صورة</span>
                        @endif
                    </td>

                    <!-- رقم الغرفة -->
                    <td class="py-3 px-4 border font-semibold">
                        {{ $room->room_number }}
                    </td>

                    <!-- نوع الغرفة -->
                    <td class="py-3 px-4 border">
                        {{ $room->type->name }}
                    </td>

                    <!-- السعر -->
                    <td class="py-3 px-4 border text-blue-600 font-bold">
                        {{ number_format($room->price, 2) }} ريال
                    </td>

                    <!-- الحالة -->
                    <td class="py-3 px-4 border">
                        @if($room->status == 'available')
                            <span class="text-green-600 font-semibold">متاحة</span>
                        @elseif($room->status == 'reserved')
                            <span class="text-orange-500 font-semibold">محجوزة</span>
                        @else
                            <span class="text-red-600 font-semibold">صيانة</span>
                        @endif
                    </td>

                    <!-- التحكم -->
                    <td class="py-3 px-4 border flex justify-center gap-3">

                        <!-- زر تعديل -->
                        <a href="{{ route('rooms.edit', $room->id) }}"
                           class="text-blue-600 hover:text-blue-800 font-semibold">
                            تعديل
                        </a>

                        <!-- زر حذف -->
                        <form action="{{ route('rooms.destroy', $room->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button onclick="return confirm('هل أنت متأكد من حذف هذه الغرفة؟');"
                                    class="text-red-600 hover:text-red-800 font-semibold">
                                حذف
                            </button>
                        </form>

                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

@endsection
