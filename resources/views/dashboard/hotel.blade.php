@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto py-10">

    <h1 class="text-3xl font-bold mb-6">
        لوحة إدارة فندق {{ $hotel->name }}
    </h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <div class="p-6 bg-white shadow rounded">
            <h2 class="text-xl font-bold">الغرف</h2>
            <p class="text-3xl font-bold mt-2">{{ $rooms }}</p>
            <a href="{{ url('hotel/' . $hotel->slug . '/dashboard/rooms') }}"
               class="text-blue-600 mt-3 inline-block">إدارة الغرف</a>
        </div>

        <div class="p-6 bg-white shadow rounded">
            <h2 class="text-xl font-bold">الحجوزات</h2>
            <p class="text-3xl font-bold mt-2">{{ $bookings }}</p>
            <a href="{{ url('hotel/' . $hotel->slug . '/dashboard/bookings') }}"
               class="text-blue-600 mt-3 inline-block">إدارة الحجوزات</a>
        </div>

        <div class="p-6 bg-white shadow rounded">
            <h2 class="text-xl font-bold">النزلاء</h2>
            <p class="text-3xl font-bold mt-2">{{ $guests }}</p>
            <a href="{{ url('hotel/' . $hotel->slug . '/dashboard/guests') }}"
               class="text-blue-600 mt-3 inline-block">إدارة النزلاء</a>
        </div>

        <div class="p-6 bg-white shadow rounded">
            <h2 class="text-xl font-bold">الإيرادات</h2>
            <p class="text-3xl font-bold mt-2">{{ $revenue }} ريال</p>
            <a href="{{ url('hotel/' . $hotel->slug . '/dashboard/reports/revenue') }}"
               class="text-blue-600 mt-3 inline-block">تقرير الإيرادات</a>
        </div>

        <div class="p-6 bg-white shadow rounded">
            <h2 class="text-xl font-bold">الصيانة المفتوحة</h2>
            <p class="text-3xl font-bold mt-2">{{ $maintenanceOpen }}</p>
            <a href="{{ url('hotel/' . $hotel->slug . '/dashboard/maintenance') }}"
               class="text-blue-600 mt-3 inline-block">إدارة الصيانة</a>
        </div>

    </div>

</div>
@endsection
