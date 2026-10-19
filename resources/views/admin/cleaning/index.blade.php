@extends('layouts.app')

@section('content')
<div class="container my-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="mb-0">إدارة التنظيفات</h2>
        @if(auth()->user()->role !== 'hotel_owner')
            <a href="{{ route('cleaning.create') }}" class="btn btn-primary">➕ إضافة طلب تنظيف</a>
        @else
            <span class="badge bg-warning text-dark font-bold px-3 py-2 rounded-xl">👑 مالك الفندق - وضع الاطلاع فقط</span>
        @endif
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>الغرفة</th>
                <th>الحالة</th>
                <th>تاريخ الطلب</th>
                <th>ملاحظات</th>
                <th>خيارات</th>
            </tr>
        </thead>

        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>غرفة رقم {{ $item->room->room_number }}</td>

                    <td>
                        @if($item->status === 'pending')
                            قيد الانتظار
                        @elseif($item->status === 'in_progress')
                            قيد التنفيذ
                        @else
                            منتهية
                        @endif
                    </td>

                    <td>{{ $item->requested_at }}</td>
                    <td>{{ $item->notes }}</td>

                    <td>
                        @if(auth()->user()->role !== 'hotel_owner')
                            <a href="{{ route('cleaning.edit', $item->id) }}" class="btn btn-warning btn-sm">تعديل</a>

                            <form action="{{ route('cleaning.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا السجل؟');">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">حذف</button>
                            </form>
                        @else
                            <span class="badge bg-slate-100 text-slate-500 border border-slate-200 text-xs px-2 py-1">اطلاع فقط</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>

    </table>

</div>
@endsection
