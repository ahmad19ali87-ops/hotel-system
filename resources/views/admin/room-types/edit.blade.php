@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2 class="mb-4">تعديل نوع الغرفة</h2>

    <form action="{{ route('room-types.update', $roomType->id) }}" method="POST">
        @csrf @method('PUT')

        <div class="mb-3">
            <label>اسم النوع</label>
            <input type="text" name="name" class="form-control" value="{{ $roomType->name }}">
        </div>

        <div class="mb-3">
            <label>الوصف</label>
            <textarea name="description" class="form-control">{{ $roomType->description }}</textarea>
        </div>

        <button class="btn btn-success w-100">تحديث النوع</button>

    </form>

</div>
@endsection
