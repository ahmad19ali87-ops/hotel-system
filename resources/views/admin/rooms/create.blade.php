@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2 class="mb-4">إضافة غرفة جديدة</h2>

   <form action="{{ route('rooms.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <!-- باقي الحقول -->



        <div class="mb-3">
            <label>رقم الغرفة</label>
            <input type="text" name="room_number" class="form-control">
        </div>

        <div class="mb-3">
            <label>نوع الغرفة</label>
            <select name="room_type_id" class="form-select">
                @foreach($types as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>السعر</label>
            <input type="number" name="price" class="form-control">
        </div>

        <div class="mb-3">
            <label>عدد الأسرة</label>
            <input type="number" name="beds" class="form-control">
        </div>

        <div class="mb-3">
            <label>الوصف</label>
            <textarea name="description" class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label>صور الغرفة</label>
            <input type="file" name="images[]" multiple class="form-control">
        </div>

        <button class="btn btn-success w-100">حفظ الغرفة</button>

    </form>

</div>
@endsection
