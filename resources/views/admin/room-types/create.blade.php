@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2 class="mb-4">إضافة نوع غرفة جديد</h2>

    <form action="{{ route('room-types.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>اسم النوع</label>
            <input type="text" name="name" class="form-control">
        </div>

        <div class="mb-3">
            <label>الوصف</label>
            <textarea name="description" class="form-control"></textarea>
        </div>

        <button class="btn btn-success w-100">حفظ النوع</button>

    </form>

</div>
@endsection
