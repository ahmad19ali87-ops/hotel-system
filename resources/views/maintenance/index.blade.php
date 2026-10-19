@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto">

    <h2 class="text-3xl font-bold mb-6">Maintenance</h2>

    <a href="{{ route('maintenance.create') }}"
       class="px-4 py-2 bg-blue-600 text-white rounded mb-4 inline-block">
        Add Maintenance
    </a>

    <table class="w-full border">
        <thead>
            <tr class="bg-gray-200">
                <th class="p-2">Room</th>
                <th class="p-2">Status</th>
                <th class="p-2">Type</th>
                <th class="p-2">Reported</th>
                <th class="p-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td class="p-2">{{ $item->room->name }}</td>
                <td class="p-2">{{ $item->status }}</td>
                <td class="p-2">{{ $item->type }}</td>
                <td class="p-2">{{ $item->reported_at }}</td>
                <td class="p-2">
                    <a href="{{ route('maintenance.edit', $item->id) }}"
                       class="text-blue-600">Edit</a>
                    |
                    <form action="{{ route('maintenance.destroy', $item->id) }}"
                          method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button class="text-red-600">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
@endsection
