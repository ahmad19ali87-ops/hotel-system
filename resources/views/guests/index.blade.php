@extends('layouts.app')

@section('content')

<div class="bg-white p-6 rounded-lg shadow">

    <div class="flex justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-700">العملاء</h2>

        <a href="{{ route('guests.create') }}"
           class="bg-blue-600 text-white py-2 px-4 rounded-lg shadow">
            + إضافة عميل جديد
        </a>
    </div>

    <table class="min-w-full border">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-3 border">الاسم</th>
                <th class="p-3 border">الهاتف</th>
                <th class="p-3 border">البريد</th>
                <th class="p-3 border">الهوية</th>
                <th class="p-3 border">التحكم</th>
            </tr>
        </thead>

        <tbody>
            @foreach($guests as $guest)
            <tr class="text-center border-b">
                <td class="p-3 border">{{ $guest->name }}</td>
                <td class="p-3 border">{{ $guest->phone }}</td>
                <td class="p-3 border">{{ $guest->email }}</td>
                <td class="p-3 border">{{ $guest->national_id }}</td>

                <td class="p-3 border flex justify-center gap-3">
                    <a href="{{ route('guests.edit', $guest->id) }}"
                       class="text-blue-600 font-semibold">تعديل</a>

                    <form action="{{ route('guests.destroy', $guest->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button class="text-red-600 font-semibold"
                                onclick="return confirm('هل أنت متأكد؟');">
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
