@extends('layouts.app')

@section('content')
<div class="container my-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>الرسائل الداخلية</h2>
        <a href="{{ route('messages.create') }}" class="btn btn-primary">➕ رسالة جديدة</a>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('messages.inbox') }}">📥 صندوق الوارد</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('messages.sent') }}">📤 الرسائل الصادرة</a>
        </li>
    </ul>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>إلى</th>
                <th>الموضوع</th>
                <th>الوقت</th>
                <th>خيارات</th>
            </tr>
        </thead>

        <tbody>
            @forelse($messages as $msg)
                <tr>
                    <td>{{ $msg->receiver?->name ?? 'مستخدم محذوف' }}</td>
                    <td>{{ $msg->subject }}</td>
                    <td>{{ $msg->created_at?->format('Y-m-d H:i') }}</td>
                    <td>
                        <a href="{{ route('messages.show', $msg->id) }}" class="btn btn-info btn-sm">عرض</a>
                        <form action="{{ route('messages.destroy', $msg->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الرسالة؟')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted p-4">لا توجد رسائل صادرة.</td></tr>
            @endforelse
        </tbody>

    </table>

</div>
@endsection
