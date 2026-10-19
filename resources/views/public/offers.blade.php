@extends('layouts.app')

@section('content')
<div class="container my-4">
    {{-- Header Banner --}}
    <div class="relative bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-950 text-white rounded-3xl p-6 sm:p-8 mb-5 overflow-hidden shadow-xl border border-white/10">
        <div class="relative z-10 max-w-2xl">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30 text-xs font-bold mb-3 backdrop-blur-md">
                🎁 عروض وباقات حصرية
            </span>
            <h1 class="text-2xl sm:text-4xl font-black tracking-tight text-white mb-2 leading-tight">
                العروض والخصومات الفندقية الحالية
            </h1>
            <p class="text-xs sm:text-sm text-blue-100 leading-relaxed mb-0">
                استفد من أفضل الخصومات والأسعار المخفضة المقدمة مباشرة من الفنادق والمنتجعات المشتركة في المنصة، مع تأكيد فوري وخيارات دفع بالليرة السورية والدولار.
            </p>
        </div>
        <div class="absolute -bottom-10 -left-10 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    {{-- Offers Grid --}}
    @if($offers->isEmpty())
        <div class="card border-0 shadow-sm rounded-3xl p-5 text-center my-5 bg-white dark:bg-slate-800">
            <div class="text-5xl mb-3">🏷️</div>
            <h4 class="font-bold text-slate-800 dark:text-slate-100 mb-2">لا توجد عروض ترويجية نشطة حالياً</h4>
            <p class="text-xs text-slate-500 max-w-md mx-auto mb-4">
                ترقب قريباً أقوى العروض والخصومات الحصرية من شركائنا الفندقيين. يمكنك تصفح الفنادق المتاحة وحجز إقامتك الآن.
            </p>
            <div>
                <a href="{{ route('hotels.list') }}" class="btn btn-primary rounded-xl px-4 py-2 font-bold text-sm">
                    استعراض الفنادق المتاحة
                </a>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach($offers as $offer)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card border-0 shadow-md rounded-3xl overflow-hidden hover:shadow-xl transition-all bg-white dark:bg-slate-800 h-100 d-flex flex-column group">
                        {{-- Image & Discount Badge --}}
                        <div class="relative h-48 overflow-hidden bg-slate-900">
                            <img src="{{ $offer->image_url }}" alt="{{ $offer->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            
                            @if($offer->discount_percentage > 0)
                                <span class="absolute top-3 right-3 px-3 py-1 rounded-full bg-rose-600 text-white font-black text-xs shadow-md">
                                    خصم {{ (int)$offer->discount_percentage }}%
                                </span>
                            @endif

                            <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-3 d-flex align-items-center justify-content-between text-white text-xs">
                                <span class="font-bold truncate">{{ $offer->hotel?->name }}</span>
                                <span class="text-amber-400">
                                    @for($s = 0; $s < ($offer->hotel?->stars ?? 3); $s++) ⭐ @endfor
                                </span>
                            </div>
                        </div>

                        {{-- Body --}}
                        <div class="p-4 flex-grow-1 d-flex flex-column">
                            <h5 class="font-black text-base text-slate-900 dark:text-slate-100 mb-2 line-clamp-1">
                                {{ $offer->title }}
                            </h5>
                            
                            @if($offer->description)
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-3 flex-grow-1 line-clamp-2">
                                    {{ $offer->description }}
                                </p>
                            @endif

                            {{-- Validity Dates --}}
                            @if($offer->start_date || $offer->end_date)
                                <div class="text-[11px] text-slate-400 mb-3 d-flex align-items-center gap-1">
                                    <span>📅</span>
                                    <span>صالح حتى: <strong>{{ $offer->end_date?->format('Y-m-d') ?? 'لفترة محدودة' }}</strong></span>
                                </div>
                            @endif

                            {{-- Price Section --}}
                            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-700/60 mb-3 d-flex align-items-center justify-content-between">
                                <span class="text-xs font-bold text-slate-500">سعر العرض الخاص:</span>
                                <div class="text-end">
                                    @if($offer->special_price)
                                        <strong class="text-sm font-black text-emerald-600 dark:text-emerald-400 block">
                                            {{ number_format($offer->special_price, 2) }} ل.س
                                        </strong>
                                    @endif
                                    @if($offer->special_price_usd)
                                        <span class="text-xs font-bold text-blue-600 dark:text-blue-400">
                                            ${{ number_format($offer->special_price_usd, 2) }} USD
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- CTA Button --}}
                            <a href="{{ route('hotel.front', $offer->hotel?->slug ?? '') }}" class="btn btn-primary w-100 py-2.5 rounded-2xl font-bold text-xs shadow-md shadow-blue-600/20">
                                🛎️ احجز الآن من هذا الفندق
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $offers->links() }}
        </div>
    @endif
</div>
@endsection

