@extends('layouts.app')

@section('content')
<div class="container my-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="font-black text-2xl sm:text-3xl tracking-tight text-slate-900 dark:text-slate-100 mb-1">
                ✉️ صندوق المراسلات الداخلي
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mb-0">
                تواصل مباشرة مع إدارات الفنادق والاستقبال، وأرسل واستقبل الاستفسارات والملاحظات مع المرفقات
            </p>
        </div>
        <button type="button" class="btn btn-primary rounded-2xl font-bold text-xs sm:text-sm px-4 py-2.5 shadow-md shadow-blue-600/20" data-bs-toggle="modal" data-bs-target="#newGuestMessageModal">
            ✍️ رسالة جديدة للفندق
        </button>
    </div>

    @if($messages->isEmpty())
        <div class="card border-0 shadow-sm rounded-3xl p-5 text-center my-4 bg-white dark:bg-slate-800">
            <div class="text-5xl mb-3">📬</div>
            <h4 class="font-bold text-slate-800 dark:text-slate-100 mb-2">صندوق الرسائل فارغ</h4>
            <p class="text-xs text-slate-500 max-w-md mx-auto mb-4">
                لا توجد لديك رسائل واردة أو صادرة حتى الآن. يمكنك مراسلة إدارة أي فندق للاستفسار عن الحجوزات أو الخدمات الإضافية.
            </p>
            <div>
                <button type="button" class="btn btn-primary rounded-xl px-4 py-2 font-bold text-sm" data-bs-toggle="modal" data-bs-target="#newGuestMessageModal">
                    إرسال رسالة جديدة الآن
                </button>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-md rounded-3xl overflow-hidden bg-white dark:bg-slate-800">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-900/60 text-xs font-bold uppercase text-slate-500 border-b border-slate-100 dark:border-slate-700">
                        <tr>
                            <th class="ps-4 py-3">الطرف الآخر / الفندق</th>
                            <th>الموضوع</th>
                            <th>نص الرسالة</th>
                            <th>المرفقات</th>
                            <th>التاريخ</th>
                            <th class="text-end pe-4">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        @foreach($messages as $msg)
                            @php
                                $isSentByMe = ($msg->sender_id === Auth::id());
                            @endphp
                            <tr class="{{ !$isSentByMe && !$msg->is_read ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' }}">
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge {{ $isSentByMe ? 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200' : 'bg-blue-100 text-blue-800' }} text-[10px] px-2 py-0.5 rounded-lg">
                                            {{ $isSentByMe ? 'صادرة منك' : 'واردة من الفندق' }}
                                        </span>
                                        <strong class="text-xs font-bold text-slate-800 dark:text-slate-100">
                                            {{ $msg->hotel?->name ?? ($isSentByMe ? $msg->receiver?->name : $msg->sender?->name) }}
                                        </strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-bold text-slate-800 dark:text-slate-200 block truncate max-w-xs">
                                        {{ $msg->subject }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 block truncate max-w-md">
                                        {{ $msg->body }}
                                    </span>
                                </td>
                                <td>
                                    @if($msg->attachment)
                                        <a href="{{ $msg->attachment_url }}" target="_blank" download class="badge bg-emerald-100 text-emerald-800 text-[11px] px-2 py-1 rounded-lg text-decoration-none d-inline-flex align-items-center gap-1">
                                            <span>📎</span> <span>تحميل</span>
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-[11px] text-slate-400">
                                        {{ $msg->created_at?->diffForHumans() }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('messages.show', $msg) }}" class="btn btn-sm btn-outline-primary rounded-xl font-bold text-xs py-1 px-3">
                                        عرض
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $messages->links() }}
        </div>
    @endif
</div>

{{-- New Message Modal for Guest --}}
<div class="modal fade" id="newGuestMessageModal" tabindex="-1" aria-labelledby="newGuestMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-2xl rounded-3xl overflow-hidden">
            <div class="modal-header bg-gradient-to-r from-blue-700 to-indigo-800 text-white p-4 border-0">
                <h5 class="modal-title font-black text-lg text-white" id="newGuestMessageModalLabel">
                    ✉️ مراسلة إدارة الفندق
                </h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
            </div>
            <form action="{{ route('guest.messages.send') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">اختر الفندق <span class="text-rose-500">*</span></label>
                        <select name="hotel_id" class="form-select rounded-xl text-sm py-2.5" required>
                            <option value="">-- حدد الفندق المستلم --</option>
                            @foreach($hotels as $h)
                                <option value="{{ $h->id }}">{{ $h->name }} ({{ $h->province }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">الموضوع <span class="text-rose-500">*</span></label>
                        <input type="text" name="subject" class="form-control rounded-xl text-sm py-2.5" required placeholder="مثال: استفسار عن خدمات الغرف أو موعد الوصول">
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">نص الرسالة <span class="text-rose-500">*</span></label>
                        <textarea name="body" class="form-control rounded-2xl p-3 text-sm" rows="4" required placeholder="اكتب استفسارك أو طلبك هنا..."></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">مرفق (صورة أو مستند اختياري)</label>
                        <input type="file" name="attachment" class="form-control rounded-xl text-sm py-2" accept="image/*,.pdf,.doc,.docx">
                        <span class="text-[11px] text-slate-400 mt-1 block">يمكنك إرفاق صور أو ملفات PDF حتى حجم 10MB</span>
                    </div>
                </div>
                <div class="modal-footer p-3 bg-slate-50 dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" class="btn btn-outline-secondary rounded-xl font-bold text-xs py-2 px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-xl font-bold text-xs py-2 px-4 shadow-md shadow-blue-600/25">إرسال الرسالة والمرفق 🚀</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

