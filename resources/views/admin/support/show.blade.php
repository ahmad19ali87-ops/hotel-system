@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl py-6 px-4">
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-600 mb-1">
                <a href="{{ route('admin.support.index') }}" class="hover:underline text-blue-600">تذاكر الدعم الفني</a>
                <span>/</span>
                <span class="text-slate-500 font-mono">{{ $ticket->ticket_number }}</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2">
                <span>🎫 {{ $ticket->subject }}</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                القسم: <strong>{{ $ticket->department_label }}</strong> · الأولوية: <span class="badge {{ $ticket->priority_badge_class }}">{{ $ticket->priority_label }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="badge {{ $ticket->status_badge_class }} px-3 py-1.5 font-bold">{{ $ticket->status_label }}</span>
            <a href="{{ route('admin.support.index') }}" class="btn btn-outline-secondary btn-sm font-bold ms-2">
                ⬅️ العودة للتذاكر
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Messages Thread --}}
    <div class="space-y-4 mb-6">
        @foreach($ticket->messages as $msg)
            <div class="card p-4 rounded-2xl shadow-sm border {{ $msg->is_staff_reply ? 'border-indigo-300 bg-indigo-50/50 ms-6' : 'border-slate-200 bg-white me-6' }}">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-full {{ $msg->is_staff_reply ? 'bg-indigo-600 text-white' : 'bg-blue-600 text-white' }} flex items-center justify-center text-xs font-bold">
                            {{ $msg->is_staff_reply ? '🎧' : '👤' }}
                        </span>
                        <span class="font-bold text-xs text-slate-900">
                            {{ $msg->sender?->name }}
                            @if($msg->is_staff_reply)
                                <span class="text-[10px] text-indigo-700 font-bold bg-indigo-100 px-1.5 py-0.5 rounded">فريق دعم المنصة الرسمي</span>
                            @else
                                <span class="text-[10px] text-slate-500 font-bold bg-slate-100 px-1.5 py-0.5 rounded">طاقم الفندق</span>
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
    </div>

    {{-- Reply Form --}}
    @if($ticket->status !== 'closed')
        <div class="card p-5 rounded-2xl shadow-sm border border-slate-200 bg-white">
            <h4 class="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                <span>✍️ إضافة رد على التذكرة</span>
            </h4>
            <form action="{{ route('admin.support.reply', $ticket) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <textarea name="message" class="form-control rounded-xl text-sm" rows="4" required placeholder="اكتب ردك أو توضيحك الإضافي لفريق الدعم هنا..."></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary font-bold px-5 py-2 rounded-xl shadow-sm">
                        🚀 إرسال الرد
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="p-4 bg-slate-100 rounded-2xl border text-center text-xs text-slate-500 font-bold">
            هذه التذكرة مغلقة ومؤرشفة. إذا كانت لديك مشكلة جديدة، يمكنك فتح تذكرة جديدة.
        </div>
    @endif
</div>
@endsection

