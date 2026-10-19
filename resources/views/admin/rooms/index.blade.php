@extends('layouts.app')

@section('content')
<div class="container my-4">

    {{-- App Header Toolbar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h3 fw-bold mb-1">🚪 إدارة غرف الفندق</h2>
            <p class="text-muted mb-0 small">متابعة جاهزية الغرف، الأنواع، والتسعير المزدوج بالليرة السورية والدولار الأمريكي.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route('room-types.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 shadow-sm">
                <span>🏷️</span>
                <span>أنواع الغرف</span>
            </a>
            @if(auth()->user()->role !== 'hotel_owner')
                <a href="{{ route('rooms.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
                    <span>➕</span>
                    <span>إضافة غرفة جديدة</span>
                </a>
            @else
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-1 border border-warning">
                    <span>👁️</span> وضع اطلاع مالك الفندق (قراءة ومراقبة فقط)
                </span>
            @endif
        </div>
    </div>

    {{-- KPI Cards --}}
    @php
        $totalRooms = $rooms->count();
        $availableRooms = $rooms->where('status', 'available')->count();
        $bookedRooms = $rooms->whereIn('status', ['booked', 'reserved'])->count();
        $maintenanceRooms = $rooms->where('status', 'maintenance')->count();
        $cleaningRooms = $rooms->where('status', 'cleaning')->count();
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">إجمالي الغرف</span>
                    <h4 class="fw-bold mb-0 text-primary mt-1">{{ $totalRooms }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">الغرف المتاحة</span>
                    <h4 class="fw-bold mb-0 text-success mt-1">{{ $availableRooms }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-4 border-info h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">المحجوزة حالياً</span>
                    <h4 class="fw-bold mb-0 text-info mt-1">{{ $bookedRooms }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-4 border-warning h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold">صيانة أو تنظيف</span>
                    <h4 class="fw-bold mb-0 text-warning mt-1">{{ $maintenanceRooms + $cleaningRooms }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Live Search & Filter Toolbar --}}
    <div class="card border-0 shadow-sm mb-3 bg-light">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0">🔍</span>
                        <input type="text" id="roomsSearchInput" class="form-control border-start-0" placeholder="بحث فوري برقم الغرفة، نوع الغرفة، أو الحالة...">
                    </div>
                </div>
                <div class="col-md-6 text-md-end text-muted small">
                    عرض: <span id="visibleRoomsCount" class="fw-bold">{{ $rooms->count() }}</span> غرفة
                </div>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="roomsTable">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3">الصورة</th>
                            <th>رقم الغرفة</th>
                            <th>نوع الغرفة</th>
                            <th>السعر بالليرة السورية</th>
                            <th>السعر بالدولار ($)</th>
                            <th>الحالة والجاهزية</th>
                            <th class="text-end pe-3">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $statusMap = [
                                'available' => ['title' => 'متاحة', 'class' => 'bg-success'],
                                'booked' => ['title' => 'محجوزة', 'class' => 'bg-primary'],
                                'reserved' => ['title' => 'محجوزة', 'class' => 'bg-primary'],
                                'maintenance' => ['title' => 'صيانة', 'class' => 'bg-danger'],
                                'cleaning' => ['title' => 'تنظيف', 'class' => 'bg-warning text-dark'],
                            ];
                        @endphp
                        @forelse($rooms as $room)
                            @php
                                $typeName = $room->type?->name ?? $room->roomType?->name ?? 'بدون نوع';
                            @endphp
                            <tr class="room-row">
                                <td class="ps-3" style="width: 80px;">
                                    @if($room->image_url)
                                        <img src="{{ $room->image_url }}" alt="غرفة {{ $room->room_number }}" class="rounded shadow-sm" style="width: 60px; height: 44px; object-fit: cover;">
                                    @else
                                        <div class="bg-light text-muted small rounded d-flex align-items-center justify-content-center border" style="width: 60px; height: 44px;">
                                            🚪
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 fs-6">
                                        غرفة {{ $room->room_number }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                        🏷️ {{ $typeName }}
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-primary">{{ number_format($room->price) }} ل.س</strong>
                                </td>
                                <td>
                                    @if(!empty($room->price_usd) && $room->price_usd > 0)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold">
                                            ${{ number_format($room->price_usd, 2) }} USD
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $statusMap[$room->status]['class'] ?? 'bg-secondary' }}">
                                        {{ $statusMap[$room->status]['title'] ?? $room->status }}
                                    </span>
                                    @if($room->needs_cleaning)
                                        <span class="badge bg-warning text-dark d-block mt-1" style="font-size: 0.75rem;">🧹 بحاجة تنظيف</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    @if(auth()->user()->role === 'hotel_owner')
                                        <span class="badge bg-light text-muted border">اطلاع فقط</span>
                                    @else
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('rooms.edit', $room->id) }}" class="btn btn-outline-primary btn-sm">
                                                تعديل
                                            </a>
                                            <form action="{{ route('rooms.destroy', $room->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الغرفة؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    حذف
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    لا توجد غرف مسجلة في هذا الفندق حالياً.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('roomsSearchInput');
    const rows = document.querySelectorAll('.room-row');
    const countSpan = document.getElementById('visibleRoomsCount');

    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            const query = e.target.value.trim().toLowerCase();
            let count = 0;
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (!query || text.includes(query)) {
                    row.style.display = '';
                    count++;
                } else {
                    row.style.display = 'none';
                }
            });
            if (countSpan) countSpan.textContent = count;
        });
    }
});
</script>
@endsection
