@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl py-6 px-4">
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-600 mb-1">
                <a href="{{ route('admin.support.index') }}" class="hover:underline text-blue-600">تذاكر الدعم الفني</a>
                <span>/</span>
                <span class="text-slate-500">فتح تذكرة جديدة</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900">فتح تذكرة دعم فني جديدة 🎧</h1>
        </div>
        <div>
            <a href="{{ route('admin.support.index') }}" class="btn btn-outline-secondary btn-sm font-bold">
                ⬅️ العودة للتذاكر
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger rounded-2xl shadow-sm mb-4">
            <ul class="mb-0 text-xs">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
        <form action="{{ route('admin.support.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="form-label text-xs font-bold text-slate-700">موضوع التذكرة / المشكلة *</label>
                    <input type="text" name="subject" class="form-control rounded-xl text-sm" required placeholder="مثال: استفسار بخصوص ربط الطابعة الحرارية أو إضافة مستخدم جديد" value="{{ old('subject') }}">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label text-xs font-bold text-slate-700">قسم المساعدة *</label>
                        <select name="department" class="form-select rounded-xl text-sm" required>
                            <option value="technical">الدعم التقني والتشغيلي (طابعات، غرف، سيستم)</option>
                            <option value="billing">الاشتراكات والفوترة المالية</option>
                            <option value="sales">المبيعات وتطوير الأعمال</option>
                            <option value="feature_request">اقتراحات ومزايا إضافية جديدة</option>
                            <option value="general">استفسارات عامة</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label text-xs font-bold text-slate-700">مستوى الأولوية *</label>
                        <select name="priority" class="form-select rounded-xl text-sm" required>
                            <option value="low">عادية (استفسار غير عاجل)</option>
                            <option value="medium" selected>متوسطة (مشكلة بسيطة)</option>
                            <option value="high">مرتفعة (تعطل جزء من العمليات)</option>
                            <option value="urgent">عاجلة وطوارئ 🔥 (توقف كلي)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="form-label text-xs font-bold text-slate-700">تفاصيل المشكلة والرسالة *</label>
                    <textarea name="message" class="form-control rounded-xl text-sm" rows="6" required placeholder="يرجى كتابة كافة التفاصيل لمساعدة فريق الدعم على الحل السريع...">{{ old('message') }}</textarea>
                </div>

                <div class="pt-3 border-t flex justify-end">
                    <button type="submit" class="btn btn-primary font-bold px-6 py-2 rounded-xl shadow-sm">
                        🚀 إرسال التذكرة لفريق الدعم
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

