@extends('layouts.app')

@section('content')

<h2 class="text-3xl font-bold text-gray-700 mb-6">لوحة التحكم</h2>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- إجمالي الغرف -->
    <div class="bg-blue-600 text-white p-6 rounded-lg shadow">
        <h3 class="text-xl font-semibold mb-2">إجمالي الغرف</h3>
        <p class="text-4xl font-bold">{{ $totalRooms }}</p>
    </div>

    <!-- الغرف المتاحة -->
    <div class="bg-green-600 text-white p-6 rounded-lg shadow">
        <h3 class="text-xl font-semibold mb-2">الغرف المتاحة</h3>
        <p class="text-4xl font-bold">{{ $availableRooms }}</p>
    </div>

    <!-- الغرف المحجوزة -->
    <div class="bg-orange-500 text-white p-6 rounded-lg shadow">
        <h3 class="text-xl font-semibold mb-2">الغرف المحجوزة</h3>
        <p class="text-4xl font-bold">{{ $reservedRooms }}</p>
    </div>

    <!-- غرف الصيانة -->
    <div class="bg-red-600 text-white p-6 rounded-lg shadow">
        <h3 class="text-xl font-semibold mb-2">غرف تحت الصيانة</h3>
        <p class="text-4xl font-bold">{{ $maintenanceRooms }}</p>
    </div>

    <!-- أنواع الغرف -->
    <div class="bg-purple-600 text-white p-6 rounded-lg shadow">
        <h3 class="text-xl font-semibold mb-2">أنواع الغرف</h3>
        <p class="text-4xl font-bold">{{ $roomTypes }}</p>
    </div>

</div>

<!-- روابط سريعة -->
<div class="mt-10">
    <h3 class="text-xl font-bold text-gray-700 mb-4">روابط سريعة</h3>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <a href="/rooms"
           class="bg-white shadow p-6 rounded-lg text-center hover:bg-gray-100">
            <h4 class="text-lg font-semibold text-gray-700">إدارة الغرف</h4>
        </a>

        <a href="/room-types"
           class="bg-white shadow p-6 rounded-lg text-center hover:bg-gray-100">
            <h4 class="text-lg font-semibold text-gray-700">إدارة أنواع الغرف</h4>
        </a>

        <a href="/rooms/create"
           class="bg-white shadow p-6 rounded-lg text-center hover:bg-gray-100">
            <h4 class="text-lg font-semibold text-gray-700">إضافة غرفة جديدة</h4>
        </a>

    </div>
</div>

@endsection
