@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl py-6 px-4">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-blue-600 block mb-1">لوحة إدارة المنصة</span>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900">إعدادات الموقع والمظهر الخارجي</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">تخصيص صورة الغلاف، الأيقونة، الاسم، نصوص الواجهة الرئيسية، وكلمات الميتا لمحركات البحث</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('home') }}" target="_blank" class="btn btn-outline-primary btn-sm font-bold d-flex align-items-center gap-1.5">
                <span>👁️</span>
                <span>معاينة الواجهة الرئيسية</span>
            </a>
            <a href="{{ route('platform.destinations.index') }}" class="btn btn-outline-secondary btn-sm">
                🏙️ إدارة المحافظات والوجهات
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('platform.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- 1. Brand Identity Card --}}
        <div class="card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <h2 class="text-lg font-extrabold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>🏷️</span>
                <span>هوية واسم وأيقونة الموقع</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-bold text-slate-700 mb-1">اسم الموقع والمنصة <span class="text-red-500">*</span></label>
                    <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name'] ?? 'نظام إدارة وحجز الفنادق') }}" required class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">الرمز التعبيري / الأيقونة</label>
                    <input type="text" name="site_icon" value="{{ old('site_icon', $settings['site_icon'] ?? '🏨') }}" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-center text-xl">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Logo Upload --}}
                <div class="border rounded-xl p-4 bg-slate-50">
                    <label class="block text-sm font-bold text-slate-700 mb-2">شعار المنصة (Logo)</label>
                    @php $logoUrl = \App\Models\SiteSetting::getImageUrl('site_logo'); @endphp
                    @if($logoUrl)
                        <div class="relative inline-block mb-3">
                            <img src="{{ $logoUrl }}" alt="Logo" class="h-16 object-contain rounded border bg-white p-1 shadow-sm">
                            <label class="flex items-center gap-1.5 mt-1.5 text-xs text-red-600 font-bold cursor-pointer">
                                <input type="checkbox" name="delete_site_logo" value="1" class="rounded text-red-600">
                                <span>حذف الشعار واستخدام النص الافتراضي</span>
                            </label>
                        </div>
                    @endif
                    <input type="file" name="site_logo" accept="image/*" class="w-full rounded-xl border border-slate-300 p-2 bg-white text-xs">
                </div>

                {{-- Favicon Upload --}}
                <div class="border rounded-xl p-4 bg-slate-50">
                    <label class="block text-sm font-bold text-slate-700 mb-2">أيقونة المتصفح (Favicon .ico/.png)</label>
                    @php $favUrl = \App\Models\SiteSetting::getImageUrl('site_favicon'); @endphp
                    @if($favUrl)
                        <div class="mb-2 flex items-center gap-2">
                            <img src="{{ $favUrl }}" alt="Favicon" class="w-7 h-7 rounded border bg-white p-0.5">
                            <span class="text-xs text-slate-500 font-mono">الأيقونة المفضلة الحالية</span>
                        </div>
                    @endif
                    <input type="file" name="site_favicon" accept="image/x-icon,image/png,image/svg+xml" class="w-full rounded-xl border border-slate-300 p-2 bg-white text-xs">
                </div>
            </div>
        </div>

        {{-- 2. Hero Section & Texts --}}
        <div class="card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <h2 class="text-lg font-extrabold text-slate-900 mb-2 pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>✨</span>
                <span>نصوص الواجهة الرئيسية وصورة الغلاف العلوية</span>
            </h2>
            <p class="text-xs text-slate-500 mb-4">هذه النصوص والصورة تظهر في أعلى الصفحة الرئيسية أمام جميع الزوار:</p>

            {{-- Cover Image Upload --}}
            <div class="border rounded-2xl p-4 bg-slate-50 mb-5">
                <label class="block text-sm font-bold text-slate-800 mb-2">صورة الغلاف العلوية (Hero Cover Image)</label>
                @php $coverUrl = \App\Models\SiteSetting::getImageUrl('hero_cover_image'); @endphp
                @if($coverUrl)
                    <div class="relative rounded-2xl overflow-hidden border border-slate-300 mb-3 max-h-48">
                        <img src="{{ $coverUrl }}" alt="Cover" class="w-full h-44 object-cover">
                        <div class="absolute bottom-2 right-2 bg-slate-900/80 rounded-xl px-3 py-1.5 backdrop-blur-sm">
                            <label class="flex items-center gap-2 text-xs text-red-300 font-bold cursor-pointer">
                                <input type="checkbox" name="delete_hero_cover" value="1" class="rounded text-red-500">
                                <span>حذف صورة الغلاف والعودة للنمط التدرجي الذكي</span>
                            </label>
                        </div>
                    </div>
                @endif
                <label class="block text-xs font-semibold text-slate-600 mb-1">
                    {{ $coverUrl ? 'استبدال صورة الغلاف:' : 'رفع صورة غلاف بانورامية بدقة عالية:' }}
                </label>
                <input type="file" name="hero_cover_image" accept="image/png,image/jpeg,image/webp" class="w-full rounded-xl border border-slate-300 p-2 bg-white text-xs">
                <span class="text-[11px] text-slate-400 block mt-1">يُفضل استخدام صورة أفقية بانورامية بدقة 1920×800 بكسل لتحقيق أفضل مظهر.</span>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">الشارة الترويجية العلوية (Badge)</label>
                    <input type="text" name="hero_badge" value="{{ old('hero_badge', $settings['hero_badge'] ?? '✨ بوابتك لحجز أرقى الفنادق والمنتجعات السياحية') }}" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">العنوان الرئيسي الأول <span class="text-red-500">*</span></label>
                        <input type="text" name="hero_title" value="{{ old('hero_title', $settings['hero_title'] ?? 'إقامتك المثالية تبدأ هنا') }}" required class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">العنوان المميز باللون الذهبي</label>
                        <input type="text" name="hero_subtitle" value="{{ old('hero_subtitle', $settings['hero_subtitle'] ?? 'بأعلى معايير الضيافة العالمية') }}" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">النص الوصفي الكامل أسفل العنوان</label>
                    <textarea name="hero_description" rows="3" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">{{ old('hero_description', $settings['hero_description'] ?? 'اختر من بين أفخم الفنادق والوجهات السياحية، استعرض الغرف المتوفرة لحظياً، واستمتع بخيارات تسعير منفصلة بالليرة السورية والدولار الأمريكي مع تأكيد فوري للحجز.') }}</textarea>
                </div>
            </div>
        </div>

        {{-- 3. SEO & Meta Tags Card --}}
        <div class="card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <h2 class="text-lg font-extrabold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>🌐</span>
                <span>تهيئة محركات البحث (SEO & Meta Tags)</span>
            </h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">عنوان الميتا (Meta Title)</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title', $settings['meta_title'] ?? 'حجز وإدارة الفنادق في سوريا') }}" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">وصف الميتا لمحركات البحث (Meta Description)</label>
                    <textarea name="meta_description" rows="2" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">{{ old('meta_description', $settings['meta_description'] ?? 'منصة متكاملة لحجز أرقى الفنادق والمنتجعات السياحية في المحافظات السورية') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">الكلمات المفتاحية للميتا (Meta Keywords)</label>
                    <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $settings['meta_keywords'] ?? 'حجز فنادق, فنادق دمشق, فنادق حلب, فنادق اللاذقية, سياحة سوريا') }}" placeholder="مفصولة بفواصل" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">نص حقوق الملكية في الفوتر</label>
                    <input type="text" name="footer_text" value="{{ old('footer_text', $settings['footer_text'] ?? '© 2026 جميع الحقوق محفوظة | منصة إدارة وحجز الفنادق') }}" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('platform.dashboard') }}" class="btn btn-light px-4 py-2 text-sm font-bold">إلغاء</a>
            <button type="submit" class="btn btn-primary px-6 py-2.5 text-sm font-bold shadow-sm">
                💾 حفظ كافة الإعدادات والمظهر
            </button>
        </div>
    </form>
</div>
@endsection

