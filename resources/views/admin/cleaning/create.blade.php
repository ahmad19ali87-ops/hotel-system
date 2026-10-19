@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2 class="mb-4">إضافة طلب تنظيف جديد</h2>

    <form action="{{ route('cleaning.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>الغرفة</label>
            <select name="room_id" class="form-select">
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}">غرفة رقم {{ $room->room_number }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>تاريخ الطلب</label>
            <input type="date" name="requested_at" class="form-control">
        </div>

        <div class="mb-3">
            <label>ملاحظات</label>
            <textarea name="notes" class="form-control"></textarea>
        </div>

        <button class="btn btn-success w-100">حفظ الطلب</button>

    </form>

</div>
@endsection
