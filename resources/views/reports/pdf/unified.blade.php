<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .title { font-size: 24px; font-weight: bold; margin-bottom: 20px; }
        .section-title { font-size: 18px; font-weight: bold; margin-top: 20px; }
        .box { border: 1px solid #000; padding: 10px; margin-top: 10px; }
        .row { margin-bottom: 8px; }
    </style>
</head>
<body>

    <div class="title">Unified Hotel Report</div>

    <div class="row">
        <strong>Date Range:</strong>
        {{ $from ?? 'All' }} - {{ $to ?? 'All' }}
    </div>

    {{-- الإيرادات --}}
    <div class="section-title">Revenue</div>
    <div class="box">
        <strong>Total Revenue:</strong> {{ number_format($revenue, 2) }} SAR
    </div>

    {{-- الإشغال --}}
    <div class="section-title">Occupancy</div>
    <div class="box">
        <div class="row"><strong>Total Rooms:</strong> {{ $rooms }}</div>
        <div class="row"><strong>Booked Rooms:</strong> {{ $booked }}</div>
        <div class="row"><strong>Occupancy Rate:</strong> {{ $occupancyRate }}%</div>
    </div>

    {{-- الصيانة --}}
    <div class="section-title">Maintenance</div>
    <div class="box">
        <div class="row"><strong>Open:</strong> {{ $maintenanceOpen }}</div>
        <div class="row"><strong>Closed:</strong> {{ $maintenanceClosed }}</div>
    </div>

    {{-- العملاء --}}
    <div class="section-title">Guests</div>
    <div class="box">
        <div class="row"><strong>Total Guests:</strong> {{ $totalGuests }}</div>
        <div class="row"><strong>Repeat Guests:</strong> {{ $repeatGuests }}</div>
    </div>

    {{-- الحجوزات --}}
    <div class="section-title">Bookings</div>
    <div class="box">
        <div class="row"><strong>Total Bookings:</strong> {{ $totalBookings }}</div>
        <div class="row"><strong>Active Bookings:</strong> {{ $activeBookings }}</div>
    </div>

</body>
</html>
