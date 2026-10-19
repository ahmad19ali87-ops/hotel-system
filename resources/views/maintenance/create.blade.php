@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto bg-white shadow p-6 rounded">

    <h2 class="text-2xl font-bold mb-4">Create Maintenance Request for {{ $room->name }}</h2>

    <form action="{{ route('maintenance.store') }}" method="POST">
        @csrf

        <input type="hidden" name="room_id" value="{{ $room->id }}">

        <label class="block mb-2 font-semibold">Issue Type</label>
        <select name="issue_type" class="border p-2 rounded w-full mb-4">
            <option value="Electricity">Electricity</option>
            <option value="Plumbing">Plumbing</option>
            <option value="Air Conditioning">Air Conditioning</option>
            <option value="Furniture">Furniture</option>
            <option value="Other">Other</option>
        </select>

        <label class="block mb-2 font-semibold">Priority</label>
        <select name="priority" class="border p-2 rounded w-full mb-4">
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
        </select>

        <label class="block mb-2 font-semibold">Notes</label>
        <textarea name="notes" class="border p-2 rounded w-full mb-4" rows="3"></textarea>

        <button class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
            Submit Maintenance Request
        </button>
    </form>

</div>
@endsection
