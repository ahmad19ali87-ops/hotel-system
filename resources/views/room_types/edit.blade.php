@extends('layouts.app')

@section('content')

<div class="bg-white shadow-md rounded-lg p-6 max-w-xl mx-auto">

    <h2 class="text-2xl font-bold text-gray-700 mb-6">تعديل نوع الغرفة</h2>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            <ul class="list-disc pr-5">
                @foreach ($errors->all() as $error)
                    <li class="text-sm">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('room-types.update', $roomType->id) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label class="block mb-1 font-semibold text-gray-700">اسم النوع:</label>
            <input type="text" name="name" value="{{ $roomType->name }}"
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label class="block mb-1 font-semibold text-gray-700">الوصف:</label>
            <textarea name="description"
                      class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ $roomType->description }}</textarea>
        </div>

        <div class="text-center">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-6 rounded-lg shadow">
                حفظ التعديلات
            </button>
        </div>

    </form>

</div>

@endsection
