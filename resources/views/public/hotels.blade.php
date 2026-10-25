@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">

    {{-- Hero Banner Section --}}
    @php
        $heroCover = \App\Models\SiteSetting::getImageUrl('hero_cover_image');
        $heroBadge = \App\Models\SiteSetting::get('hero_badge', '✨ بوابتك لحجز أرقى الفنادق والمنتجعات السياحية');
        $heroTitle = \App\Models\SiteSetting::get('hero_title', 'إقامتك المثالية تبدأ هنا');
        $heroSubtitle = \App\Models\SiteSetting::get('hero_subtitle', 'بأعلى معايير الضيافة العالمية');
        $heroDesc = \App\Models\SiteSetting::get('hero_description', 'اختر من بين أفخم الفنادق والوجهات السياحية، استعرض الغرف المتوفرة لحظياً، واستمتع بخيارات تسعير منفصلة بالليرة السورية والدولار الأمريكي مع تأكيد فوري للحجز.');
    @endphp

    <style>
        @keyframes heroKenBurns1 {
            0% { opacity: 0; transform: scale(1); }
            8% { opacity: 1; }
            28% { opacity: 1; transform: scale(1.08); }
            36% { opacity: 0; transform: scale(1.12); }
            100% { opacity: 0; }
        }
        @keyframes heroKenBurns2 {
            0%, 28% { opacity: 0; transform: scale(1); }
            36% { opacity: 1; }
            58% { opacity: 1; transform: scale(1.08); }
            66% { opacity: 0; transform: scale(1.12); }
            100% { opacity: 0; }
        }
        @keyframes heroKenBurns3 {
            0%, 58% { opacity: 0; transform: scale(1); }
            66% { opacity: 1; }
            88% { opacity: 1; transform: scale(1.08); }
            96% { opacity: 0; transform: scale(1.12); }
            100% { opacity: 0; }
        }
        .hero-slide-1 { animation: heroKenBurns1 24s infinite ease-in-out; }
        .hero-slide-2 { animation: heroKenBurns2 24s infinite ease-in-out; }
        .hero-slide-3 { animation: heroKenBurns3 24s infinite ease-in-out; }
    </style>

    <section class="relative bg-gradient-to-bl from-slate-950 via-slate-900 to-blue-950 text-white pt-16 pb-28 px-4 overflow-hidden min-h-[580px] flex items-center">
        {{-- Background Video Layer --}}
        <video autoplay loop muted playsinline class="absolute inset-0 h-full w-full object-cover opacity-35 pointer-events-none" poster="{{ $heroCover ?: 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1920&q=80' }}">
            <source src="https://assets.mixkit.co/videos/preview/mixkit-luxury-resort-with-swimming-pool-and-palm-trees-39722-large.mp4" type="video/mp4">
        </video>

        {{-- Background Ken-Burns Animated Visual Showcase (Fallback / Supplement) --}}
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="hero-slide-1 absolute inset-0 bg-cover bg-center opacity-30 mix-blend-overlay" style="background-image: url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1920&q=80');"></div>
            <div class="hero-slide-2 absolute inset-0 bg-cover bg-center opacity-30 mix-blend-overlay" style="background-image: url('https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1920&q=80');"></div>
            <div class="hero-slide-3 absolute inset-0 bg-cover bg-center opacity-30 mix-blend-overlay" style="background-image: url('https://images.unsplash.com/photo-1582719508461-905c673771fd?auto=format&fit=crop&w=1920&q=80');"></div>
        </div>

        {{-- Dark Luxury Overlay & Ambient Glow Gradients --}}
        <div class="absolute inset-0 bg-gradient-to-b from-slate-950/80 via-slate-950/70 to-slate-950/90 backdrop-blur-[2px]"></div>
        <div class="absolute inset-0 opacity-20 pointer-events-none">
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-blue-500 blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 rounded-full bg-amber-500 blur-3xl"></div>
            <div class="absolute inset-0" style="background-image: radial-gradient(rgba(255,255,255,0.08) 1px, transparent 1px); background-size: 24px 24px;"></div>
        </div>

        <div class="relative mx-auto max-w-6xl text-center z-10 w-full">
            {{-- Floating Promo Badge --}}
            @if($heroBadge)
                <div class="inline-flex items-center gap-2 rounded-full border border-blue-400/30 bg-blue-500/15 px-4 py-1.5 text-xs font-semibold text-blue-300 backdrop-blur-md mb-6 shadow-sm">
                    <span class="inline-block animate-pulse">✨</span>
                    <span>{{ $heroBadge }}</span>
                </div>
            @endif

            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight text-white mb-4">
                {{ $heroTitle }}
                @if($heroSubtitle)
                    <br>
                    <span class="bg-gradient-to-r from-amber-300 via-amber-200 to-yellow-400 bg-clip-text text-transparent">
                        {{ $heroSubtitle }}
                    </span>
                @endif
            </h1>

            @if($heroDesc)
                <p class="mx-auto max-w-3xl text-slate-300 text-sm sm:text-base leading-relaxed mb-6 font-medium">
                    {{ $heroDesc }}
                </p>
            @endif

            {{-- Feature Showcase Chips --}}
            <div class="flex flex-wrap items-center justify-center gap-2 mb-8 text-xs text-slate-300">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 border border-white/10 backdrop-blur-md">
                    <span class="text-emerald-400">●</span> حجز فوري مؤكد
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 border border-white/10 backdrop-blur-md">
                    <span class="text-blue-400">●</span> تسعير منفصل بالليرة السورية والدولار
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 border border-white/10 backdrop-blur-md">
                    <span class="text-amber-400">●</span> فواتير بباركود إلكتروني لمجلد الفندق
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 border border-white/10 backdrop-blur-md">
                    <span class="text-purple-400">●</span> تطبيق سطح مكتب + ويب متزامن
                </span>
            </div>

            {{-- Call To Action Button Group (Demo, Pricing, Search) --}}
            <div class="flex flex-wrap items-center justify-center gap-3">
                {{-- Live Demo Button --}}
                <a href="{{ route('demo.enter') }}" class="group relative inline-flex items-center gap-3 px-6 py-3.5 rounded-2xl bg-gradient-to-r from-amber-500 via-amber-400 to-yellow-500 text-slate-950 font-black text-sm sm:text-base shadow-xl shadow-amber-500/30 hover:shadow-amber-500/50 hover:scale-105 active:scale-95 transition-all duration-300 text-decoration-none">
                    <span class="inline-flex items-center justify-center h-7 w-7 rounded-xl bg-slate-950 text-amber-300 text-xs shadow-inner group-hover:rotate-12 transition">⚡</span>
                    <div class="text-end">
                        <span class="block leading-tight font-black text-slate-950">تجربة النظام الحي (Demo)</span>
                        <span class="block text-[11px] font-bold text-slate-900/80">دخول فوري بدون تسجيل للوحة فندق</span>
                    </div>
                    <span class="text-xs transition-transform group-hover:-translate-x-1">➔</span>
                </a>

                {{-- Pricing & Subscriptions Button --}}
                <a href="{{ route('pricing') }}" class="inline-flex items-center gap-2 px-5 py-3.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-sm sm:text-base backdrop-blur-md border border-white/20 hover:border-white/40 transition-all duration-200 text-decoration-none">
                    <span>💳</span>
                    <span>خطط وباقات الاشتراك</span>
                </a>

                {{-- Hotel Booking Direct Button --}}
                <a href="#bookingSearchForm" class="inline-flex items-center gap-2 px-5 py-3.5 rounded-2xl bg-blue-600/80 hover:bg-blue-600 text-white font-bold text-sm sm:text-base backdrop-blur-md border border-blue-400/30 transition-all duration-200 text-decoration-none">
                    <span>🏨</span>
                    <span>ابحث واحجز فندقك الآن</span>
                </a>
            </div>

            {{-- Safe Sandbox Guarantee Note --}}
            <div class="mt-4 inline-flex items-center gap-2 text-xs text-amber-200/90 font-medium px-4 py-1.5 rounded-full bg-slate-900/70 border border-amber-400/20 backdrop-blur-md">
                <span class="text-emerald-400 font-bold">✓</span>
                <span>بيئة تجريبية معزولة: يمكنك اختبار إدارة الغرف والحجوزات والفواتير بكل حرية، وتُحذف كافة بيانات التجربة تلقائياً عند الخروج.</span>
            </div>
        </div>
    </section>

    {{-- Floating Master Search Engine Card (Booking.com Luxury Style) --}}
    <div class="relative -mt-16 mx-auto max-w-6xl px-4 z-20">
        <div class="rounded-3xl border border-slate-200/90 bg-white p-4 sm:p-6 shadow-2xl backdrop-blur-xl">
            <form method="GET" action="{{ route('hotels.list') }}" id="bookingSearchForm" class="space-y-4">
                
                {{-- Row 1: Core Search Fields --}}
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6 items-end">
                    {{-- 1. Keyword / Destination Search --}}
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            🔍 الوجهة أو اسم الفندق
                        </label>
                        <div class="relative">
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="اسم الفندق، العنوان، أو المعلم..." class="w-full rounded-2xl border border-slate-300 bg-slate-50/80 px-4 py-3 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 transition">
                        </div>
                    </div>

                    {{-- 2. Province Filter --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            📍 المحافظة / المدينة
                        </label>
                        <select name="province" class="w-full rounded-2xl border border-slate-300 bg-slate-50/80 px-3 py-3 text-sm font-medium text-slate-900 focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 transition">
                            <option value="all">كل المحافظات</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province }}" @selected(request('province') == $province)>
                                    {{ $province }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 3. Check-in Date --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            📅 تاريخ الوصول
                        </label>
                        <input type="date" name="check_in" id="searchCheckIn" value="{{ request('check_in', $checkIn) }}" min="{{ date('Y-m-d') }}" class="w-full rounded-2xl border border-slate-300 bg-slate-50/80 px-3 py-3 text-sm font-medium text-slate-900 focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 transition">
                    </div>

                    {{-- 4. Check-out Date --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            📅 تاريخ المغادرة
                        </label>
                        <input type="date" name="check_out" id="searchCheckOut" value="{{ request('check_out', $checkOut) }}" min="{{ request('check_in') ? date('Y-m-d', strtotime(request('check_in') . ' +1 day')) : date('Y-m-d', strtotime('+1 day')) }}" class="w-full rounded-2xl border border-slate-300 bg-slate-50/80 px-3 py-3 text-sm font-medium text-slate-900 focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 transition">
                    </div>

                    {{-- 5. Search Action Button --}}
                    <div>
                        <button type="submit" class="w-full rounded-2xl bg-gradient-to-r from-blue-700 to-indigo-700 hover:from-blue-800 hover:to-indigo-800 py-3.5 px-4 text-sm font-bold text-white shadow-lg shadow-blue-700/25 transition-all transform active:scale-95 flex items-center justify-center gap-1.5">
                            <span>🔍</span>
                            <span>بحث وتأكيد</span>
                        </button>
                    </div>
                </div>

                {{-- Row 2: Booking.com Advanced Sorting & Filter Badges --}}
                <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs">
                    
                    {{-- Sort Dropdown --}}
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-600">ترتيب النتائج:</span>
                        <select name="sort" onchange="this.form.submit()" class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-800 focus:bg-white focus:border-blue-600 focus:outline-none">
                            <option value="name" @selected(request('sort') == 'name')>الافتراضي (حسب الاسم)</option>
                            <option value="price_asc" @selected(request('sort') == 'price_asc')>💰 السعر: من الأقل إلى الأعلى</option>
                            <option value="price_desc" @selected(request('sort') == 'price_desc')>💎 السعر: من الأعلى إلى الأقل</option>
                            <option value="rating_desc" @selected(request('sort') == 'rating_desc')>⭐ تقييم النزلاء: الأعلى تقييماً</option>
                            <option value="stars_desc" @selected(request('sort') == 'stars_desc')>🌟 التصنيف: الأكثر نجوماً</option>
                        </select>
                    </div>

                    {{-- Quick Amenity / Conditions Filter --}}
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-bold text-slate-600">شروط ومرافق:</span>
                        @php
                            $amenityOptions = [
                                'واي فاي' => '📶 واي فاي',
                                'مسبح' => '🏊‍♂️ مسبح',
                                'إفطار' => '🍳 إفطار فاخر',
                                'مواقف' => '🚗 مواقف سيارات',
                                'تكييف' => '❄️ تكييف مركزي',
                            ];
                        @endphp
                        @foreach($amenityOptions as $key => $label)
                            <button type="submit" name="amenity" value="{{ request('amenity') == $key ? '' : $key }}" class="rounded-full px-3 py-1 border transition text-xs font-semibold {{ request('amenity') == $key ? 'bg-blue-700 text-white border-blue-700 shadow-sm' : 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Reset Button --}}
                    @if(request()->hasAny(['q', 'province', 'stars', 'check_in', 'check_out', 'sort', 'amenity']))
                        <div>
                            <a href="{{ route('hotels.list') }}" class="text-rose-600 hover:text-rose-800 font-bold flex items-center gap-1 transition">
                                <span>🔄</span>
                                <span>إعادة تعيين الفلاتر</span>
                            </a>
                        </div>
                    @endif
                </div>

            </form>
        </div>
    </div>

    {{-- Value Props Ribbon --}}
    <section class="mx-auto max-w-6xl px-4 py-12">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="flex items-center gap-3.5 rounded-2xl bg-white p-4 border border-slate-200/80 shadow-sm">
                <span class="text-2xl p-2.5 rounded-xl bg-blue-50 text-blue-600">🛡️</span>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm">ضمان أفضل الأسعار</h4>
                    <p class="text-xs text-slate-500 mt-0.5">أسعار مباشرة وبدون عمولات وسيطة</p>
                </div>
            </div>

            <div class="flex items-center gap-3.5 rounded-2xl bg-white p-4 border border-slate-200/80 shadow-sm">
                <span class="text-2xl p-2.5 rounded-xl bg-emerald-50 text-emerald-600">💱</span>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm">تسعير بالعملتين</h4>
                    <p class="text-xs text-slate-500 mt-0.5">بالليرة السورية والدولار الأمريكي</p>
                </div>
            </div>

            <div class="flex items-center gap-3.5 rounded-2xl bg-white p-4 border border-slate-200/80 shadow-sm">
                <span class="text-2xl p-2.5 rounded-xl bg-amber-50 text-amber-600">⚡</span>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm">تأكيد حجز فوري</h4>
                    <p class="text-xs text-slate-500 mt-0.5">تحديث مباشر لتوافر الغرف</p>
                </div>
            </div>

            <div class="flex items-center gap-3.5 rounded-2xl bg-white p-4 border border-slate-200/80 shadow-sm">
                <span class="text-2xl p-2.5 rounded-xl bg-purple-50 text-purple-600">🛎️</span>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm">خدمة استقبال 24/7</h4>
                    <p class="text-xs text-slate-500 mt-0.5">جاهزون لخدمتكم على مدار الساعة</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Hotels Listing Section --}}
    <section class="mx-auto max-w-6xl px-4 pb-16">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
            <div>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">الفنادق والمنتجعات المتاحة</h2>
                <p class="text-sm text-slate-500 mt-1">
                    تم العثور على <strong class="text-slate-800 font-bold">{{ $hotels->count() }}</strong> فندق وفق معايير البحث الحالية
                    @if(request('check_in') && request('check_out'))
                        <span class="inline-block bg-blue-100 text-blue-800 px-2.5 py-0.5 rounded-full font-bold text-xs ms-1">
                            متاحة للإقامة من {{ request('check_in') }} إلى {{ request('check_out') }}
                        </span>
                    @endif
                </p>
            </div>

            {{-- Quick Filter Pills --}}
            <div class="flex flex-wrap items-center gap-2">
                @php
                    $queryParams = request()->except('stars');
                @endphp
                <a href="{{ route('hotels.list', $queryParams) }}" class="rounded-full px-4 py-1.5 text-xs font-bold border transition {{ !request('stars') ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50' }}">
                    جميع التصنيفات
                </a>
                <a href="{{ route('hotels.list', array_merge($queryParams, ['stars' => 5])) }}" class="rounded-full px-4 py-1.5 text-xs font-bold border transition {{ request('stars') == 5 ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50' }}">
                    ⭐⭐⭐⭐⭐ 5 نجوم
                </a>
                <a href="{{ route('hotels.list', array_merge($queryParams, ['stars' => 4])) }}" class="rounded-full px-4 py-1.5 text-xs font-bold border transition {{ request('stars') == 4 ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50' }}">
                    ⭐⭐⭐⭐ 4 نجوم
                </a>
                <a href="{{ route('hotels.list', array_merge($queryParams, ['stars' => 3])) }}" class="rounded-full px-4 py-1.5 text-xs font-bold border transition {{ request('stars') == 3 ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50' }}">
                    ⭐⭐⭐ 3 نجوم
                </a>
            </div>
        </div>

        {{-- Hotel Cards Grid --}}
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($hotels as $hotel)
                @php
                    $rooms = $hotel->rooms ?? collect();
                    $minPriceSYP = $rooms->min('price');
                    $minPriceUSD = $rooms->where('price_usd', '>', 0)->min('price_usd');
                    $availableRooms = $rooms->where('status', 'available')->count();
                    $rating = $hotel->reviews_avg_rating ? round((float)$hotel->reviews_avg_rating, 1) : null;
                @endphp

                <div class="group relative flex flex-col overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                    {{-- Cover Image & Badges --}}
                    <div class="relative h-56 w-full overflow-hidden bg-slate-100">
                        @if($hotel->cover_image_url)
                            <img src="{{ $hotel->cover_image_url }}" alt="{{ $hotel->name }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-800 to-slate-900 text-slate-400 text-4xl">
                                🏨
                            </div>
                        @endif

                        {{-- Gradient Overlay --}}
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/20"></div>

                        {{-- Stars & Province Badges --}}
                        <div class="absolute top-3 right-3 flex items-center gap-1.5">
                            <span class="rounded-full bg-slate-900/80 backdrop-blur-md px-3 py-1 text-xs font-bold text-amber-300 border border-white/10 shadow">
                                @for($i = 0; $i < ($hotel->stars ?: 4); $i++)★@endfor
                            </span>
                        </div>

                        <div class="absolute top-3 left-3">
                            <span class="rounded-full bg-white/90 backdrop-blur-md px-3 py-1 text-xs font-bold text-slate-800 shadow">
                                📍 {{ $hotel->province ?: 'سوريا' }}
                            </span>
                        </div>

                        {{-- Hotel Logo Avatar --}}
                        @if($hotel->logo_url)
                            <div class="absolute -bottom-4 right-5 h-14 w-14 rounded-2xl border-2 border-white bg-white p-1 shadow-lg overflow-hidden">
                                <img src="{{ $hotel->logo_url }}" alt="{{ $hotel->name }}" class="h-full w-full object-contain">
                            </div>
                        @endif
                    </div>

                    {{-- Card Body --}}
                    <div class="flex flex-1 flex-col p-5 pt-6">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 group-hover:text-blue-700 transition">
                                    {{ $hotel->name }}
                                </h3>
                                @if($hotel->tagline)
                                    <p class="text-xs font-semibold text-blue-700 mt-0.5 flex items-center gap-1">
                                        <span>✨</span>
                                        <span>{{ $hotel->tagline }}</span>
                                    </p>
                                @endif
                                <p class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                                    <span>📍</span>
                                    <span>{{ Str::limit($hotel->address ?: $hotel->province, 42) }}</span>
                                </p>
                            </div>

                            {{-- Rating Badge --}}
                            <div class="text-left shrink-0">
                                @if($rating)
                                    <div class="flex items-center gap-1">
                                        <span class="rounded-xl bg-blue-700 px-2.5 py-1 text-xs font-extrabold text-white shadow-sm">
                                            {{ $rating }}
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 mt-0.5 block">
                                        {{ $hotel->reviews_count }} تقييم
                                    </span>
                                @else
                                    <span class="rounded-xl bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 border border-emerald-200">
                                        مميز جديد
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Hotel Amenities Chips (Dynamic) --}}
                        @php $hotelAmenities = $hotel->amenities_list; @endphp
                        @if(!empty($hotelAmenities))
                            <div class="my-3 flex flex-wrap gap-1.5 text-[11px] text-slate-600 font-medium">
                                @foreach(array_slice($hotelAmenities, 0, 4) as $amenity)
                                    <span class="rounded-lg bg-slate-100 px-2.5 py-1">{{ $amenity }}</span>
                                @endforeach
                                @if(count($hotelAmenities) > 4)
                                    <span class="rounded-lg bg-blue-50 text-blue-700 font-bold px-2 py-1 text-[10px]">+{{ count($hotelAmenities) - 4 }}</span>
                                @endif
                            </div>
                        @endif

                        @if($hotel->description)
                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed mb-4">
                                {{ $hotel->description }}
                            </p>
                        @endif

                        {{-- Pricing and Action Section --}}
                        <div class="mt-auto border-t border-slate-100 pt-4">
                            <div class="flex items-end justify-between gap-3 mb-4">
                                <div>
                                    <span class="text-[11px] text-slate-500 block font-medium">يبدأ سعر الإقامة من:</span>
                                    @if($minPriceSYP)
                                        <div class="text-lg font-black text-slate-900 leading-tight">
                                            {{ number_format((float) $minPriceSYP) }} <span class="text-xs font-bold text-blue-700">ل.س</span>
                                            <span class="text-[10px] font-normal text-slate-400">/ ليلة</span>
                                        </div>
                                    @else
                                        <div class="text-sm font-bold text-slate-600">أسعار الغرف حسب التوافر</div>
                                    @endif

                                    @if($minPriceUSD && (float)$minPriceUSD > 0)
                                        <div class="text-xs font-bold text-emerald-700 mt-0.5">
                                            ${{ number_format((float) $minPriceUSD, 2) }} <span class="text-[10px]">USD / ليلة</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="text-left">
                                    @if($availableRooms > 0)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            {{ $availableRooms }} غرف متاحة
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 bg-slate-100 px-2 py-1 rounded-full">
                                            استعلم للتوافر
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Dual Actions: Book Rooms vs Hotel Profile --}}
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('hotel.rooms', array_filter(['hotelSlug' => $hotel->slug, 'check_in' => request('check_in'), 'check_out' => request('check_out')])) }}" class="rounded-xl bg-slate-900 hover:bg-slate-800 text-center py-2.5 px-3 text-xs font-bold text-white shadow-sm transition">
                                    🛎️ حجز الغرف
                                </a>
                                <a href="{{ route('hotel.front', $hotel->slug) }}" class="rounded-xl border border-slate-300 hover:bg-slate-50 text-center py-2.5 px-3 text-xs font-bold text-slate-700 transition">
                                    استكشف الفندق
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
                    <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-3xl">
                        🔍
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">لا توجد فنادق مطابقة لمعايير البحث الحالية</h3>
                    <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">
                        يرجى تجربة تغيير المحافظة، إزالة كلمات البحث، أو استعراض جميع الفنادق المسجلة في النظام.
                    </p>
                    <div class="mt-5">
                        <a href="{{ route('hotels.list') }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-blue-800 transition">
                            عرض جميع الفنادق
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Popular Syrian Destinations Section (Dynamic from Platform Admin) --}}
    @if(isset($destinations) && $destinations->isNotEmpty())
    <section class="border-t border-slate-200/80 bg-white py-16 px-4">
        <div class="mx-auto max-w-6xl">
            <div class="text-center mb-10">
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600 block mb-1">وجهات مميزة للإقامة والسياحة</span>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900">أشهر المدن والمحافظات السياحية</h2>
                <p class="text-sm text-slate-500 mt-1">اختر وجهتك المفضلة واستعرض الفنادق المتاحة فيها فوراً</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($destinations as $dest)
                    @php
                        $targetUrl = $dest->link ?: route('hotels.list', ['province' => $dest->title]);
                    @endphp
                    <a href="{{ $targetUrl }}" class="group relative overflow-hidden rounded-2xl h-48 bg-slate-900 block shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        @if($dest->image_url)
                            <img src="{{ $dest->image_url }}" alt="{{ $dest->title }}" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105 opacity-80">
                        @endif

                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-black/10 z-10"></div>

                        @if(!$dest->image_url)
                            <div class="absolute inset-0 flex items-center justify-center text-4xl group-hover:scale-110 transition duration-500 z-10">
                                {{ $dest->icon ?: '🏛️' }}
                            </div>
                        @endif

                        <div class="absolute bottom-3 right-3 left-3 z-20 text-white">
                            <h4 class="font-extrabold text-base flex items-center gap-1.5">
                                @if($dest->icon && $dest->image_url) <span>{{ $dest->icon }}</span> @endif
                                <span>{{ $dest->title }}</span>
                            </h4>
                            @if($dest->subtitle)
                                <span class="text-xs text-slate-200 line-clamp-1 mt-0.5">{{ $dest->subtitle }}</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Apps Download Center Section (Windows, Android, Apple) --}}
    <section id="downloads" class="relative border-t border-slate-200/80 bg-gradient-to-b from-slate-50 via-white to-blue-50/40 py-20 px-4 overflow-hidden">
        {{-- Decorative background glow --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[350px] bg-gradient-to-r from-blue-400/10 via-indigo-400/10 to-amber-400/10 blur-3xl pointer-events-none rounded-full"></div>

        <div class="relative mx-auto max-w-6xl">
            {{-- Header --}}
            <div class="text-center max-w-2xl mx-auto mb-14">
                <div class="inline-flex items-center gap-2 rounded-full border border-blue-500/20 bg-blue-500/10 px-4 py-1.5 text-xs font-black text-blue-700 dark:text-blue-400 mb-3">
                    <span>📲 تطبيقات نظام الفنادق الرسمية</span>
                </div>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight mb-3">
                    حمّل تطبيقات النظام للكمبيوتر والجوال
                </h2>
                <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                    منظومة متكاملة تلبي احتياجات موظفي وإدارات الفنادق على أجهزة الكمبيوتر، وتمنح النزلاء والزوار تجربة حجز سلسة تحاكي منصات الحجز العالمية على هواتفهم الذكية.
                </p>
            </div>

            {{-- 3 Download Cards Grid --}}
            <div class="grid gap-6 md:grid-cols-3 items-stretch">

                {{-- 1. Windows Desktop App (.exe) --}}
                <div class="group relative flex flex-col justify-between rounded-3xl border-2 border-blue-200 bg-white p-7 shadow-lg hover:shadow-2xl hover:border-blue-500 transition-all duration-300">
                    <div>
                        {{-- Top Badge & Logo --}}
                        <div class="flex items-start justify-between mb-5">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 p-3 group-hover:scale-110 transition duration-300 shadow-inner">
                                <svg class="w-10 h-10 text-blue-600" viewBox="0 0 88 88" fill="currentColor">
                                    <path d="M0 12.402l35.689-4.86.016 34.423-35.67.203zm35.67 33.529l.026 34.453L0 75.58v-30.05zm4.327-39.045L87.986 0v41.524l-47.989.317zm0 43.565l47.989.316V88l-47.989-6.758z"/>
                                </svg>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-[11px] font-black text-blue-800">
                                للإدارة والموظفين 💻
                            </span>
                        </div>

                        <h3 class="text-xl font-black text-slate-900 mb-2">تطبيق الكمبيوتر (Windows)</h3>
                        <p class="text-xs text-slate-600 leading-relaxed mb-4">
                            لوحة تحكم سطح المكتب فائقة السرعة للمدراء، موظفي الاستقبال، المحاسبين، فرق الصيانة والتنظيف. تدعم الصلاحيات المخصصة وفصل العملات الدقيق بالليرة والدولار.
                        </p>

                        {{-- Staff-only Alert Notice --}}
                        <div class="rounded-2xl border border-amber-200 bg-amber-50/80 p-3 text-[11px] text-amber-900 mb-5 leading-normal">
                            <span class="font-bold">🔒 تنبيه تسجيل الدخول:</span> مخصص لطاقم الفنادق حصراً. حسابات الزوار لا تفتح التطبيق. للتسجيل راجع مسؤول النظام.
                        </div>
                    </div>

                    {{-- Download Actions --}}
                    <div class="space-y-2.5 pt-2">
                        <a href="{{ route('downloads.windows') }}" class="flex w-full items-center justify-center gap-2.5 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-black text-white shadow-md hover:bg-blue-700 hover:shadow-lg transition">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M13 8V2H7v6H2l8 8 8-8h-5zM0 18h20v2H0v-2z"/></svg>
                            <span>تحميل نسخة ويندوز (.zip مجمعة)</span>
                        </a>

                        <a href="{{ route('downloads.windows-exe') }}" class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-white hover:text-blue-600 transition">
                            <span>تحميل المشغل المباشر (.exe)</span>
                        </a>

                        <div class="flex items-center justify-between text-[10px] text-slate-400 px-1 pt-1">
                            <span>الإصدار: v1.0.0 Release</span>
                            <span>Windows 10 / 11 (64-bit)</span>
                        </div>
                    </div>
                </div>

                {{-- 2. Android Mobile App (.apk) --}}
                <div class="group relative flex flex-col justify-between rounded-3xl border-2 border-emerald-300 bg-white p-7 shadow-xl hover:shadow-2xl hover:border-emerald-500 transition-all duration-300 ring-2 ring-emerald-500/10">
                    {{-- Highlight banner --}}
                    <div class="absolute -top-3.5 right-6 rounded-full bg-gradient-to-r from-emerald-600 to-teal-600 px-3.5 py-0.5 text-[10px] font-black text-white shadow-sm uppercase tracking-wider">
                        ⭐ متاح للجميع مجاناً
                    </div>

                    <div>
                        {{-- Top Badge & Logo --}}
                        <div class="flex items-start justify-between mb-5">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 p-3 group-hover:scale-110 transition duration-300 shadow-inner">
                                <svg class="w-10 h-10 text-emerald-600" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1 0-.5514.4482-1 .9993-1C18.075 13.3414 18.523 13.79 18.523 14.3414c0 .5514-.448 1-.9993 1m-11.046 0c-.5511 0-.9993-.4486-.9993-1 0-.5514.4482-1 .9993-1C7.028 13.3414 7.476 13.79 7.476 14.3414c0 .5514-.448 1-.999 1m11.405-6.02l1.997-3.46a.416.416 0 0 0-.152-.567.416.416 0 0 0-.568.152l-2.022 3.503c-1.282-.585-2.716-.913-4.238-.913-1.521 0-2.955.328-4.237.913L6.638 5.4464a.415.415 0 0 0-.568-.152.416.416 0 0 0-.152.567l1.997 3.46C3.688 11.4864 1 15.6564 1 20.4864h22c0-4.83-2.688-9-6.882-11.165"/>
                                </svg>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-black text-emerald-800">
                                للنزلاء والزوار 📱
                            </span>
                        </div>

                        <h3 class="text-xl font-black text-slate-900 mb-2">تطبيق الأندرويد (Android APK)</h3>
                        <p class="text-xs text-slate-600 leading-relaxed mb-4">
                            تصميم بوكينغ العالمي الأنيق. تصفح الفنادق والغرف بدون تسجيل مسبق، أو سجّل ببريدك الإلكتروني واستمتع بحجز فوري، نظام شات تفاعلي مباشر مع إدارة الفندق، وإشعارات حية.
                        </p>

                        {{-- Features mini tags --}}
                        <div class="flex flex-wrap gap-1.5 mb-5">
                            <span class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-700">🏨 تصفح حر كالموقع</span>
                            <span class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-700">💬 شات تفاعلي موحد</span>
                            <span class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-700">🔔 إشعارات لحظية</span>
                            <span class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-700">⚡ تسجيل جيميل وإيميل</span>
                        </div>
                    </div>

                    {{-- Download Action --}}
                    <div class="space-y-2.5 pt-2">
                        <a href="{{ route('downloads.android') }}" class="flex w-full items-center justify-center gap-2.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-3.5 text-sm font-black text-white shadow-lg shadow-emerald-600/25 hover:from-emerald-700 hover:to-teal-700 hover:shadow-xl transition">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M13 8V2H7v6H2l8 8 8-8h-5zM0 18h20v2H0v-2z"/></svg>
                            <span>تحميل ملف التثبيت المباشر (APK)</span>
                        </a>

                        <div class="flex items-center justify-between text-[10px] text-slate-400 px-1 pt-1">
                            <span>الحجم: 51.2 ميغابايت</span>
                            <span>Android 8.0 فما فوق</span>
                        </div>
                    </div>
                </div>

                {{-- 3. Apple iOS App (Coming Soon) --}}
                <div class="group relative flex flex-col justify-between rounded-3xl border border-slate-200 bg-white/70 p-7 shadow-sm opacity-90 hover:opacity-100 transition-all duration-300">
                    <div>
                        {{-- Top Badge & Logo --}}
                        <div class="flex items-start justify-between mb-5">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 p-3 group-hover:scale-105 transition duration-300">
                                <svg class="w-10 h-10 text-slate-800" viewBox="0 0 170 170" fill="currentColor">
                                    <path d="M150.37 130.25c-2.45 5.66-5.35 10.87-8.71 15.66-4.58 6.53-8.33 11.05-11.22 13.56-4.48 4.12-9.28 6.23-14.42 6.35-3.69 0-8.14-1.05-13.32-3.18-5.19-2.12-9.97-3.17-14.34-3.17-4.58 0-9.49 1.05-14.74 3.17-5.26 2.13-9.5 3.24-12.74 3.35-4.35.13-9.16-1.9-14.42-6.08-3.7-3.03-7.61-7.74-11.73-14.14-5.69-8.86-10.15-18.96-13.38-30.3-3.23-11.35-4.85-22.13-4.85-32.33 0-14.18 3.55-26.06 10.65-35.63 7.1-9.57 16.03-14.44 26.8-14.61 5.04 0 10.51 1.43 16.41 4.29 5.9 2.86 9.87 4.34 11.91 4.43 1.83 0 5.86-1.55 12.08-4.66 6.22-3.11 11.83-4.52 16.83-4.24 13.14.73 23.49 5.48 31.05 14.25-11.45 6.9-17.02 16.48-16.71 28.74.26 9.68 3.99 17.77 11.19 24.27 7.21 6.5 15.68 10.12 25.43 10.87-2.32 7.02-4.99 13.68-8.01 19.98zm-30.29-123.63c0 7.74-2.82 14.88-8.46 21.42-6.52 7.42-14.32 11.75-23.4 12.98-.38-1.52-.57-3.04-.57-4.56 0-7.74 2.97-15.17 8.91-22.3 3.14-3.78 7.04-6.84 11.69-9.18 4.65-2.34 9.09-3.64 13.32-3.9.19 1.84.28 3.55.28 5.14z"/>
                                </svg>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-[11px] font-black text-amber-900 border border-amber-300">
                                ⏳ قريباً
                            </span>
                        </div>

                        <h3 class="text-xl font-black text-slate-900 mb-2">تطبيق الآيفون (Apple iOS)</h3>
                        <p class="text-xs text-slate-600 leading-relaxed mb-4">
                            النسخة المخصصة لأجهزة iPhone و iPad قيد التحضير والرفع لمتجر App Store الرسمي، وتتضمن كافة ميزات الحجز السريع والمحادثة والإشعارات المباشرة.
                        </p>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-center mb-5">
                            <span class="text-2xl block mb-1">🍏</span>
                            <span class="text-xs font-bold text-slate-700">جاري الاعتماد على متجر Apple App Store</span>
                            <p class="text-[11px] text-slate-400 mt-0.5">ستتوفر النسخة للتنزيل قريباً جداً</p>
                        </div>
                    </div>

                    {{-- Disabled Action --}}
                    <div class="pt-2">
                        <button disabled class="flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-slate-200 px-4 py-3.5 text-sm font-black text-slate-400">
                            <span>متاح قريباً على App Store</span>
                        </button>
                        <div class="text-center text-[10px] text-slate-400 pt-2">
                            iPhone • iPad • iOS 15+
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkInInput = document.getElementById('searchCheckIn');
    const checkOutInput = document.getElementById('searchCheckOut');
    if (checkInInput && checkOutInput) {
        checkInInput.addEventListener('change', function () {
            if (this.value) {
                const nextDay = new Date(this.value);
                nextDay.setDate(nextDay.getDate() + 1);
                const nextDayFormatted = nextDay.toISOString().split('T')[0];
                checkOutInput.min = nextDayFormatted;
                if (!checkOutInput.value || checkOutInput.value <= this.value) {
                    checkOutInput.value = nextDayFormatted;
                }
            }
        });
    }
});
</script>
@endsection
