@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto bg-white shadow p-6 rounded">

    <h2 class="text-3xl font-bold mb-6">Housekeeping Dashboard</h2>

    @if($rooms->count() == 0)
        <p class="text-gray-600">No rooms need cleaning.</p>
    @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            @foreach($rooms as $room)
                <div class="p-4 bg-yellow-50 border border-yellow-300 rounded shadow">

                    <h3 class="text-xl font-bold mb-2">{{ $room->name }}</h3>

                    <p class="text-yellow-700 font-semibold mb-3">
                        Needs Cleaning
                    </p>

                    <form action="{{ route('housekeeping.clean', $room->id) }}" method="POST">
                        @csrf
                        <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            Mark as Cleaned
                        </button>
                    </form>

                </div>
            @endforeach

        </div>
    @endif

</div>
@endsection
