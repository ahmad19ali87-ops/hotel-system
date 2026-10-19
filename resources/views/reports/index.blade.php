@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">

    <h2 class="text-3xl font-bold mb-6">Reports Dashboard</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <a href="{{ route('reports.revenue') }}" class="p-6 bg-blue-100 text-blue-700 rounded shadow">
            <h3 class="text-xl font-bold">Revenue Report</h3>
        </a>

        <a href="{{ route('reports.occupancy') }}" class="p-6 bg-green-100 text-green-700 rounded shadow">
            <h3 class="text-xl font-bold">Occupancy Report</h3>
        </a>

        <a href="{{ route('reports.maintenance') }}" class="p-6 bg-red-100 text-red-700 rounded shadow">
            <h3 class="text-xl font-bold">Maintenance Report</h3>
        </a>

        <a href="{{ route('reports.guests') }}" class="p-6 bg-yellow-100 text-yellow-700 rounded shadow">
            <h3 class="text-xl font-bold">Guests Report</h3>
        </a>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    </div>

</div>
@endsection
