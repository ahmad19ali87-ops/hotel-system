@extends('layouts.app')

@section('content')
<form method="GET" class="mb-6 p-4 bg-gray-100 rounded shadow">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <div>
            <label class="font-semibold">From Date</label>
            <input type="date" name="from_date" class="border p-2 rounded w-full"
                   value="{{ request('from_date') }}">
        </div>

        <div>
            <label class="font-semibold">To Date</label>
            <input type="date" name="to_date" class="border p-2 rounded w-full"
                   value="{{ request('to_date') }}">
        </div>

        <div>
            <label class="font-semibold">Room</label>
            <select name="room_id" class="border p-2 rounded w-full">
                <option value="">All Rooms</option>
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                        {{ $room->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="font-semibold">Guest</label>
            <select name="guest_id" class="border p-2 rounded w-full">
                <option value="">All Guests</option>
                @foreach($guests as $guest)
                    <option value="{{ $guest->id }}" {{ request('guest_id') == $guest->id ? 'selected' : '' }}>
                        {{ $guest->name }}
                    </option>
                @endforeach
            </select>
        </div>

    </div>

    <button class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        Apply Filters
    </button>

</form>

<div class="max-w-4xl mx-auto">

    <h2 class="text-3xl font-bold mb-6">Revenue Report</h2>

    {{-- الأرقام --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">

        <div class="p-4 bg-blue-100 text-blue-700 rounded shadow">
            <h3 class="text-xl font-bold">Daily</h3>
            <p class="text-3xl font-bold">{{ number_format($daily, 2) }} SAR</p>
        </div>

        <div class="p-4 bg-green-100 text-green-700 rounded shadow">
            <h3 class="text-xl font-bold">Monthly</h3>
            <p class="text-3xl font-bold">{{ number_format($monthly, 2) }} SAR</p>
        </div>

        <div class="p-4 bg-yellow-100 text-yellow-700 rounded shadow">
            <h3 class="text-xl font-bold">Yearly</h3>
            <p class="text-3xl font-bold">{{ number_format($yearly, 2) }} SAR</p>
        </div>

    </div>

    {{-- الرسم البياني --}}
    <div class="bg-white p-6 rounded shadow">
        <h3 class="text-xl font-bold mb-4">Revenue Chart</h3>
        <canvas id="revenueChart"></canvas>
    </div>

</div>
<a href="{{ route('reports.revenue.pdf', request()->query()) }}"
   class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
    Export PDF
</a>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const ctx = document.getElementById('revenueChart');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Daily', 'Monthly', 'Yearly'],
        datasets: [{
            label: 'Revenue (SAR)',
            data: [
                {{ $daily }},
                {{ $monthly }},
                {{ $yearly }}
            ],
            borderWidth: 3,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.3)',
            tension: 0.4
        }]
    },
    options: {
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

@endsection
