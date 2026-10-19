@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">

    <h2 class="text-3xl font-bold mb-6">Unified Reports Dashboard</h2>

    {{-- فلاتر التاريخ --}}
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

        </div>

        <button class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Apply Filters
        </button>
    </form>

    {{-- الإحصائيات --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">

        <div class="p-4 bg-blue-100 text-blue-700 rounded shadow">
            <h3 class="text-xl font-bold">Revenue</h3>
            <p class="text-3xl font-bold">{{ number_format($revenue, 2) }} SAR</p>
        </div>

        <div class="p-4 bg-green-100 text-green-700 rounded shadow">
            <h3 class="text-xl font-bold">Occupancy</h3>
            <p class="text-3xl font-bold">{{ $occupancyRate }}%</p>
        </div>

        <div class="p-4 bg-yellow-100 text-yellow-700 rounded shadow">
            <h3 class="text-xl font-bold">Bookings</h3>
            <p class="text-3xl font-bold">{{ $totalBookings }}</p>
        </div>

    </div>

    {{-- الرسوم البيانية --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <div class="bg-white p-6 rounded shadow">
            <h3 class="text-xl font-bold mb-4">Maintenance Status</h3>
            <canvas id="maintenanceChart"></canvas>
        </div>

        <div class="bg-white p-6 rounded shadow">
            <h3 class="text-xl font-bold mb-4">Guests Overview</h3>
            <canvas id="guestsChart"></canvas>
        </div>

    </div>

</div>
<a href="{{ route('reports.unified.pdf', request()->query()) }}"
   class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 mb-6 inline-block">
    Export Unified PDF
</a>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
new Chart(document.getElementById('maintenanceChart'), {
    type: 'pie',
    data: {
        labels: ['Open', 'Closed'],
        datasets: [{
            data: [{{ $maintenanceOpen }}, {{ $maintenanceClosed }}],
            backgroundColor: ['#f87171', '#4ade80']
        }]
    }
});

new Chart(document.getElementById('guestsChart'), {
    type: 'doughnut',
    data: {
        labels: ['Total Guests', 'Repeat Guests'],
        datasets: [{
            data: [{{ $totalGuests }}, {{ $repeatGuests }}],
            backgroundColor: ['#2563eb', '#facc15']
        }]
    }
});
</script>

@endsection
