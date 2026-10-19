@extends('layouts.app')

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif



@section('content')
@php
    $statusColor = $invoice->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
    $statusText = $invoice->status === 'paid' ? 'Paid' : 'Unpaid';
@endphp
<div class="mb-4">
    <span class="px-3 py-1 rounded {{ $statusColor }}">
        {{ $statusText }}
    </span>
</div>

<div class="max-w-3xl mx-auto bg-white shadow p-6 rounded">

    <h2 class="text-2xl font-bold mb-4">Invoice {{ $invoice->invoice_number }}</h2>

    <div class="mb-4">
        <p><strong>Guest:</strong> {{ $invoice->booking->guest->name }}</p>
        <p><strong>Room:</strong> {{ $invoice->booking->room->name }}</p>
        <p><strong>Check-in:</strong> {{ $invoice->booking->check_in }}</p>
        <p><strong>Check-out:</strong> {{ $invoice->booking->check_out }}</p>
    </div>

    <table class="w-full border-collapse mb-6">
        <tr class="bg-gray-100">
            <th class="border p-2 text-left">Description</th>
            <th class="border p-2 text-right">Amount</th>
        </tr>

        <tr>
            <td class="border p-2">Room Charges</td>
            <td class="border p-2 text-right">{{ number_format($subtotal, 2) }} SAR</td>
        </tr>

        <tr>
            <td class="border p-2">Tax</td>
            <td class="border p-2 text-right">{{ number_format($tax, 2) }} SAR</td>
        </tr>

        <tr>
            <td class="border p-2">Discount</td>
            <td class="border p-2 text-right">-{{ number_format($discount, 2) }} SAR</td>
        </tr>

        <tr class="bg-gray-100 font-bold">
            <td class="border p-2">Total</td>
            <td class="border p-2 text-right">{{ number_format($total, 2) }} SAR</td>
        </tr>
    </table>

    <div class="text-right">
        <a href="{{ route('invoice.download', $invoice->id) }}"
           class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
           Download PDF
        </a>
    </div>
<form action="{{ route('invoice.toggle', $invoice->id) }}" method="POST">
    @csrf
    <button class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-800">
        Toggle Status
    </button>
</form>

</div>
@endsection
