@extends('layouts.app')

@section('content')
<div class="container my-4">

    <h2>إدارة الموظفين</h2>
    @if(auth()->user()->role !== 'hotel_owner')
        <a href="{{ route('employees.create') }}" class="btn btn-primary mt-3">إضافة موظف</a>
    @else
        <div class="mt-3">
            <span class="badge bg-amber-500 text-white px-3 py-2 rounded-pill font-bold">
                <span>👁️</span> وضع اطلاع مالك الفندق (قراءة ومراقبة فقط)
            </span>
        </div>
    @endif

    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>الاسم</th>
                <th>البريد</th>
                <th>الدور</th>
                <th>خيارات</th>
            </tr>
        </thead>

        <tbody>
            @php
                $rolesMap = [
                    'hotel_owner' => ['title' => 'مالك الفندق (اطلاع فقط)', 'class' => 'bg-warning text-dark fw-bold border border-warning'],
                    'reception' => ['title' => 'استقبال', 'class' => 'bg-info text-dark'],
                    'accountant' => ['title' => 'محاسب', 'class' => 'bg-success text-white'],
                    'maintenance' => ['title' => 'صيانة', 'class' => 'bg-secondary text-white'],
                    'cleaner' => ['title' => 'تنظيف', 'class' => 'bg-dark text-white'],
                ];
            @endphp
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="badge {{ $rolesMap[$user->role]['class'] ?? 'bg-light text-dark' }}">
                            {{ $rolesMap[$user->role]['title'] ?? $user->role }}
                        </span>
                        @if(!empty($user->permissions_list))
                            <div class="mt-1 d-flex flex-wrap gap-1">
                                @foreach($user->permissions_list as $extraPerm)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle text-[10px]">
                                        + {{ $rolesMap[$extraPerm]['title'] ?? $extraPerm }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td>
                        @if(auth()->user()->role !== 'hotel_owner')
                            <a href="{{ route('employees.edit', $user->id) }}" class="btn btn-warning btn-sm">تعديل</a>
                            <form action="{{ route('employees.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الموظف؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                            </form>
                        @else
                            <span class="badge bg-light text-muted border">اطلاع فقط</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted p-4">لا يوجد موظفون مسجلون بعد.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>
@endsection
