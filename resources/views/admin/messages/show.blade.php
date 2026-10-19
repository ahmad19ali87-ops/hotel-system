@extends('layouts.app')

@section('content')
<div class="row justify-content-center my-4">
    <div class="col-12 col-md-9 col-lg-8">
        <div class="card border-0 shadow-lg rounded-3xl overflow-hidden">
            <div class="card-header bg-gradient-to-r from-blue-700 to-indigo-800 text-white p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="badge bg-white/20 text-white text-xs mb-1">رسالة</span>
                        <h4 class="font-black text-xl mb-0 text-white">{{ $message->subject }}</h4>
                    </div>
                    <a href="{{ route('messages.inbox') }}" class="btn btn-sm btn-light rounded-xl font-bold text-xs">
                        عودة لصندوق الوارد
                    </a>
                </div>
            </div>

            <div class="card-body p-4 sm:p-5">
                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 mb-4">
                    <div class="row g-2 text-xs">
                        <div class="col-sm-6">
                            <span class="text-slate-400 block">المرسل:</span>
                            <strong class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                {{ $message->sender?->name ?? 'مستخدم' }}
                                @if($message->sender?->role === 'guest')
                                    <span class="badge bg-amber-100 text-amber-800 ms-1">نزيل</span>
                                @elseif($message->sender?->role)
                                    <span class="badge bg-blue-100 text-blue-800 ms-1">{{ $message->sender->role }}</span>
                                @endif
                            </strong>
                            <div class="text-[11px] text-slate-400">{{ $message->sender?->email }}</div>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-slate-400 block">المستلم:</span>
                            <strong class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                {{ $message->receiver?->name ?? 'مستخدم' }}
                                @if($message->receiver?->role === 'guest')
                                    <span class="badge bg-amber-100 text-amber-800 ms-1">نزيل</span>
                                @endif
                            </strong>
                            <div class="text-[11px] text-slate-400">تاريخ الإرسال: {{ $message->created_at?->format('Y-m-d H:i') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Message Body --}}
                <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 mb-4 leading-relaxed" style="white-space: pre-wrap; font-size: 0.95rem;">{{ $message->body }}</div>

                {{-- Attachment if present --}}
                @if($message->attachment)
                    <div class="p-3 rounded-2xl bg-blue-50 dark:bg-slate-800 border border-blue-200 dark:border-slate-700 mb-4 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-2xl">📎</span>
                            <div>
                                <span class="font-bold text-xs text-blue-900 dark:text-blue-300 block">ملف مرفق مع الرسالة</span>
                                <span class="text-[11px] text-slate-400">{{ basename($message->attachment) }}</span>
                            </div>
                        </div>
                        <a href="{{ $message->attachment_url }}" target="_blank" download class="btn btn-sm btn-primary rounded-xl font-bold text-xs px-3">
                            ⬇️ تحميل المرفق
                        </a>
                    </div>
                @endif

                <div class="d-flex gap-2 pt-3 border-top border-slate-100 dark:border-slate-800">
                    <a href="{{ route('messages.create') }}?receiver_id={{ $message->sender_id }}" class="btn btn-primary rounded-xl font-bold text-xs py-2 px-4">
                        ↩️ رد على الرسالة
                    </a>
                    <a href="{{ route('messages.inbox') }}" class="btn btn-outline-secondary rounded-xl font-bold text-xs py-2 px-4">
                        العودة للبريد
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
