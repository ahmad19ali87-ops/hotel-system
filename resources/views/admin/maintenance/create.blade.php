@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2 class="mb-4">إضافة بلاغ صيانة جديد</h2>

    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form action="{{ route('maintenance.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>الغرفة</label>
            <select name="room_id" required class="form-select">
                <option value="">اختر الغرفة</option>
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>غرفة {{ $room->room_number }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>نوع العطل</label>
            <input type="text" name="issue_type" value="{{ old('issue_type') }}" required maxlength="255" class="form-control">
        </div>

        <div class="mb-3">
            <label>الأولوية</label>
            <select name="priority" class="form-select">
                <option value="low" @selected(old('priority') === 'low')>منخفضة</option>
                <option value="normal" @selected(old('priority', 'normal') === 'normal')>عادية</option>
                <option value="high" @selected(old('priority') === 'high')>عالية</option>
                <option value="urgent" @selected(old('priority') === 'urgent')>عاجلة</option>
            </select>
        </div>

        <div class="mb-3"><label>ملاحظات</label><textarea name="notes" maxlength="4000" class="form-control">{{ old('notes') }}</textarea></div>

        <button class="btn btn-success w-100">حفظ البلاغ</button>

    </form>

</div>
@endsection
