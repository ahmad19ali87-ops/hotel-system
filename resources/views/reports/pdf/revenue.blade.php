<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .title { font-size: 22px; font-weight: bold; margin-bottom: 20px; }
        .section { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 8px; font-size: 14px; }
        th { background: #eee; }
    </style>
</head>
<body>
<a href="{{ route('reports.revenue.excel', request()->query()) }}"
   class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
    Export Excel
</a>

    <div class="title">Revenue Report</div>

    <div class="section">
        <strong>Date Range:</strong>
        {{ $from_date ?? 'All' }} - {{ $to_date ?? 'All' }}
    </div>

    <div class="section">
        <strong>Total Revenue:</strong> {{ number_format($totalRevenue, 2) }} SAR
    </div>

    <table>
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Guest</th>
                <th>Room</th>
                <th>Total</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $inv)
            <tr>
                <td>{{ $inv->invoice_number }}</td>
                <td>{{ $inv->booking->guest->name }}</td>
                <td>{{ $inv->booking->room->name }}</td>
                <td>{{ number_format($inv->total, 2) }} SAR</td>
                <td>{{ $inv->created_at->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
