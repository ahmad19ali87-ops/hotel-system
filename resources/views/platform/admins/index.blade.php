@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl py-6 px-4">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-blue-600 block mb-1">لوحة إدارة المنصة</span>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900">إدارة مدراء ومساعدي المنصة</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">إضافة مدراء وتحديد صلاحياتهم لمساعدتك في إضافة الفنادق، تعديلها، وإدارة المظهر الخارجي</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('platform.admins.create') }}" class="btn btn-primary btn-sm font-bold shadow-sm d-flex align-items-center gap-1.5">
                <span>➕</span>
                <span>إضافة مدير مساعد جديد</span>
            </a>
            <a href="{{ route('platform.hotels.index') }}" class="btn btn-secondary btn-sm">العودة للفنادق</a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border border-slate-200 bg-white rounded-2xl overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-slate-50 text-slate-700 text-xs font-extrabold uppercase border-b border-slate-200">
                    <tr>
                        <th class="ps-4 py-3">المدير</th>
                        <th>البريد الإلكتروني</th>
                        <th>الهاتف</th>
                        <th>الدور</th>
                        <th>الصلاحيات الممنوحة</th>
                        <th class="text-end pe-4">الإجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @php
                        $permissionLabels = \App\Http\Controllers\Platform\PlatformAdminController::PERMISSIONS;
                    @endphp
                    @foreach($admins as $adm)
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <img src="{{ $adm->avatar_url }}" alt="avatar" class="w-8 h-8 rounded-full object-cover border bg-slate-100">
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $adm->name }}</div>
                                        @if($adm->id === auth()->id())
                                            <span class="text-[10px] text-blue-600 font-bold">(حسابك الحالي)</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-xs text-slate-600 font-mono">{{ $adm->email }}</td>
                            <td class="text-xs text-slate-600" dir="ltr">{{ $adm->phone ?: '—' }}</td>
                            <td>
                                @if($adm->role === 'super_admin')
                                    <span class="badge bg-purple-100 text-purple-800 border border-purple-200 font-bold px-2 py-1">
                                        👑 مدير عام رئيسي (Full Access)
                                    </span>
                                @else
                                    <span class="badge bg-blue-100 text-blue-800 border border-blue-200 font-bold px-2 py-1">
                                        🛡️ مدير مساعد للمنصة
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($adm->role === 'super_admin')
                                    <span class="text-xs text-emerald-700 font-bold">كافة صلاحيات المنصة كاملة</span>
                                @else
                                    @php $perms = $adm->permissions_list; @endphp
                                    @if(count($perms))
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($perms as $p)
                                                <span class="badge bg-slate-100 text-slate-700 border text-[10px] px-1.5 py-0.5">
                                                    {{ $permissionLabels[$p] ?? $p }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">لا توجد صلاحيات مخصصة</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="{{ route('platform.admins.edit', $adm) }}" class="btn btn-outline-primary btn-sm text-xs font-bold px-2.5 py-1">
                                        ✏️ تعديل
                                    </a>
                                    @if($adm->id !== auth()->id())
                                        <form action="{{ route('platform.admins.destroy', $adm) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('هل أنت متأكد من حذف حساب هذا المدير؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm text-xs font-bold px-2 py-1">
                                                🗑️
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

