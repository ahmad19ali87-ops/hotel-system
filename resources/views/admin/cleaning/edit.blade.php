@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2 class="mb-4">تعديل طلب تنظيف</h2>

    <form action="{{ route('cleaning.update', $cleaning->id) }}" method="POST">
        @csrf @method('PUT')

        <div class="mb-3">
            <label>الغرفة</label>
            <select name="room_id" class="form-select">
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}" {{ $cleaning->room_id == $room->id ? 'selected' : '' }}>
                        غرفة رقم {{ $room->room_number }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>الحالة</label>
            <select name="status" class="form-select">
                <option value="pending" {{ $cleaning->status === 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                <option value="in_progress" {{ $cleaning->status === 'in_progress' ? 'selected' : '' }}>قيد التنفيذ</option>
                <option value="done" {{ $cleaning->status === 'done' ? 'selected' : '' }}>منتهية</option>
            </select>
        </div>

        <div class="mb-3">
            <label>تاريخ الطلب</label>
            <input type="date" name="requested_at" class="form-control" value="{{ $cleaning->requested_at }}">
        </div>

        <div class="mb-3">
            <label>ملاحظات</label>
            <textarea name="notes" class="form-control">{{ $cleaning->notes }}</textarea>
        </div>

        <button class="btn btn-success w-100">تحديث الطلب</button>

    </form>

</div>
@endsection
