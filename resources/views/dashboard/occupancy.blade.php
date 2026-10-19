@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">

    <h2 class="text-3xl font-bold mb-6">Hotel Occupancy Dashboard</h2>

    {{-- الإحصائيات --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">

        <div class="p-4 bg-green-100 text-green-700 rounded shadow">
            <h3 class="text-xl font-bold">Available</h3>
            <p class="text-3xl font-bold">{{ $available }}</p>
        </div>

        <div class="p-4 bg-blue-100 text-blue-700 rounded shadow">
            <h3 class="text-xl font-bold">Booked</h3>
            <p class="text-3xl font-bold">{{ $booked }}</p>
        </div>

        <div class="p-4 bg-yellow-100 text-yellow-700 rounded shadow">
            <h3 class="text-xl font-bold">Cleaning</h3>
            <p class="text-3xl font-bold">{{ $cleaning }}</p>
        </div>

        <div class="p-4 bg-red-100 text-red-700 rounded shadow">
            <h3 class="text-xl font-bold">Maintenance</h3>
            <p class="text-3xl font-bold">{{ $maintenance }}</p>
        </div>

    </div>

    {{-- نسبة الإشغال --}}
    <div class="mb-8 p-6 bg-gray-100 rounded shadow">
        <h3 class="text-xl font-bold mb-2">Occupancy Rate</h3>

        <div class="w-full bg-gray-300 rounded h-6">
            <div class="bg-blue-600 h-6 rounded" style="width: {{ $occupancyRate }}%;"></div>
        </div>

        <p class="mt-2 text-lg font-semibold">{{ $occupancyRate }}%</p>
    </div>

    {{-- قائمة الغرف --}}
    <h3 class="text-2xl font-bold mb-4">Rooms Overview</h3>

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

            <div class="p-4 bg-white shadow rounded">
                <h3 class="text-xl font-bold mb-2">{{ $room->name }}</h3>

                <span class="px-3 py-1 rounded {{ $color }}">
                    {{ ucfirst($room->status) }}
                </span>

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
@endsection
