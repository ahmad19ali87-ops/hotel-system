@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl py-6 px-4">
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-indigo-600 mb-1">
                <a href="{{ route('platform.crm.index', ['tab' => 'support']) }}" class="hover:underline text-indigo-600">تذاكر الدعم الفني</a>
                <span>/</span>
                <span class="text-slate-500 font-mono">{{ $ticket->ticket_number }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 flex items-center gap-2">
                <span>🎫 {{ $ticket->subject }}</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                الفندق: <strong class="text-slate-800">{{ $ticket->hotel?->name }}</strong> · المنشئ: <strong>{{ $ticket->creator?->name }}</strong> ({{ $ticket->creator?->email }})
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="badge {{ $ticket->priority_badge_class }} px-3 py-1.5 font-bold">{{ $ticket->priority_label }}</span>
            <span class="badge {{ $ticket->status_badge_class }} px-3 py-1.5 font-bold">{{ $ticket->status_label }}</span>
            <a href="{{ route('platform.crm.index', ['tab' => 'support']) }}" class="btn btn-outline-secondary btn-sm font-bold ms-2">
                ⬅️ العودة للقائمة
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Messages Thread --}}
        <div class="lg:col-span-2 space-y-4">
            @foreach($ticket->messages as $msg)
                <div class="card p-4 rounded-2xl shadow-sm border {{ $msg->is_staff_reply ? 'border-indigo-200 bg-indigo-50/40 ms-4' : 'border-slate-200 bg-white me-4' }}">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-full {{ $msg->is_staff_reply ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-700' }} flex items-center justify-center text-xs font-bold">
                                {{ $msg->is_staff_reply ? '🎧' : '🏨' }}
                            </span>
                            <span class="font-bold text-xs text-slate-900">
                                {{ $msg->sender?->name }}
                                @if($msg->is_staff_reply)
                                    <span class="text-[10px] text-indigo-600 font-bold bg-indigo-100 px-1.5 py-0.5 rounded">فريق دعم المنصة</span>
                                @else
                                    <span class="text-[10px] text-slate-500 font-bold bg-slate-100 px-1.5 py-0.5 rounded">إدارة الفندق</span>
                                @endif
                            </span>
                        </div>
                        <span class="text-[11px] text-slate-400">{{ $msg->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="text-sm text-slate-800 leading-relaxed whitespace-pre-wrap">
                        {{ $msg->message }}
                    </div>
                </div>
            @endforeach

            {{-- Reply Form --}}
            <div class="card p-5 rounded-2xl shadow-sm border border-slate-200 bg-white mt-6">
                <h4 class="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    <span>✍️ كتابة رد الدعم الفني</span>
                </h4>
                <form action="{{ route('platform.crm.tickets.reply', $ticket) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <textarea name="message" class="form-control rounded-xl text-sm" rows="4" required placeholder="اكتب ردك ومساعدتك لإدارة الفندق هنا..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">تحديث حالة التذكرة</label>
                            <select name="status" class="form-select form-select-sm rounded-xl">
                                <option value="waiting_customer" selected>بانتظار رد العميل (Waiting Customer)</option>
                                <option value="in_progress">قيد المتابعة والمعالجة (In Progress)</option>
                                <option value="resolved">تم الحل بنجاح (Resolved)</option>
                                <option value="closed">إغلاق وأرشفة (Closed)</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700">تعيين لموظف دعم</label>
                            <select name="assigned_to" class="form-select form-select-sm rounded-xl">
                                <option value="">-- غير معين --</option>
                                @foreach($team as $tm)
                                    <option value="{{ $tm->id }}" {{ $ticket->assigned_to == $tm->id ? 'selected' : '' }}>{{ $tm->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary btn-sm font-bold px-4 py-2">
                            🚀 إرسال الرد وتحديث التذكرة
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Ticket Metadata Sidebar --}}
        <div class="space-y-4">
            <div class="card p-4 rounded-2xl shadow-sm border border-slate-200 bg-white">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">بيانات التذكرة والفندق</h4>
                <div class="space-y-2.5 text-xs">
                    <div>
                        <span class="text-slate-400 block">رقم التذكرة:</span>
                        <span class="font-mono font-bold text-slate-900">{{ $ticket->ticket_number }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">الفندق:</span>
                        <span class="font-bold text-slate-900">{{ $ticket->hotel?->name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">القسم:</span>
                        <span class="badge bg-slate-100 text-slate-800 border">{{ $ticket->department_label }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">الأولوية:</span>
                        <span class="badge {{ $ticket->priority_badge_class }}">{{ $ticket->priority_label }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">الحالة الحالية:</span>
                        <span class="badge {{ $ticket->status_badge_class }}">{{ $ticket->status_label }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">تاريخ الإنشاء:</span>
                        <span class="text-slate-700">{{ $ticket->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">آخر رد وتحديث:</span>
                        <span class="text-slate-700">{{ $ticket->last_reply_at ? $ticket->last_reply_at->diffForHumans() : '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

