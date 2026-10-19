@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">

    <h2 class="text-3xl font-bold mb-6">Occupancy Report</h2>

    <div class="p-6 bg-white rounded shadow mb-6">
        <h3 class="text-xl font-bold mb-2">Occupancy Rate</h3>
        <p class="text-4xl font-bold">{{ $occupancyRate }}%</p>
    </div>

    <div class="bg-white p-6 rounded shadow">
        <h3 class="text-xl font-bold mb-4">Occupancy Chart</h3>
        <canvas id="occupancyChart"></canvas>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
new Chart(document.getElementById('occupancyChart'), {
    type: 'bar',
    data: {
        labels: ['Total Rooms', 'Booked Rooms'],
        datasets: [{
            label: 'Rooms',
            data: [{{ $rooms }}, {{ $booked }}],
            backgroundColor: ['#4ade80', '#2563eb']
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
