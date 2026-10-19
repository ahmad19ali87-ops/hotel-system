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
            <td>{{ $inv->total }}</td>
            <td>{{ $inv->created_at->format('Y-m-d') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
