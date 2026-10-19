@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">

    <h2 class="text-3xl font-bold mb-6">Maintenance Report</h2>

    <div class="bg-white p-6 rounded shadow">
        <h3 class="text-xl font-bold mb-4">Maintenance Chart</h3>
        <canvas id="maintenanceChart"></canvas>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
new Chart(document.getElementById('maintenanceChart'), {
    type: 'pie',
    data: {
        labels: ['Open', 'Closed'],
        datasets: [{
            data: [{{ $open }}, {{ $closed }}],
            backgroundColor: ['#f87171', '#4ade80']
        }]
    }
});
</script>

@endsection
