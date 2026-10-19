@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2 class="mb-4">تعديل بلاغ صيانة</h2>

    <form action="{{ route('maintenance.update', $maintenance->id) }}" method="POST">
        @csrf @method('PUT')

        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        <div class="mb-3"><label>الغرفة</label>
            <select name="room_id" required class="form-select">
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}" @selected(old('room_id', $maintenance->room_id) == $room->id)>غرفة {{ $room->room_number }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3"><label>نوع العطل</label><input type="text" name="issue_type" required class="form-control" value="{{ old('issue_type', $maintenance->issue_type) }}"></div>
        <div class="mb-3"><label>الأولوية</label>
            <select name="priority" class="form-select">
                @foreach(['low' => 'منخفضة', 'normal' => 'عادية', 'high' => 'عالية', 'urgent' => 'عاجلة'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('priority', $maintenance->priority) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3"><label>ملاحظات</label><textarea name="notes" class="form-control">{{ old('notes', $maintenance->notes) }}</textarea></div>

        <div class="mb-3">
            <label>الحالة</label>
            <select name="status" class="form-select">
                <option value="open" {{ $maintenance->status === 'open' ? 'selected' : '' }}>مفتوح</option>
                <option value="in_progress" {{ $maintenance->status === 'in_progress' ? 'selected' : '' }}>قيد التنفيذ</option>
                <option value="closed" {{ $maintenance->status === 'closed' ? 'selected' : '' }}>مغلق</option>
            </select>
        </div>


        <button class="btn btn-success w-100">تحديث البلاغ</button>

    </form>

</div>
@endsection
