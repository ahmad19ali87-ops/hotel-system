@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto">

    <h2 class="text-3xl font-bold mb-6">Daily Snapshot - {{ $today->format('Y-m-d') }}</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">

        <div class="p-4 bg-blue-100 text-blue-700 rounded shadow">
            <h3 class="text-xl font-bold">Revenue Today</h3>
            <p class="text-3xl font-bold">{{ number_format($revenueToday, 2) }} SAR</p>
        </div>

        <div class="p-4 bg-green-100 text-green-700 rounded shadow">
            <h3 class="text-xl font-bold">Bookings Today</h3>
            <p class="text-3xl font-bold">{{ $bookingsToday }}</p>
        </div>

        <div class="p-4 bg-yellow-100 text-yellow-700 rounded shadow">
            <h3 class="text-xl font-bold">Occupancy</h3>
            <p class="text-3xl font-bold">{{ $occupancyRate }}%</p>
        </div>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="p-4 bg-purple-100 text-purple-700 rounded shadow">
            <h3 class="text-xl font-bold">Check-ins Today</h3>
            <p class="text-3xl font-bold">{{ $checkinsToday }}</p>
        </div>

        <div class="p-4 bg-red-100 text-red-700 rounded shadow">
            <h3 class="text-xl font-bold">Check-outs Today</h3>
            <p class="text-3xl font-bold">{{ $checkoutsToday }}</p>
        </div>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-8">

        <div class="p-4 bg-orange-100 text-orange-700 rounded shadow">
            <h3 class="text-xl font-bold">Rooms Under Maintenance</h3>
            <p class="text-3xl font-bold">{{ $maintenanceOpen }}</p>
        </div>

        <div class="p-4 bg-teal-100 text-teal-700 rounded shadow">
            <h3 class="text-xl font-bold">Rooms Needing Cleaning</h3>
            <p class="text-3xl font-bold">{{ $cleaningRooms }}</p>
        </div>

    </div>

</div>
@endsection
