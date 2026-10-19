@extends('layouts.app')

@section('content')
<div class="container my-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>إدارة شكاوى العملاء</h2>
        @if(auth()->user()->role !== 'hotel_owner')
            <a href="{{ route('complaints.create') }}" class="btn btn-primary">➕ تسجيل شكوى جديدة</a>
        @else
            <span class="badge bg-warning text-dark font-bold px-3 py-2 rounded-xl">👑 مالك الفندق - وضع الاطلاع فقط</span>
        @endif
    </div>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">العنوان</th>
                            <th>العميل</th>
                            <th>الحجز</th>
                            <th>الغرفة</th>
                            <th>الحالة</th>
                            <th>الموظف المعالج</th>
                            <th class="text-end pe-3">خيارات</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($complaints as $complaint)
                            <tr>
                                <td class="ps-3 fw-bold">{{ $complaint->title }}</td>
                                <td>{{ $complaint->guest?->name ?? '—' }}</td>
                                <td>{{ $complaint->booking ? '#'.$complaint->booking->id : '—' }}</td>
                                <td>{{ $complaint->room ? 'غرفة '.$complaint->room->room_number : '—' }}</td>
                                <td>
                                    @if($complaint->status == 'new')
                                        <span class="badge bg-danger">جديدة</span>
                                    @elseif($complaint->status == 'in_progress')
                                        <span class="badge bg-warning text-dark">قيد المعالجة</span>
                                    @else
                                        <span class="badge bg-success">مغلقة</span>
                                    @endif
                                </td>
                                <td>{{ $complaint->handler?->name ?? '—' }}</td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('complaints.show', $complaint->id) }}" class="btn btn-outline-info btn-sm me-1">عرض</a>
                                    @if(auth()->user()->role !== 'hotel_owner')
                                        <a href="{{ route('complaints.edit', $complaint->id) }}" class="btn btn-outline-primary btn-sm me-1">تعديل</a>
                                        <form action="{{ route('complaints.destroy', $complaint->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الشكوى؟');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                        </form>
                                    @else
                                        <span class="badge bg-slate-100 text-slate-500 border border-slate-200 text-xs px-2 py-1">اطلاع فقط</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد شكاوى مسجلة لهذا الفندق.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
