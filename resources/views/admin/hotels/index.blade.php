@extends('layouts.app')

@section('content')
<div class="container my-4">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="mb-0">🏨 إدارة فنادق المنصة</h2>
            <p class="text-muted small mb-0">لوحة التحكم المركزية لإدارة فنادق النظام والمظهر الخارجي</p>
        </div>
        <div class="d-flex flex-wrap items-center gap-2">
            @if(Auth::user()->hasPermission('appearance') || Auth::user()->role === 'super_admin')
                <a href="{{ route('platform.settings.edit') }}" class="btn btn-outline-secondary">
                    ⚙️ مظهر ونصوص الموقع
                </a>
                <a href="{{ route('platform.destinations.index') }}" class="btn btn-outline-primary">
                    🏙️ الوجهات والمظهر
                </a>
            @endif
            @if(Auth::user()->hasPermission('admins') || Auth::user()->role === 'super_admin')
                <a href="{{ route('platform.admins.index') }}" class="btn btn-outline-info">
                    👥 مدراء المنصة
                </a>
            @endif
            @if(Auth::user()->hasPermission('hotels_create') || Auth::user()->role === 'super_admin')
                <a href="{{ route('platform.hotels.create') }}" class="btn btn-primary">
                    ➕ إضافة فندق جديد
                </a>
            @endif
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">اسم الفندق</th>
                            <th>الرابط (Slug)</th>
                            <th>المحافظة</th>
                            <th>التصنيف</th>
                            <th>الهاتف</th>
                            <th>الغرف</th>
                            <th>الحجوزات</th>
                            <th class="text-end pe-3">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hotels as $hotel)
                            <tr>
                                <td class="ps-3 fw-bold">{{ $hotel->name }}</td>
                                <td><code>{{ $hotel->slug }}</code></td>
                                <td><span class="badge bg-light text-dark border">{{ $hotel->province }}</span></td>
                                <td>
                                    @for($s = 0; $s < $hotel->stars; $s++)
                                        ⭐
                                    @endfor
                                </td>
                                <td>{{ implode(' | ', $hotel->phones ? (json_decode($hotel->phones, true) ?: []) : array_filter([$hotel->phone])) ?: '—' }}</td>
                                <td><span class="badge bg-info text-dark">{{ $hotel->rooms_count }} غرفة</span></td>
                                <td><span class="badge bg-secondary">{{ $hotel->bookings_count }} حجز</span></td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('hotel.front', $hotel->slug) }}" target="_blank" class="btn btn-outline-info btn-sm me-1" title="معاينة الواجهة">🌐</a>
                                    
                                    @if(Auth::user()->hasPermission('backups') || Auth::user()->role === 'super_admin')
                                        <a href="{{ route('platform.hotels.backup.index', $hotel) }}" class="btn btn-outline-success btn-sm me-1" title="نسخ احتياطي واستعادة">💾 النسخ</a>
                                    @endif

                                    @if(Auth::user()->hasPermission('hotels_edit') || Auth::user()->role === 'super_admin')
                                        <form action="{{ route('platform.hotels.toggle-visibility', $hotel) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm me-1 {{ $hotel->is_visible ? 'btn-outline-success' : 'btn-outline-secondary' }}" title="{{ $hotel->is_visible ? 'الفندق ظاهر في الرئيسية (انقر للإخفاء)' : 'الفندق مخفي من الرئيسية (انقر للإظهار)' }}">
                                                {{ $hotel->is_visible ? '👁️ ظاهر' : '🚫 مخفي' }}
                                            </button>
                                        </form>
                                        <a href="{{ route('platform.hotels.edit', $hotel) }}" class="btn btn-outline-primary btn-sm me-1">تعديل</a>
                                    @endif

                                    @if(Auth::user()->hasPermission('hotels_delete') || Auth::user()->role === 'super_admin')
                                        <form action="{{ route('platform.hotels.destroy', $hotel) }}" method="POST" class="d-inline" onsubmit="return confirm('سيتم أرشفة الفندق وإخفاؤه عن الزوار. هل تريد المتابعة؟')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">أرشفة</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    لا توجد فنادق مضافة حتى الآن.
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
