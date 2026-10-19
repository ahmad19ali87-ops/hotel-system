@extends('layouts.app')

@section('content')

<div class="bg-white p-6 rounded-lg shadow max-w-xl mx-auto">

    <h2 class="text-2xl font-bold mb-6 text-gray-700">تعديل بيانات العميل</h2>

    <form action="{{ route('guests.update', $guest->id) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label class="font-semibold">الاسم:</label>
            <input type="text" name="name" value="{{ $guest->name }}"
                   class="w-full border rounded-lg p-2" required>
        </div>

        <div>
            <label class="font-semibold">الهاتف:</label>
            <input type="text" name="phone" value="{{ $guest->phone }}"
                   class="w-full border rounded-lg p-2">
        </div>

        <div>
            <label class="font-semibold">البريد الإلكتروني:</label>
            <input type="email" name="email" value="{{ $guest->email }}"
                   class="w-full border rounded-lg p-2">
        </div>

        <div>
            <label class="font-semibold">رقم الهوية / الإقامة:</label>
            <input type="text" name="national_id" value="{{ $guest->national_id }}"
                   class="w-full border rounded-lg p-2">
        </div>

        <button class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-6 rounded-lg shadow">
            حفظ التعديلات
        </button>

    </form>

</div>

@endsection
