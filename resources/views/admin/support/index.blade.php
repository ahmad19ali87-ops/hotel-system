@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl py-6 px-4">
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-600 mb-1">
                <span>إدارة الفندق</span>
                <span>/</span>
                <span class="text-slate-500">مركز الدعم الفني والتذاكر</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 flex items-center gap-2">
                <span>🎧 تذاكر الدعم الفني والمساعدة</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                تواصل مباشر ومستمر مع فريق دعم المنصة لحل المشاكل التقنية، الاستفسارات المالية، وطلبات التطوير
            </p>
        </div>
        <div>
            <a href="{{ route('admin.support.create') }}" class="btn btn-primary btn-sm font-bold shadow-sm d-flex align-items-center gap-1.5">
                <span>➕</span>
                <span>فتح تذكرة دعم جديدة</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Tickets Table --}}
    <div class="card shadow-sm border border-slate-200 bg-white rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h3 class="text-base font-black text-slate-900 m-0">قائمة تذاكر فندقكم</h3>
            <span class="text-xs text-slate-500">متابعة فورية ومباشرة لحالة التذاكر</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-sm">
                <thead class="bg-slate-100 text-slate-700 text-xs font-bold uppercase">
                    <tr>
                        <th class="ps-4">رقم التذكرة</th>
                        <th>الموضوع</th>
                        <th>القسم</th>
                        <th>الأولوية</th>
                        <th>الحالة</th>
                        <th>آخر تحديث</th>
                        <th class="text-end pe-4">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tickets as $tck)
                        <tr>
                            <td class="ps-4 font-mono font-bold text-blue-700">{{ $tck->ticket_number }}</td>
                            <td>
                                <div class="font-bold text-slate-900">{{ $tck->subject }}</div>
                                <div class="text-xs text-slate-500 truncate max-w-sm">{{ $tck->latestMessage?->message }}</div>
                            </td>
                            <td>
                                <span class="badge bg-slate-100 text-slate-700 border">{{ $tck->department_label }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $tck->priority_badge_class }}">{{ $tck->priority_label }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $tck->status_badge_class }}">{{ $tck->status_label }}</span>
                            </td>
                            <td class="text-xs text-slate-500">
                                {{ $tck->last_reply_at ? $tck->last_reply_at->diffForHumans() : '—' }}
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.support.show', $tck) }}" class="btn btn-outline-primary btn-sm font-bold text-xs">
                                    💬 استعراض والرد
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-slate-400">
                                <div class="text-3xl mb-2">🎧</div>
                                <div class="font-bold text-slate-700">لا توجد تذاكر دعم فني مفتوحة حالياً.</div>
                                <div class="text-xs text-slate-400 mt-1">إذا واجهتك أي مشكلة أو استفسار، يمكنك فتح تذكرة جديدة وسيقوم فريق المنصة بمساعدتك فوراً.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
            <div class="p-3 border-t bg-slate-50">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

