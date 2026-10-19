@extends('layouts.app')

@section('content')

<div class="bg-white shadow-md rounded-lg p-6">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-700">أنواع الغرف</h2>

        <a href="{{ route('room-types.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg shadow">
            + إضافة نوع جديد
        </a>
    </div>

    <table class="min-w-full bg-white border border-gray-200 rounded-lg">
        <thead>
            <tr class="bg-gray-100 text-gray-700">
                <th class="py-3 px-4 border">الاسم</th>
                <th class="py-3 px-4 border">الوصف</th>
                <th class="py-3 px-4 border">التحكم</th>
            </tr>
        </thead>

        <tbody>
            @foreach($types as $type)
            <tr class="text-center border-b hover:bg-gray-50">

                <td class="py-3 px-4 border font-semibold">
                    {{ $type->name }}
                </td>

                <td class="py-3 px-4 border">
                    {{ $type->description ?? '—' }}
                </td>

                <td class="py-3 px-4 border flex justify-center gap-3">

                    <a href="{{ route('room-types.edit', $type->id) }}"
                       class="text-blue-600 hover:text-blue-800 font-semibold">
                        تعديل
                    </a>

                    <form action="{{ route('room-types.destroy', $type->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('هل أنت متأكد من حذف هذا النوع؟');"
                                class="text-red-600 hover:text-red-800 font-semibold">
                            حذف
                        </button>
                    </form>

                </td>

            </tr>
            @endforeach
        </tbody>
    </table>

</div>

@endsection
