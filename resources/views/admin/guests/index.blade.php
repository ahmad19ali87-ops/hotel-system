@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl py-6 px-4">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-blue-600 block mb-1">قسم الاستقبال والنزلاء</span>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900">سجل النزلاء والعملاء</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">البحث والتحقق من بيانات النزلاء، وثائق الهوية، وجوازات السفر والمرفقات الرسمية</p>
        </div>
        <div class="flex items-center gap-2">
            @if(auth()->user()->role !== 'hotel_owner')
                <a href="{{ route('guests.create') }}" class="btn btn-primary btn-sm font-bold shadow-sm d-flex align-items-center gap-1.5">
                    <span>➕</span>
                    <span>تسجيل نزيل جديد (مع سكنر الوثائق)</span>
                </a>
            @else
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-1 border border-warning">
                    <span>👁️</span> وضع اطلاع مالك الفندق (قراءة ومراقبة فقط)
                </span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Search Bar --}}
    <div class="card p-4 shadow-sm border border-slate-200 bg-white rounded-2xl mb-6">
        <form method="GET" action="{{ route('guests.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <span class="absolute inset-y-0 right-3 flex items-center text-slate-400">🔍</span>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="ابحث بالاسم، اسم الأب، الكنية/النسبة، رقم الهاتف، رقم الهوية أو الجواز..." class="w-full rounded-xl border border-slate-300 pr-10 pl-4 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit" class="btn btn-primary px-4 py-2 text-sm font-bold w-full sm:w-auto shadow-sm">
                    بحث
                </button>
                @if(request('q'))
                    <a href="{{ route('guests.index') }}" class="btn btn-light px-3 py-2 text-sm w-full sm:w-auto">
                        إلغاء الفلتر
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Guests Table --}}
    <div class="card shadow-sm border border-slate-200 bg-white rounded-2xl overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-slate-50 text-slate-700 text-xs font-extrabold uppercase border-b border-slate-200">
                    <tr>
                        <th class="ps-4 py-3">النزيل (الاسم الكامل)</th>
                        <th>الجنسية</th>
                        <th>الوثيقة ورقمها</th>
                        <th>معلومات الاتصال</th>
                        <th>الوثائق والمرفقات</th>
                        <th>الحجوزات</th>
                        <th class="text-end pe-4">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($guests as $guest)
                        @php
                            $idTypeLabels = [
                                'national_id' => 'بطاقة شخصية / هوية',
                                'passport' => 'جواز سفر',
                                'residence' => 'إقامة',
                                'driver_license' => 'شهادة قيادة',
                            ];
                            $attachments = $guest->attachments_list;
                        @endphp
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="font-bold text-slate-900 d-flex align-items-center gap-1.5">
                                    <span>{{ $guest->full_name }}</span>
                                    <a href="{{ route('messages.create', ['guest_id' => $guest->id]) }}" class="position-relative inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 hover:bg-blue-600 text-blue-700 hover:text-white transition shadow-sm" title="مراسلة النزيل عبر بريد الموقع الداخلي">
                                        ✉️
                                        @if(($guest->unread_messages_count ?? 0) > 0)
                                            <span class="position-absolute -top-1 -end-1 flex h-2.5 w-2.5">
                                                <span class="animate-ping position-absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-600"></span>
                                            </span>
                                        @endif
                                    </a>
                                </div>
                                @if($guest->father_name || $guest->birth_date)
                                    <div class="text-[11px] text-slate-400">
                                        @if($guest->father_name) <span>الأب: {{ $guest->father_name }}</span> @endif
                                        @if($guest->birth_date) <span class="ms-2">المواليد: {{ $guest->birth_date }}</span> @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-slate-100 text-slate-700 border border-slate-200 font-medium px-2 py-1">
                                    {{ $guest->nationality ?: 'سوري' }}
                                </span>
                            </td>
                            <td>
                                <div class="font-bold text-slate-800">
                                    {{ $guest->id_number ?: $guest->national_id ?: '—' }}
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    {{ $idTypeLabels[$guest->id_type] ?? 'هوية وطنية' }}
                                </div>
                            </td>
                            <td>
                                <div class="text-xs">
                                    @if($guest->phone)
                                        <div class="font-bold text-slate-800 flex items-center gap-1" dir="ltr">
                                            <span>{{ $guest->phone }}</span>
                                            <span>📞</span>
                                        </div>
                                    @endif
                                    @if($guest->email)
                                        <div class="text-[11px] text-slate-400">{{ $guest->email }}</div>
                                    @endif
                                    @if(!$guest->phone && !$guest->email)
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if(count($attachments))
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="badge bg-blue-50 text-blue-700 border border-blue-200 px-2 py-1 font-bold text-xs">
                                            📁 {{ count($attachments) }} مرفق
                                        </span>
                                        @foreach(array_slice($attachments, 0, 2) as $att)
                                            <a href="{{ $att['url'] }}" target="_blank" class="w-7 h-7 rounded border border-slate-200 overflow-hidden inline-block hover:scale-110 transition shadow-xs" title="عرض الوثيقة">
                                                @if(in_array(strtolower($att['type']), ['jpg', 'jpeg', 'png', 'webp']))
                                                    <img src="{{ $att['url'] }}" class="w-full h-full object-cover">
                                                @else
                                                    <span class="w-full h-full flex items-center justify-center bg-slate-100 text-[10px] font-bold text-slate-600">PDF</span>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">لا توجد وثائق</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold px-2 py-1">
                                    {{ $guest->bookings_count ?? 0 }} إقامة
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="{{ route('guests.print', $guest) }}" target="_blank" class="btn btn-outline-secondary btn-sm text-xs font-bold px-2.5 py-1" title="طباعة بطاقة تسجيل النزيل الرسمية">
                                        🖨️ طباعة البطاقة
                                    </a>
                                    <a href="{{ route('guests.history', $guest) }}" class="btn btn-outline-info btn-sm text-xs font-bold px-2.5 py-1" title="سجل الإقامة">
                                        📋 الإقامات
                                    </a>
                                    @if(auth()->user()->role !== 'hotel_owner')
                                        <a href="{{ route('guests.edit', $guest) }}" class="btn btn-outline-primary btn-sm text-xs font-bold px-2.5 py-1" title="تعديل">
                                            ✏️ تعديل
                                        </a>
                                        <form action="{{ route('guests.destroy', $guest) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('هل أنت متأكد من حذف هذا النزيل وكافة وثائقه؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm text-xs font-bold px-2 py-1" title="حذف">
                                                🗑️
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-slate-100 text-slate-500 border border-slate-200 text-xs px-2 py-1">اطلاع فقط</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12 text-slate-400">
                                <span class="text-3xl block mb-2">👥</span>
                                <h4 class="font-bold text-slate-700">لا يوجد نزلاء مطابقين</h4>
                                <p class="text-xs text-slate-400 mt-1">يمكنك إضافة نزيل جديد وتوثيق بياناته ومستنداته فوراً.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($guests->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $guests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
