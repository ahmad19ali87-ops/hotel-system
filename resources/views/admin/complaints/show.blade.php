@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2>{{ $complaint->title }}</h2>

    <div class="card p-3 shadow mb-4">
        <p><strong>العميل:</strong> {{ $complaint->guest->name ?? '-' }}</p>
        <p><strong>الحجز:</strong> {{ $complaint->booking ? '#'.$complaint->booking->id : '-' }}</p>
        <p><strong>الغرفة:</strong> {{ $complaint->room ? 'غرفة '.$complaint->room->room_number : '-' }}</p>
        <p><strong>الحالة:</strong> {{ $complaint->status }}</p>
        <hr>
        <p><strong>نص الشكوى:</strong></p>
        <p>{{ $complaint->message }}</p>
        @if($complaint->response)
            <hr>
            <p><strong>رد الإدارة:</strong></p>
            <p>{{ $complaint->response }}</p>
        @endif
    </div>

</div>
@endsection
