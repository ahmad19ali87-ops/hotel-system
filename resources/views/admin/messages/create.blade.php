@extends('layouts.app')

@section('content')
<div class="row justify-content-center my-4">
    <div class="col-12 col-md-8 col-lg-7">
        <div class="card border-0 shadow-lg rounded-3xl overflow-hidden">
            <div class="card-header bg-gradient-to-r from-blue-700 to-indigo-800 text-white p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="font-black text-xl mb-1 text-white">✉️ إنشاء رسالة جديدة</h4>
                        <p class="text-xs text-blue-100 mb-0">إرسال رسالة داخلية لموظف أو التواصل المباشر مع نزيل</p>
                    </div>
                    <a href="{{ route('messages.inbox') }}" class="btn btn-sm btn-light rounded-xl font-bold text-xs">
                        عودة للبريد
                    </a>
                </div>
            </div>

            <div class="card-body p-4 sm:p-5">
                <form action="{{ route('messages.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    @if($errors->any())
                        <div class="alert alert-danger text-xs font-bold rounded-2xl mb-4">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @if(isset($preselectedGuest) && $preselectedGuest)
                        <div class="p-3 rounded-2xl bg-blue-50 dark:bg-slate-800 border border-blue-200 dark:border-slate-700 mb-3 d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-xs text-slate-500 block">إرسال رسالة خاصة إلى النزيل:</span>
                                <strong class="text-sm text-blue-900 dark:text-blue-300 font-black">{{ $preselectedGuest->full_name }}</strong>
                                <span class="text-xs text-slate-400 ms-2">({{ $preselectedGuest->email ?: 'بدون بريد مسجل' }})</span>
                            </div>
                            <input type="hidden" name="guest_id" value="{{ $preselectedGuest->id }}">
                            <span class="badge bg-blue-600 text-white text-xs px-2.5 py-1">محدد مسبقاً</span>
                        </div>
                    @else
                        {{-- Recipient Selector (Staff or Guest) --}}
                        <div class="mb-3">
                            <label class="form-label font-bold text-xs text-slate-700 dark:text-slate-300">جهة الاستلام (المستلم)</label>
                            <select name="receiver_target" id="recipientTypeSelect" class="form-select rounded-xl text-sm mb-2" onchange="toggleRecipientType(this.value)">
                                <option value="staff">👤 أحد موظفي الفندق</option>
                                <option value="guest">🛎️ أحد نزلاء الفندق</option>
                            </select>

                            {{-- Staff Select --}}
                            <div id="staffSelectBox">
                                <select name="receiver_id" class="form-select rounded-xl text-sm">
                                    <option value="">-- اختر الموظف --</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role }})</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Guest Select --}}
                            <div id="guestSelectBox" class="d-none">
                                <select name="guest_id" class="form-select rounded-xl text-sm">
                                    <option value="">-- اختر النزيل --</option>
                                    @foreach($guests as $g)
                                        <option value="{{ $g->id }}">{{ $g->full_name }} - {{ $g->phone }} ({{ $g->email ?: 'بدون بريد' }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label font-bold text-xs text-slate-700 dark:text-slate-300">موضوع الرسالة <span class="text-rose-500">*</span></label>
                        <input type="text" name="subject" class="form-control rounded-xl py-2.5 px-3 text-sm" required placeholder="مثال: استفسار عن موعد الوصول أو تفاصيل الحجز">
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-bold text-xs text-slate-700 dark:text-slate-300">نص الرسالة <span class="text-rose-500">*</span></label>
                        <textarea name="body" class="form-control rounded-2xl p-3 text-sm" rows="5" required placeholder="اكتب تفاصيل الرسالة هنا..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label font-bold text-xs text-slate-700 dark:text-slate-300">إرفاق ملف أو مستند (اختياري)</label>
                        <input type="file" name="attachment" class="form-control rounded-xl text-sm py-2">
                        <span class="text-[11px] text-slate-400 mt-1 block">الملفات المدعومة: صور (JPG, PNG, WEBP) ومستندات (PDF, DOCX) حتى حجم 10MB</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-2xl font-bold shadow-lg shadow-blue-600/30 text-sm">
                        إرسال الرسالة والمرفقات 🚀
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleRecipientType(type) {
    const staffBox = document.getElementById('staffSelectBox');
    const guestBox = document.getElementById('guestSelectBox');
    if (type === 'guest') {
        staffBox.classList.add('d-none');
        guestBox.classList.remove('d-none');
    } else {
        guestBox.classList.add('d-none');
        staffBox.classList.remove('d-none');
    }
}
</script>
@endsection
