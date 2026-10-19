@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto py-10">

    <h1 class="text-3xl font-bold mb-6">الفنادق المتاحة</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        @foreach($hotels as $hotel)
        <a href="{{ url('hotel/' . $hotel->slug) }}" class="block border rounded-lg p-4 shadow hover:shadow-lg">

            <img src="{{ asset($hotel->logo) }}" class="w-full h-40 object-cover rounded">

            <h2 class="text-xl font-bold mt-3">{{ $hotel->name }}</h2>

            <p class="text-gray-600">{{ $hotel->address }}</p>

            <p class="text-yellow-500">⭐ {{ $hotel->stars }}</p>

        </a>
        @endforeach

    </div>

</div>
@endsection
