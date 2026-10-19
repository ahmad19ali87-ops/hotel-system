@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2 class="mb-4">لوحة إدارة الفندق</h2>

    <div class="row">

        <div class="col-md-3 mb-3">
            <a href="{{ route('rooms.index') }}" class="btn btn-primary w-100 py-3">إدارة الغرف</a>
        </div>

        <div class="col-md-3 mb-3">
            <a href="{{ route('room-types.index') }}" class="btn btn-primary w-100 py-3">أنواع الغرف</a>
        </div>

        <div class="col-md-3 mb-3">
            <a href="{{ route('maintenance.index') }}" class="btn btn-primary w-100 py-3">الصيانة</a>
        </div>

        <div class="col-md-3 mb-3">
            <a href="{{ route('cleaning.index') }}" class="btn btn-primary w-100 py-3">التنظيفات</a>
        </div>

        <div class="col-md-3 mb-3">
            <a href="{{ route('accounts.index') }}" class="btn btn-primary w-100 py-3">الحسابات</a>
        </div>

        <div class="col-md-3 mb-3">
            <a href="{{ route('invoices.index') }}" class="btn btn-primary w-100 py-3">الفواتير</a>
        </div>

    </div>

</div>
@endsection
