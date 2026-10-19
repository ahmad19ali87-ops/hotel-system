@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2>تحديث حالة الشكوى</h2>

    <form action="{{ route('complaints.update', $complaint->id) }}" method="POST">
        @csrf @method('PUT')

        <div class="mb-3">
            <label>الحالة</label>
            <select name="status" class="form-select">
                <option value="new" {{ $complaint->status == 'new' ? 'selected' : '' }}>جديدة</option>
                <option value="in_progress" {{ $complaint->status == 'in_progress' ? 'selected' : '' }}>قيد المعالجة</option>
                <option value="closed" {{ $complaint->status == 'closed' ? 'selected' : '' }}>مغلقة</option>
            </select>
        </div>

        <div class="mb-3">
            <label>رد الإدارة (اختياري)</label>
            <textarea name="response" class="form-control" rows="4">{{ $complaint->response }}</textarea>
        </div>

        <button class="btn btn-success w-100">تحديث الشكوى</button>

    </form>

</div>
@endsection
