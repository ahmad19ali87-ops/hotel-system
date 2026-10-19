@extends('layouts.app')

@section('content')
<div class="container my-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="mb-0">🧹 مهام وإدارة التنظيف</h2>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-primary">الغرف المطلوب تنظيفها</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">رقم الغرفة</th>
                            <th>نوع الغرفة</th>
                            <th>الحالة الحالية</th>
                            <th class="text-end pe-3">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rooms as $room)
                            <tr>
                                <td class="ps-3 fw-bold">غرفة {{ $room->room_number }}</td>
                                <td>{{ $room->type?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-warning text-dark">بحاجة لتنظيف</span>
                                </td>
                                <td class="text-end pe-3">
                                    <form method="POST" action="{{ route('cleaning.complete', $room) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            ✓ تم التنظيف
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    🎉 جميع الغرف نظيفة حالياً، لا توجد غرف تنتظر التنظيف.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-secondary">سجل آخر مهام التنظيف المنجزة</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">الغرفة</th>
                            <th>الموظف المنفذ</th>
                            <th class="text-end pe-3">وقت الإنجاز</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLogs as $log)
                            <tr>
                                <td class="ps-3 fw-bold">غرفة {{ $log->room?->room_number ?? '—' }}</td>
                                <td>{{ $log->cleaner?->name ?? '—' }}</td>
                                <td class="text-end pe-3 text-muted">{{ $log->cleaned_at }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    لا يوجد سجل لعمليات التنظيف حتى الآن.
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
