@extends('layouts.app')

@section('content')
<div class="container my-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="mb-0">🏷️ إدارة أنواع الغرف</h2>
        @if(auth()->user()->role !== 'hotel_owner')
            <a href="{{ route('room-types.create') }}" class="btn btn-primary">➕ إضافة نوع جديد</a>
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
                            <th class="ps-3">اسم النوع</th>
                            <th>الوصف</th>
                            <th class="text-end pe-3">خيارات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($types as $type)
                            <tr>
                                <td class="ps-3 fw-bold">{{ $type->name }}</td>
                                <td>{{ $type->description ?? '—' }}</td>
                                <td class="text-end pe-3">
                                    @if(auth()->user()->role !== 'hotel_owner')
                                        <a href="{{ route('room-types.edit', $type->id) }}" class="btn btn-outline-primary btn-sm me-1">تعديل</a>
                                        <form action="{{ route('room-types.destroy', $type->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا النوع؟');">
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
                                <td colspan="3" class="text-center py-4 text-muted">
                                    لا توجد أنواع غرف مضافة حتى الآن.
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

