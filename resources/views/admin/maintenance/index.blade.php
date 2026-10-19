@extends('layouts.app')

@section('content')
<div class="container my-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="mb-0">🛠️ إدارة الصيانة</h2>
        @if(auth()->user()->role !== 'hotel_owner')
            <a href="{{ route('maintenance.create') }}" class="btn btn-primary">➕ إضافة أمر صيانة جديد</a>
        @else
            <span class="badge bg-warning text-dark font-bold px-3 py-2 rounded-xl">👑 مالك الفندق - وضع الاطلاع فقط</span>
        @endif
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">الغرفة</th>
                            <th>نوع العطل</th>
                            <th>الأولوية</th>
                            <th>الملاحظات</th>
                            <th>الحالة</th>
                            <th>تاريخ الفتح</th>
                            <th class="text-end pe-3">خيارات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td class="ps-3 fw-bold">غرفة {{ $item->room?->room_number ?? '—' }}</td>
                                <td>{{ $item->issue_type }}</td>
                                <td>
                                    @php
                                        $priorityClass = match(strtolower($item->priority)) {
                                            'high', 'عالية' => 'bg-danger',
                                            'medium', 'متوسطة' => 'bg-warning text-dark',
                                            'low', 'منخفضة' => 'bg-info text-dark',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $priorityClass }}">{{ $item->priority }}</span>
                                </td>
                                <td>{{ $item->notes ?? '—' }}</td>
                                <td>
                                    @if($item->status === 'open')
                                        <span class="badge bg-danger">مفتوح</span>
                                    @elseif($item->status === 'in_progress')
                                        <span class="badge bg-primary">قيد التنفيذ</span>
                                    @else
                                        <span class="badge bg-success">مكتمل ومغلق</span>
                                    @endif
                                </td>
                                <td>{{ $item->opened_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="text-end pe-3">
                                    @if(auth()->user()->role !== 'hotel_owner')
                                        <a href="{{ route('maintenance.edit', $item->id) }}" class="btn btn-outline-primary btn-sm">تعديل</a>
                                        <form action="{{ route('maintenance.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف أمر الصيانة هذا؟');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                        </form>
                                    @else
                                        <span class="badge bg-slate-100 text-slate-500 border border-slate-200 text-xs px-2 py-1">اطلاع فقط</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    لا توجد طلبات صيانة حالياً.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

