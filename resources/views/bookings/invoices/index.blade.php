@extends('layouts.app')

@section('content')

<div class="bg-white p-6 rounded-lg shadow">

    <h2 class="text-2xl font-bold mb-6 text-gray-700">جميع الفواتير</h2>

    <table class="min-w-full border">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-3 border">رقم الفاتورة</th>
                <th class="p-3 border">العميل</th>
                <th class="p-3 border">الغرفة</th>
                <th class="p-3 border">الدخول</th>
                <th class="p-3 border">الخروج</th>
                <th class="p-3 border">الإجمالي</th>
                <th class="p-3 border">عرض</th>
            </tr>
        </thead>

        <tbody>
            @foreach($invoices as $invoice)
            <tr class="text-center border-b">

                <td class="p-3 border">{{ $invoice->id }}</td>

                <td class="p-3 border">
                    {{ $invoice->booking->guest->name }}
                    <div class="text-gray-500 text-sm">
                        {{ $invoice->booking->guest->phone }}
                    </div>
                </td>

                <td class="p-3 border">
                    غرفة رقم {{ $invoice->room->room_number }}
                </td>

                <td class="p-3 border">{{ $invoice->booking->check_in }}</td>

                <td class="p-3 border">{{ $invoice->booking->check_out }}</td>

                <td class="p-3 border text-blue-600 font-bold">
                    {{ $invoice->total }} ريال
                </td>

                <td class="p-3 border">
                    <a href="{{ route('invoices.show', $invoice->id) }}"
                       class="bg-blue-600 text-white py-1 px-3 rounded">
                        عرض
                    </a>
                </td>

            </tr>
            @endforeach
        </tbody>
    </table>

</div>

@endsection
