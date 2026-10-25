@php
    $siteFavicon = \App\Models\SiteSetting::getImageUrl('site_favicon'); 
    $siteLogo = \App\Models\SiteSetting::getImageUrl('site_logo');
    $siteIcon = \App\Models\SiteSetting::get('site_icon', '🏨');
    $siteName = \App\Models\SiteSetting::get('site_name', 'نظام إدارة وحجز الفنادق');
    $locale = $currentLocale ?? session('locale', 'ar');
    $isEn = ($locale === 'en');
    $dir = $isEn ? 'ltr' : 'rtl';
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $dir }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ \App\Models\SiteSetting::get('meta_title', config('app.name', $siteName)) }}</title>
    <meta name="description" content="{{ \App\Models\SiteSetting::get('meta_description') }}">
    <meta name="keywords" content="{{ \App\Models\SiteSetting::get('meta_keywords') }}">
    @if($siteFavicon)
        <link rel="icon" href="{{ $siteFavicon }}">
    @endif
    
    <!-- Immediate theme detector to prevent FOUC (Flash of Unstyled Content) -->
    <script>
        (function() {
            try {
                const savedTheme = localStorage.getItem('hotel_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-bs-theme', savedTheme);
                if (savedTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>
    
    <!-- Google Fonts: Tajawal for Arabic, Plus Jakarta Sans for English -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Tajawal:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap RTL for Arabic, LTR for English -->
    @if($isEn)
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @else
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @endif
    
    <!-- Tailwind CSS with Dark Mode Class support -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        darkbg: '#0b0f19',
                        darkcard: '#131b2e',
                        darkborder: '#1e293b',
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --app-font-ar: "Tajawal", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --app-font-en: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        html[dir="rtl"] body {
            font-family: var(--app-font-ar);
        }

        html[dir="ltr"] body {
            font-family: var(--app-font-en);
        }

        body {
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Modern Luxury Sticky Header */
        header.app-header {
            background: linear-gradient(135deg, #090d16 0%, #0f172a 40%, #1e1b4b 100%);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.45), 0 1px 0 rgba(255, 255, 255, 0.08) inset;
            position: sticky;
            top: 0;
            z-index: 1030;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Nav Dropdown Trigger Buttons */
        .nav-dropdown-btn {
            color: rgba(255, 255, 255, 0.92) !important;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            padding: 0.45rem 0.8rem;
            border-radius: 0.75rem;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            text-decoration: none;
            outline: none !important;
            white-space: nowrap;
        }

        .nav-dropdown-btn:hover,
        .nav-dropdown-btn[aria-expanded="true"],
        .nav-item.dropdown:hover > .nav-dropdown-btn {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.16);
            border-color: rgba(255, 255, 255, 0.35);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
            transform: translateY(-1px);
        }

        .nav-link-direct {
            color: rgba(255, 255, 255, 0.92) !important;
            text-decoration: none;
            padding: 0.45rem 0.8rem;
            border-radius: 0.75rem;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .nav-link-direct:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.14);
        }

        /* Modern Custom Dropdown Menus */
        .custom-dropdown-menu {
            border: 1px solid rgba(226, 232, 240, 0.9) !important;
            border-radius: 1.15rem !important;
            box-shadow: 0 20px 30px -5px rgba(0, 0, 0, 0.18), 0 10px 15px -6px rgba(0, 0, 0, 0.1) !important;
            padding: 0.6rem !important;
            min-width: 270px;
            background: #ffffff !important;
            margin-top: 0.5rem !important;
        }

        /* Desktop Hover Interaction */
        @media (min-width: 992px) {
            .nav-item.dropdown:hover > .custom-dropdown-menu {
                display: block !important;
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }
            .nav-item.dropdown > .custom-dropdown-menu {
                display: block;
                opacity: 0;
                visibility: hidden;
                transform: translateY(8px);
                transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            }
        }

        /* Dropdown Items with Rich Icons */
        .custom-dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.75rem;
            border-radius: 0.75rem;
            text-decoration: none;
            color: #1e293b;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            margin-bottom: 0.15rem;
        }

        .custom-dropdown-item:last-child {
            margin-bottom: 0;
        }

        .custom-dropdown-item:hover {
            background-color: #f8fafc;
            transform: translateX(4px);
        }
        html[dir="rtl"] .custom-dropdown-item:hover {
            transform: translateX(-4px);
        }

        .dropdown-item-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.65rem;
            font-size: 1.1rem;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }

        .custom-dropdown-item:hover .dropdown-item-icon {
            transform: scale(1.08);
        }

        .dropdown-item-title {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.25;
            transition: color 0.15s ease;
        }

        .dropdown-item-desc {
            display: block;
            font-size: 0.72rem;
            color: #64748b;
            margin-top: 0.15rem;
            line-height: 1.2;
        }

        .custom-dropdown-item:hover .dropdown-item-title {
            color: #1d4ed8;
        }

        /* Chevron indicator */
        .dropdown-chevron {
            display: inline-block;
            font-size: 0.65rem;
            opacity: 0.7;
            transition: transform 0.25s ease;
        }

        .nav-item.dropdown:hover .dropdown-chevron,
        .nav-dropdown-btn[aria-expanded="true"] .dropdown-chevron {
            transform: rotate(180deg);
        }

        /* ==========================================================================
           BEAUTIFUL SEGMENTED LANGUAGE SWITCHER
           ========================================================================== */
        .lang-segmented-pill {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 9999px;
            padding: 0.2rem;
            gap: 0.15rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .lang-pill-item {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.7);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            line-height: 1;
        }

        .lang-pill-item:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
        }

        .lang-pill-item.active {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* ==========================================================================
           BEAUTIFUL DARK / LIGHT MODE SWITCHER BUTTON
           ========================================================================== */
        .theme-toggle-btn {
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            outline: none !important;
            font-size: 1.1rem;
        }

        .theme-toggle-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.35);
            transform: scale(1.06);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        [data-bs-theme="dark"] .theme-icon-moon {
            display: none !important;
        }
        [data-bs-theme="dark"] .theme-icon-sun {
            display: inline-block !important;
            filter: drop-shadow(0 0 6px #f59e0b);
            animation: sunPulse 3s ease-in-out infinite;
        }

        [data-bs-theme="light"] .theme-icon-sun,
        :not([data-bs-theme="dark"]) .theme-icon-sun {
            display: none !important;
        }
        [data-bs-theme="light"] .theme-icon-moon,
        :not([data-bs-theme="dark"]) .theme-icon-moon {
            display: inline-block !important;
            filter: drop-shadow(0 0 5px #38bdf8);
        }

        @keyframes sunPulse {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(15deg); }
        }

        /* ==========================================================================
           HAMBURGER BUTTON FOR MOBILE & TABLET
           ========================================================================== */
        .mobile-menu-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.75rem;
            color: #ffffff;
            padding: 0.45rem 0.65rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.2s ease;
        }

        .mobile-menu-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        /* ==========================================================================
           OFFCANVAS MOBILE & IPAD DRAWER
           ========================================================================== */
        .mobile-drawer {
            background: linear-gradient(180deg, #090d16 0%, #0f172a 50%, #1e1b4b 100%) !important;
            color: #f8fafc !important;
            width: 330px !important;
            max-width: 88vw !important;
            border: none !important;
            box-shadow: -10px 0 35px rgba(0, 0, 0, 0.6) !important;
        }

        html[dir="ltr"] .mobile-drawer {
            box-shadow: 10px 0 35px rgba(0, 0, 0, 0.6) !important;
        }

        .mobile-drawer .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        .mobile-drawer .btn-close:hover {
            opacity: 1;
        }

        .mobile-category-title {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            margin-top: 1.25rem;
            margin-bottom: 0.5rem;
            padding-inline: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .mobile-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 0.85rem;
            border-radius: 0.85rem;
            color: #e2e8f0;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 0.45rem;
            transition: all 0.2s ease;
            min-height: 48px;
        }

        .mobile-nav-link:hover,
        .mobile-nav-link:active {
            background: rgba(255, 255, 255, 0.14);
            border-color: rgba(255, 255, 255, 0.25);
            color: #ffffff;
            transform: translateX(3px);
        }
        html[dir="rtl"] .mobile-nav-link:hover,
        html[dir="rtl"] .mobile-nav-link:active {
            transform: translateX(-3px);
        }

        /* ==========================================================================
           RESPONSIVE CONTAINER & TABLE WRAPPERS
           ========================================================================== */
        main {
            flex: 1 0 auto;
        }

        footer {
            flex-shrink: 0;
            background-color: #f1f5f9;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.875rem;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        .table-responsive {
            -webkit-overflow-scrolling: touch;
            overflow-x: auto;
            max-width: 100%;
            border-radius: 0.75rem;
        }

        @media (max-width: 991.98px) {
            main table:not(.table-responsive table) {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        /* ==========================================================================
           COMPREHENSIVE DARK MODE CSS OVERRIDES
           ========================================================================== */
        html[data-bs-theme="dark"] body,
        html.dark body {
            background-color: #0b0f19 !important;
            color: #e2e8f0 !important;
        }

        html[data-bs-theme="dark"] .card,
        html.dark .card {
            background-color: #131b2e !important;
            border-color: #1e293b !important;
            color: #f8fafc !important;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.45) !important;
        }

        html[data-bs-theme="dark"] .card-header,
        html.dark .card-header {
            background-color: #162032 !important;
            border-bottom-color: #1e293b !important;
            color: #f8fafc !important;
        }

        html[data-bs-theme="dark"] .card-footer,
        html.dark .card-footer {
            background-color: #162032 !important;
            border-top-color: #1e293b !important;
        }

        /* Dark Table Overrides */
        html[data-bs-theme="dark"] .table,
        html.dark .table {
            color: #cbd5e1 !important;
            border-color: #1e293b !important;
        }

        html[data-bs-theme="dark"] .table > :not(caption) > * > *,
        html.dark .table > :not(caption) > * > * {
            background-color: transparent !important;
            color: inherit !important;
            border-bottom-color: #1e293b !important;
        }

        html[data-bs-theme="dark"] .table thead th,
        html.dark .table thead th {
            background-color: #162032 !important;
            color: #94a3b8 !important;
            border-bottom-color: #334155 !important;
        }

        html[data-bs-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd) > *,
        html.dark .table-striped > tbody > tr:nth-of-type(odd) > * {
            background-color: rgba(255, 255, 255, 0.02) !important;
        }

        html[data-bs-theme="dark"] .table-hover > tbody > tr:hover > *,
        html.dark .table-hover > tbody > tr:hover > * {
            background-color: rgba(255, 255, 255, 0.05) !important;
        }

        /* Dark Form Inputs */
        html[data-bs-theme="dark"] .form-control,
        html[data-bs-theme="dark"] .form-select,
        html.dark .form-control,
        html.dark .form-select {
            background-color: #0b0f19 !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }

        html[data-bs-theme="dark"] .form-control:focus,
        html[data-bs-theme="dark"] .form-select:focus,
        html.dark .form-control:focus,
        html.dark .form-select:focus {
            background-color: #0f172a !important;
            border-color: #3b82f6 !important;
            color: #ffffff !important;
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25) !important;
        }

        html[data-bs-theme="dark"] .form-label,
        html.dark .form-label {
            color: #cbd5e1 !important;
        }

        /* Dark Dropdown Menu Overrides */
        html[data-bs-theme="dark"] .custom-dropdown-menu,
        html.dark .custom-dropdown-menu {
            background: #131b2e !important;
            border-color: #1e293b !important;
            box-shadow: 0 20px 35px -5px rgba(0, 0, 0, 0.7), 0 10px 15px -6px rgba(0, 0, 0, 0.5) !important;
        }

        html[data-bs-theme="dark"] .custom-dropdown-item,
        html.dark .custom-dropdown-item {
            color: #e2e8f0 !important;
        }

        html[data-bs-theme="dark"] .custom-dropdown-item:hover,
        html.dark .custom-dropdown-item:hover {
            background-color: #1e293b !important;
        }

        html[data-bs-theme="dark"] .dropdown-item-title,
        html.dark .dropdown-item-title {
            color: #f8fafc !important;
        }

        html[data-bs-theme="dark"] .dropdown-item-desc,
        html.dark .dropdown-item-desc {
            color: #94a3b8 !important;
        }

        /* Dark Modals */
        html[data-bs-theme="dark"] .modal-content,
        html.dark .modal-content {
            background-color: #131b2e !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }

        html[data-bs-theme="dark"] .modal-header,
        html[data-bs-theme="dark"] .modal-footer,
        html.dark .modal-header,
        html.dark .modal-footer {
            border-color: #1e293b !important;
        }

        /* Dark Footer */
        html[data-bs-theme="dark"] footer,
        html.dark footer {
            background-color: #070a10 !important;
            border-top-color: #1e293b !important;
            color: #94a3b8 !important;
        }

        /* Tailwind Global Dark Overrides */
        html.dark .bg-slate-50,
        html.dark .bg-slate-100,
        html.dark .bg-gray-50,
        html.dark .bg-gray-100 {
            background-color: #0b0f19 !important;
        }

        html.dark .bg-white {
            background-color: #131b2e !important;
        }

        html.dark .text-slate-900,
        html.dark .text-slate-800,
        html.dark .text-gray-900,
        html.dark .text-gray-800 {
            color: #f8fafc !important;
        }

        html.dark .text-slate-700,
        html.dark .text-slate-600,
        html.dark .text-gray-700,
        html.dark .text-gray-600 {
            color: #cbd5e1 !important;
        }

        html.dark .text-slate-500,
        html.dark .text-slate-400 {
            color: #94a3b8 !important;
        }

        html.dark .border-slate-200,
        html.dark .border-slate-300,
        html.dark .border-gray-200,
        html.dark .border-gray-300 {
            border-color: #1e293b !important;
        }

        html.dark input,
        html.dark select,
        html.dark textarea {
            background-color: #0b0f19 !important;
            color: #f8fafc !important;
            border-color: #334155 !important;
        }
    </style>
</head>
<body>
    {{-- Demo Mode Interactive Banner --}}
    @if(session('is_demo_session') || (Auth::check() && Auth::user()->email === 'demo@hotel-system.local'))
        <aside class="sticky top-0 z-[9999] bg-gradient-to-r from-amber-500 via-yellow-400 to-amber-500 text-slate-950 font-bold px-4 py-2 shadow-lg border-b border-amber-600/30">
            <div class="container d-flex flex-wrap align-items-center justify-content-between gap-2 text-xs sm:text-sm">
                <div class="d-flex align-items-center gap-2">
                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-slate-950 text-amber-300 text-xs shadow-inner">⚡</span>
                    <span><strong>{{ $isEn ? 'Demo Mode Active' : 'وضع التجربة الفندقية التفاعلي (Demo Mode)' }}:</strong> {{ $isEn ? 'You are exploring the hotel management dashboard in sandbox mode. Changes are isolated.' : 'أنت الآن في لوحة إدارة تجريبية معزولة. يمكنك تجربة كل الخصائص، وتُحذف كافة البيانات تلقائياً عند الخروج.' }}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <form action="{{ route('demo.reset') }}" method="POST" class="m-0" onsubmit="return confirm('{{ $isEn ? 'Reset demo data back to clean state?' : 'هل تريد إعادة ضبط بيانات التجربة للحالة النظيفة؟' }}')">
                        @csrf
                        <button type="submit" class="btn btn-dark btn-sm py-1 px-2.5 font-bold text-xs rounded-lg shadow-sm">
                            🔄 {{ $isEn ? 'Reset Data' : 'إعادة ضبط التجربة' }}
                        </button>
                    </form>
                    <form action="{{ route('demo.exit') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm py-1 px-3 font-bold text-xs rounded-lg shadow-sm">
                            🚪 {{ $isEn ? 'Exit Demo' : 'إنهاء التجربة والخروج' }}
                        </button>
                    </form>
                </div>
            </div>
        </aside>
    @endif

    <header class="app-header py-2.5 text-white">
        <div class="container d-flex justify-content-between align-items-center gap-2">
            
            {{-- Brand / Hotel Logo --}}
            <a href="{{ route('home') }}" class="text-decoration-none d-flex align-items-center gap-2.5 group flex-shrink-0">
                <div class="relative flex items-center justify-center">
                    @if($siteLogo)
                        <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="h-9 max-h-9 max-w-[120px] sm:max-w-[140px] object-contain bg-white/10 rounded-xl p-1 shadow-sm border border-white/20">
                    @else
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 text-white text-lg shadow-md border border-white/20">
                            {{ $siteIcon }}
                        </span>
                    @endif
                    <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                </div>
                <div class="d-none d-sm-block">
                    <span class="text-base sm:text-lg font-black text-white tracking-tight block leading-tight group-hover:text-blue-200 transition">
                        {{ Auth::user()?->hotel?->name ?? $siteName }}
                    </span>
                    <span class="text-[10px] text-slate-300 font-semibold tracking-wider block">
                        {{ Auth::user()?->hotel ? ($isEn ? 'Hotel Management System' : 'نظام الإدارة الفندقية المتكامل') : ($isEn ? 'Hotel Booking Portal' : 'بوابة حجز الفنادق') }}
                    </span>
                </div>
            </a>

            {{-- Desktop Navigation Menus (Large Screens >= 992px) --}}
            <nav class="d-none d-lg-flex align-items-center gap-1.5 flex-wrap">
                @auth
                    @php
                        $unreadMessagesCount = Auth::user()->unreadMessagesCount();
                    @endphp
                    {{-- 0. GUEST USER (حساب زائر / نزيل) --}}
                    @if(Auth::user()->role === 'guest')
                        <a href="{{ route('home') }}" class="nav-link-direct">
                            <span>🏠</span> {{ $isEn ? 'Home' : 'الرئيسية' }}
                        </a>
                        <a href="{{ route('guest.bookings') }}" class="nav-link-direct">
                            <span>🛎️</span> {{ $isEn ? 'My Bookings' : 'حجوزاتي' }}
                        </a>
                        <a href="{{ route('offers.index') }}" class="nav-link-direct">
                            <span>🏷️</span> {{ $isEn ? 'Current Offers' : 'العروض الحالية' }}
                        </a>
                        <a href="{{ route('home') }}#downloads" class="nav-link-direct">
                            <span>📲</span> {{ $isEn ? 'Apps' : 'التطبيقات' }}
                        </a>
                        <a href="{{ route('guest.messages') }}" class="nav-link-direct position-relative">
                            <span>✉️</span> {{ $isEn ? 'Messages' : 'صندوق الرسائل' }}
                            @if($unreadMessagesCount > 0)
                                <span class="badge bg-danger rounded-pill ms-1 text-[10px]">{{ $unreadMessagesCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('guest.profile') }}" class="nav-link-direct">
                            <span>👤</span> {{ $isEn ? 'My Profile' : 'ملفي الشخصي' }}
                        </a>

                    {{-- 1. SUPER ADMIN / PLATFORM ADMIN --}}
                    @elseif(in_array(Auth::user()->role, ['super_admin', 'platform_admin']))
                        <a href="{{ route('platform.dashboard') }}" class="nav-link-direct">
                            <span>📊</span> {{ $isEn ? 'Platform Dashboard' : 'لوحة المنصة' }}
                        </a>

                        <a href="{{ route('platform.crm.index') }}" class="nav-link-direct text-indigo-700 fw-bold">
                            <span>💼</span> {{ $isEn ? 'Platform CRM' : 'نظام CRM والاشتراكات' }}
                        </a>

                        @if(Auth::user()->hasPermission('hotels_create') || Auth::user()->hasPermission('hotels_edit') || Auth::user()->role === 'super_admin')
                            <div class="nav-item dropdown">
                                <button class="nav-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span>🏨</span>
                                    <span>{{ $isEn ? 'Hotel Management' : 'إدارة الفنادق' }}</span>
                                    <span class="dropdown-chevron">▼</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu">
                                    <li>
                                        <a class="custom-dropdown-item" href="{{ route('platform.hotels.index') }}">
                                            <span class="dropdown-item-icon bg-blue-100 text-blue-700">🏨</span>
                                            <div>
                                                <span class="dropdown-item-title">{{ $isEn ? 'Subscribed Hotels' : 'الفنادق المشتركة' }}</span>
                                                <span class="dropdown-item-desc">{{ $isEn ? 'Add, edit and manage hotels' : 'إضافة وتعديل وحظر الفنادق' }}</span>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="custom-dropdown-item" href="{{ route('platform.destinations.index') }}">
                                            <span class="dropdown-item-icon bg-emerald-100 text-emerald-700">📍</span>
                                            <div>
                                                <span class="dropdown-item-title">{{ $isEn ? 'Destinations & Cities' : 'الوجهات والمحافظات' }}</span>
                                                <span class="dropdown-item-desc">{{ $isEn ? 'Manage destination photos & links' : 'إدارة صور وروابط المدن' }}</span>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="custom-dropdown-item" href="{{ route('admin.app-manager.index') }}">
                                            <span class="dropdown-item-icon bg-purple-100 text-purple-700">📱</span>
                                            <div>
                                                <span class="dropdown-item-title">{{ $isEn ? 'Mobile App Manager' : 'إدارة تطبيق الجوال' }}</span>
                                                <span class="dropdown-item-desc">{{ $isEn ? 'Destinations photos & promo banners' : 'صور الوجهات والبنرات الإعلانية' }}</span>
                                            </div>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        @endif

                        <a href="{{ route('admin.app-manager.index') }}" class="nav-link-direct">
                            <span>📱</span> {{ $isEn ? 'Mobile App' : 'تطبيق الجوال' }}
                        </a>

                        @if(Auth::user()->role === 'super_admin')
                            <a href="{{ route('platform.security.index') }}" class="nav-link-direct text-danger fw-bold">
                                <span>🛡️</span> {{ $isEn ? 'Security Monitoring' : 'لوحة الأمان والمراقبة' }}
                            </a>
                        @endif

                        @if(Auth::user()->hasPermission('appearance') || Auth::user()->hasPermission('admins') || Auth::user()->role === 'super_admin')
                            <div class="nav-item dropdown">
                                <button class="nav-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span>⚙️</span>
                                    <span>{{ $isEn ? 'Platform Settings' : 'إعدادات المنصة' }}</span>
                                    <span class="dropdown-chevron">▼</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu">
                                    @if(Auth::user()->hasPermission('appearance') || Auth::user()->role === 'super_admin')
                                        <li>
                                            <a class="custom-dropdown-item" href="{{ route('platform.settings.edit') }}">
                                                <span class="dropdown-item-icon bg-amber-100 text-amber-700">🎨</span>
                                                <div>
                                                    <span class="dropdown-item-title">{{ $isEn ? 'Appearance & Theme' : 'مظهر المنصة والواجهة' }}</span>
                                                    <span class="dropdown-item-desc">{{ $isEn ? 'Hero banner, logos and site texts' : 'البانر واللوجو ونصوص الموقع' }}</span>
                                                </div>
                                            </a>
                                        </li>
                                    @endif
                                    @if(Auth::user()->hasPermission('admins') || Auth::user()->role === 'super_admin')
                                        <li>
                                            <a class="custom-dropdown-item" href="{{ route('platform.admins.index') }}">
                                                <span class="dropdown-item-icon bg-purple-100 text-purple-700">👥</span>
                                                <div>
                                                    <span class="dropdown-item-title">{{ $isEn ? 'Platform Admins & Roles' : 'مدراء المنصة والصلاحيات' }}</span>
                                                    <span class="dropdown-item-desc">{{ $isEn ? 'Manage admins and granular permissions' : 'إدارة الحسابات وتوزيع الصلاحيات' }}</span>
                                                </div>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        @endif

                    {{-- 2. HOTEL ADMIN / MANAGER / OWNER --}}
                    @elseif(in_array(Auth::user()->role, ['hotel_admin', 'admin', 'hotel_owner']))
                        
                        {{-- Section 1: إدارة الفندق --}}
                        <div class="nav-item dropdown">
                            <button class="nav-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span>🏢</span>
                                <span>{{ $isEn ? 'Hotel Management' : 'إدارة الفندق' }}</span>
                                <span class="dropdown-chevron">▼</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu">
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('admin.dashboard') }}">
                                        <span class="dropdown-item-icon bg-blue-100 text-blue-700">📊</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Hotel Dashboard' : 'لوحة الفندق' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Quick stats and occupancy summary' : 'الإحصائيات السريعة ومعدل الإشغال' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('admin.offers.index') }}">
                                        <span class="dropdown-item-icon bg-pink-100 text-pink-700">🏷️</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Promotional Offers' : 'العروض الترويجية' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Discounts & special pricing packages' : 'إدارة وتخصيص باقات العروض والخصومات' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('admin.hotel-profile.edit') }}">
                                        <span class="dropdown-item-icon bg-amber-100 text-amber-700">🏨</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Hotel Profile' : 'بروفايل الفندق' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Photos, amenities, tagline and phone' : 'تعديل الصور والمزايا وأرقام الهاتف' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('employees.index') }}">
                                        <span class="dropdown-item-icon bg-indigo-100 text-indigo-700">👥</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Staff Management' : 'شؤون الموظفين' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Staff accounts and assigned roles' : 'حسابات الموظفين وصلاحيات العمل' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('admin.reports') }}">
                                        <span class="dropdown-item-icon bg-purple-100 text-purple-700">📈</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Comprehensive Reports' : 'التقارير الشاملة' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Daily, monthly and annual reports' : 'تقارير الإيرادات والإشغال والصيانة' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('admin.subscription.index') }}">
                                        <span class="dropdown-item-icon bg-emerald-100 text-emerald-700">💳</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Hotel Subscription' : 'باقتي واشتراك الفندق' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Plan details, expiry & renewal' : 'تفاصيل الخطة، الصلاحية، وطلب التجديد' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('admin.support.index') }}">
                                        <span class="dropdown-item-icon bg-cyan-100 text-cyan-700">🎧</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Technical Support' : 'الدعم الفني والمساعدة' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Open and follow up support tickets' : 'فتح ومتابعة تذاكر الدعم الفني الفوري' }}</span>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        {{-- Section 2: الحجوزات والتسكين --}}
                        <div class="nav-item dropdown">
                            <button class="nav-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span>🛎️</span>
                                <span>{{ $isEn ? 'Bookings & Stays' : 'الحجوزات والتسكين' }}</span>
                                <span class="dropdown-chevron">▼</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu">
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('bookings.index') }}">
                                        <span class="dropdown-item-icon bg-blue-100 text-blue-700">📅</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Bookings' : 'الحجوزات' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'New bookings, confirmation & check-in' : 'إدارة وتأكيد حجوزات النزلاء' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('rooms.index') }}">
                                        <span class="dropdown-item-icon bg-emerald-100 text-emerald-700">🚪</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Rooms' : 'الغرف' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Pricing in SYP & USD, room status' : 'تسعير بالليرة والدولار وحالة الغرفة' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('room-types.index') }}">
                                        <span class="dropdown-item-icon bg-cyan-100 text-cyan-700">🏷️</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Room Types' : 'أنواع الغرف' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Single, double, luxury suites' : 'مفردة، مزدوجة، أجنحة ملكية' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('guests.index') }}">
                                        <span class="dropdown-item-icon bg-violet-100 text-violet-700">👤</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Guests Directory' : 'سجل النزلاء' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Guest records, ID scan and documents' : 'بيانات النزلاء ورفع ومسح الوثائق' }}</span>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        {{-- Section 3: المحاسبة والمالية --}}
                        <div class="nav-item dropdown">
                            <button class="nav-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span>💼</span>
                                <span>{{ $isEn ? 'Finance & Accounts' : 'المحاسبة والمالية' }}</span>
                                <span class="dropdown-chevron">▼</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu">
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('invoices.index') }}">
                                        <span class="dropdown-item-icon bg-emerald-100 text-emerald-700">🧾</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Invoices' : 'الفواتير' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Issue invoices, checkout & payments' : 'إصدار وتسديد فواتير الإقامة' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('accounts.index') }}">
                                        <span class="dropdown-item-icon bg-blue-100 text-blue-700">💰</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Accounting' : 'المحاسبة' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Inflows, outflows and cash book' : 'حركات القبض والصرف والمصروفات' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('accounts.reports') }}">
                                        <span class="dropdown-item-icon bg-indigo-100 text-indigo-700">🏦</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Bank Statement & Ledger' : 'كشف الحساب والستيتمنت' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Strict currency separation (SYP / USD)' : 'كشف بنكي مفصول بالليرة والدولار' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('deletion-logs.index') }}">
                                        <span class="dropdown-item-icon bg-rose-100 text-rose-700">🗑️</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Deletion Logs & Audit' : 'سجل المحذوفات والرقابة' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Audit deleted invoices & bookings with reasons' : 'سجل الحذف وسبب العمليات المحذوفة' }}</span>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        {{-- Section 4: التشغيل والخدمات --}}
                        <div class="nav-item dropdown">
                            <button class="nav-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span>🛠️</span>
                                <span>{{ $isEn ? 'Operations & Services' : 'التشغيل والخدمات' }}</span>
                                <span class="dropdown-chevron">▼</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu">
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('maintenance.index') }}">
                                        <span class="dropdown-item-icon bg-amber-100 text-amber-700">🔧</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Maintenance' : 'إدارة الصيانة' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Work orders and room maintenance' : 'بلاغات الأعطال وأوامر الصيانة' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('cleaning.index') }}">
                                        <span class="dropdown-item-icon bg-teal-100 text-teal-700">🧹</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Housekeeping & Cleaning' : 'التدبير الفندقي والتنظيف' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Room cleaning tasks and inspection' : 'مهام تنظيف وتجهيز الغرف' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('complaints.index') }}">
                                        <span class="dropdown-item-icon bg-rose-100 text-rose-700">💬</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Guest Complaints' : 'شكاوى النزلاء' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Review and resolve guest issues' : 'متابعة ومعالجة ملاحظات النزلاء' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('messages.inbox') }}">
                                        <span class="dropdown-item-icon bg-blue-100 text-blue-700 position-relative">
                                            ✉️
                                            @if($unreadMessagesCount > 0)
                                                <span class="position-absolute -top-1 -right-1 w-2.5 h-2.5 bg-rose-500 rounded-full animate-pulse border border-white"></span>
                                            @endif
                                        </span>
                                        <div>
                                            <span class="dropdown-item-title d-flex align-items-center gap-1.5">
                                                {{ $isEn ? 'Message Inbox' : 'صندوق الرسائل' }}
                                                @if($unreadMessagesCount > 0)
                                                    <span class="badge bg-danger rounded-pill text-[10px]">{{ $unreadMessagesCount }}</span>
                                                @endif
                                            </span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Internal staff communications' : 'المراسلات الداخلية بين الموظفين' }}</span>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        {{-- Section 5: أدوات النظام --}}
                        <div class="nav-item dropdown">
                            <button class="nav-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span>⚙️</span>
                                <span>{{ $isEn ? 'System Tools' : 'أدوات النظام' }}</span>
                                <span class="dropdown-chevron">▼</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu">
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('admin.printer-settings.edit') }}">
                                        <span class="dropdown-item-icon bg-emerald-100 text-emerald-700">🖨️</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Printers & Scanner' : 'إعدادات الطباعة والسكنر' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Printers, webcam & scanner settings' : 'طابعات الفواتير والكاميرا والماسح' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('admin.backup.index') }}">
                                        <span class="dropdown-item-icon bg-amber-100 text-amber-700">💾</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Backup & Restore' : 'النسخ الاحتياطي والاستعادة' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Export and restore JSON hotel backup' : 'تصدير واسترجاع بيانات الفندق JSON' }}</span>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>

                    {{-- 3. RECEPTION --}}
                    @elseif(Auth::user()->role === 'reception')
                        <div class="nav-item dropdown">
                            <button class="nav-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span>🛎️</span>
                                <span>{{ $isEn ? 'Front Desk' : 'الاستقبال والتسكين' }}</span>
                                <span class="dropdown-chevron">▼</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu">
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('bookings.index') }}">
                                        <span class="dropdown-item-icon bg-blue-100 text-blue-700">📅</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Bookings' : 'الحجوزات' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Check-ins and guest reservations' : 'حجوزات النزلاء وإجراءات الدخول' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('guests.index') }}">
                                        <span class="dropdown-item-icon bg-emerald-100 text-emerald-700">👤</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Guests' : 'النزلاء' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Guest records and document uploads' : 'بيانات النزلاء ورفع الوثائق' }}</span>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <a href="{{ route('invoices.index') }}" class="nav-link-direct">
                            <span>🧾</span> {{ $isEn ? 'Invoices' : 'الفواتير' }}
                        </a>
                        <a href="{{ route('messages.inbox') }}" class="nav-link-direct position-relative">
                            <span>✉️</span> {{ $isEn ? 'Messages' : 'الرسائل' }}
                            @if($unreadMessagesCount > 0)
                                <span class="badge bg-danger rounded-pill ms-1 text-[10px]">{{ $unreadMessagesCount }}</span>
                            @endif
                        </a>

                    {{-- 4. ACCOUNTANT --}}
                    @elseif(Auth::user()->role === 'accountant')
                        <div class="nav-item dropdown">
                            <button class="nav-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span>💼</span>
                                <span>{{ $isEn ? 'Finance & Accounts' : 'المحاسبة والمالية' }}</span>
                                <span class="dropdown-chevron">▼</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu">
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('accounts.index') }}">
                                        <span class="dropdown-item-icon bg-emerald-100 text-emerald-700">💰</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Accounting' : 'المحاسبة' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Inflow & outflow entries' : 'دفتر القيود والصادر والوارد' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('invoices.index') }}">
                                        <span class="dropdown-item-icon bg-blue-100 text-blue-700">🧾</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Invoices' : 'الفواتير' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Billing and payments' : 'إدارة وسداد فواتير الإقامة' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('accounts.reports') }}">
                                        <span class="dropdown-item-icon bg-indigo-100 text-indigo-700">🏦</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Bank Statement & Ledger' : 'كشف الحساب والستيتمنت' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Separated statement for SYP & USD' : 'تقرير بنكي مفصول بالعملتين' }}</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="custom-dropdown-item" href="{{ route('deletion-logs.index') }}">
                                        <span class="dropdown-item-icon bg-rose-100 text-rose-700">🗑️</span>
                                        <div>
                                            <span class="dropdown-item-title">{{ $isEn ? 'Deletion Logs & Audit' : 'سجل المحذوفات والرقابة' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Deleted invoices and bookings audit' : 'الفواتير والحجوزات المحذوفة' }}</span>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <a href="{{ route('messages.inbox') }}" class="nav-link-direct position-relative">
                            <span>✉️</span> {{ $isEn ? 'Messages' : 'الرسائل' }}
                            @if($unreadMessagesCount > 0)
                                <span class="badge bg-danger rounded-pill ms-1 text-[10px]">{{ $unreadMessagesCount }}</span>
                            @endif
                        </a>

                    {{-- 5. MAINTENANCE & CLEANER --}}
                    @elseif(Auth::user()->role === 'maintenance')
                        <a href="{{ route('maintenance.index') }}" class="nav-link-direct">
                            <span>🔧</span> {{ $isEn ? 'Maintenance' : 'الصيانة' }}
                        </a>
                        <a href="{{ route('messages.inbox') }}" class="nav-link-direct position-relative">
                            <span>✉️</span> {{ $isEn ? 'Messages' : 'الرسائل' }}
                            @if($unreadMessagesCount > 0)
                                <span class="badge bg-danger rounded-pill ms-1 text-[10px]">{{ $unreadMessagesCount }}</span>
                            @endif
                        </a>
                    @elseif(Auth::user()->role === 'cleaner')
                        <a href="{{ route('cleaning.index') }}" class="nav-link-direct">
                            <span>🧹</span> {{ $isEn ? 'Cleaning Tasks' : 'مهام التنظيف' }}
                        </a>
                        <a href="{{ route('messages.inbox') }}" class="nav-link-direct position-relative">
                            <span>✉️</span> {{ $isEn ? 'Messages' : 'الرسائل' }}
                            @if($unreadMessagesCount > 0)
                                <span class="badge bg-danger rounded-pill ms-1 text-[10px]">{{ $unreadMessagesCount }}</span>
                            @endif
                        </a>
                    @endif

                @else
                    <a href="{{ route('home') }}" class="nav-link-direct">{{ $isEn ? 'Home' : 'الرئيسية' }}</a>
                    <a href="{{ route('pricing') }}" class="nav-link-direct">{{ $isEn ? 'Plans & Pricing' : 'الأسعار والاشتراكات' }}</a>
                    <a href="{{ route('offers.index') }}" class="nav-link-direct">{{ $isEn ? 'Special Offers' : 'العروض الحالية' }}</a>
                    <a href="{{ route('demo.enter') }}" class="nav-link-direct text-amber-300 font-bold hover:text-amber-200">
                        <span class="inline-block animate-pulse">⚡</span> {{ $isEn ? 'Live Demo' : 'تجربة النظام (Demo)' }}
                    </a>
                    <a href="{{ route('home') }}#downloads" class="nav-link-direct">{{ $isEn ? '📲 Apps' : '📲 التطبيقات' }}</a>
                    <a href="{{ route('login') }}" class="btn btn-sm btn-light text-blue-900 font-bold px-3 py-1.5 rounded-xl shadow-sm hover:bg-white transition">{{ $isEn ? 'Login' : 'تسجيل الدخول' }}</a>
                    <a href="{{ route('register') }}" class="btn btn-sm btn-outline-light font-bold px-3 py-1.5 rounded-xl transition">{{ $isEn ? 'Register' : 'إنشاء حساب' }}</a>
                @endauth
            </nav>

            {{-- Quick Controls (Language + Theme + Profile + Mobile Hamburger) --}}
            <div class="d-flex align-items-center gap-2">

                {{-- 1. Language Switcher (Segmented Pill) --}}
                <div class="lang-segmented-pill">
                    <a href="{{ route('locale.switch', 'ar') }}" class="lang-pill-item {{ !$isEn ? 'active' : '' }}" title="التبديل إلى العربية">
                        <span>🇸🇦</span>
                        <span class="d-none d-sm-inline">عربي</span>
                    </a>
                    <a href="{{ route('locale.switch', 'en') }}" class="lang-pill-item {{ $isEn ? 'active' : '' }}" title="Switch to English">
                        <span>🇬🇧</span>
                        <span class="d-none d-sm-inline">EN</span>
                    </a>
                </div>

                {{-- 2. Dark / Light Mode Toggle Button --}}
                <button id="themeToggleBtn" type="button" class="theme-toggle-btn" aria-label="Toggle theme" title="{{ $isEn ? 'Toggle Dark / Light Mode' : 'تبديل الوضع داكن / فاتح' }}">
                    <span class="theme-icon-moon">🌙</span>
                    <span class="theme-icon-sun">☀️</span>
                </button>

                {{-- 2.3. Messages Mailbox Quick Icon (Desktop & Mobile) --}}
                @auth
                    @php
                        $messagesLink = Auth::user()->role === 'guest' ? route('guest.messages') : route('messages.inbox');
                    @endphp
                    <a href="{{ $messagesLink }}" class="nav-dropdown-btn !p-2 !rounded-xl position-relative d-flex align-items-center justify-content-center text-decoration-none" title="{{ $isEn ? 'Messages & Mailbox' : 'صندوق البريد والرسائل' }}" id="messagesMailboxBtn">
                        <span class="text-base leading-none">✉️</span>
                        @if($unreadMessagesCount > 0)
                            <span class="position-absolute -top-1 -right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-600 text-[10px] font-black text-white border-2 border-slate-900 shadow-sm animate-pulse">
                                {{ $unreadMessagesCount > 99 ? '99+' : $unreadMessagesCount }}
                            </span>
                        @endif
                    </a>
                @endauth

                {{-- 2.5. Notifications Bell (Desktop & Mobile) --}}
                @auth
                    @php
                        $unreadNotificationsCount = Auth::user()->unreadNotificationsCount();
                        $recentNotifications = Auth::user()->notifications()->latest()->take(6)->get();
                    @endphp
                    <div class="nav-item dropdown position-relative">
                        <button class="nav-dropdown-btn !p-2 !rounded-xl position-relative d-flex align-items-center justify-content-center" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ $isEn ? 'Notifications' : 'الإشعارات' }}" id="notificationBellBtn">
                            <span class="text-base leading-none">🔔</span>
                            @if($unreadNotificationsCount > 0)
                                <span class="position-absolute -top-1 -right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-500 text-[10px] font-black text-white border-2 border-slate-900 shadow-sm animate-pulse">
                                    {{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}
                                </span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end custom-dropdown-menu shadow-2xl p-0" style="width: 320px; max-width: 90vw; border-radius: 1rem; overflow: hidden;">
                            <div class="p-3 bg-slate-900 text-white d-flex align-items-center justify-content-between border-b border-white/10">
                                <div class="d-flex align-items-center gap-2">
                                    <span>🔔</span>
                                    <span class="font-bold text-xs">{{ $isEn ? 'Notifications' : 'الإشعارات والتنبيهات' }}</span>
                                </div>
                                @if($unreadNotificationsCount > 0)
                                    <form action="{{ route('notifications.read-all') }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="text-[11px] text-blue-300 hover:text-white bg-transparent border-0 p-0 text-decoration-none font-semibold">
                                            {{ $isEn ? 'Mark all read' : 'قراءة الكل' }}
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse($recentNotifications as $notif)
                                    <a href="{{ $notif->link ? route('notifications.read', $notif->id) : '#' }}" class="d-block p-2.5 text-decoration-none hover:bg-slate-50 dark:hover:bg-slate-800 transition {{ !$notif->is_read ? 'bg-blue-50/60 dark:bg-blue-950/30' : '' }}">
                                        <div class="d-flex align-items-start gap-2">
                                            <div class="w-7 h-7 rounded-full flex-shrink-0 d-flex align-items-center justify-content-center text-xs {{ !$notif->is_read ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                                                <i class="{{ $notif->icon ?? 'fas fa-bell' }}"></i>
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="d-flex align-items-center justify-content-between gap-1 mb-0.5">
                                                    <span class="font-bold text-xs text-slate-800 dark:text-slate-100 truncate">{{ $notif->title }}</span>
                                                    <span class="text-[10px] text-slate-400 whitespace-nowrap">{{ $notif->created_at->diffForHumans() }}</span>
                                                </div>
                                                <p class="text-[11px] text-slate-600 dark:text-slate-300 line-clamp-2 mb-0">{{ $notif->message }}</p>
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <div class="p-4 text-center text-slate-400 text-xs">
                                        <span class="text-2xl d-block mb-1">🔕</span>
                                        {{ $isEn ? 'No notifications yet' : 'لا توجد إشعارات حالياً' }}
                                    </div>
                                @endforelse
                            </div>

                            <div class="p-2 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 text-center">
                                <a href="{{ route('notifications.index') }}" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ $isEn ? 'View all notifications' : 'عرض كل الإشعارات' }} ←
                                </a>
                            </div>
                        </div>
                    </div>
                @endauth

                {{-- 3. User Profile Dropdown (Desktop) --}}
                @auth
                    <div class="nav-item dropdown d-none d-lg-block">
                        <button class="nav-dropdown-btn !bg-white/10 !border-white/25 hover:!bg-white/20" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ Auth::user()->avatar_url }}" alt="avatar" class="w-6 h-6 rounded-full object-cover border border-white/60 bg-white/30">
                            <span class="font-bold text-white text-xs">{{ Auth::user()->name }}</span>
                            <span class="dropdown-chevron">▼</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu" style="min-width: 240px;">
                            <li class="px-3 py-2 border-b border-slate-100 mb-1">
                                <span class="text-xs text-slate-400 block">{{ $isEn ? 'Signed in as:' : 'تسجيل الدخول كـ:' }}</span>
                                <strong class="text-xs text-slate-800 font-bold block">{{ Auth::user()->email }}</strong>
                                <span class="badge bg-blue-100 text-blue-800 text-[10px] mt-1">
                                    {{ Auth::user()->role === 'hotel_owner' ? ($isEn ? 'Hotel Owner (Observer)' : 'مالك الفندق (اطلاع ومراقبة فقط)') : Auth::user()->role }}
                                </span>
                            </li>
                            <li>
                                <a class="custom-dropdown-item" href="{{ route('notifications.index') }}">
                                    <span class="dropdown-item-icon bg-amber-100 text-amber-700">🔔</span>
                                    <div>
                                        <span class="dropdown-item-title">{{ $isEn ? 'Notifications Center' : 'مركز الإشعارات' }}</span>
                                        <span class="dropdown-item-desc">{{ $isEn ? 'All activity alerts and updates' : 'كل التنبيهات والإشعارات والرسائل' }}</span>
                                    </div>
                                </a>
                            </li>
                            <li>
                                <a class="custom-dropdown-item" href="{{ route('profile.edit') }}">
                                    <span class="dropdown-item-icon bg-slate-100 text-slate-700">👤</span>
                                    <div>
                                        <span class="dropdown-item-title">{{ $isEn ? 'My Profile' : 'الملف الشخصي' }}</span>
                                        <span class="dropdown-item-desc">{{ $isEn ? 'Account info & password' : 'تعديل البيانات وكلمة المرور' }}</span>
                                    </div>
                                </a>
                            </li>
                            <li class="pt-1 mt-1 border-t border-slate-100">
                                <form action="{{ route('logout') }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="w-full custom-dropdown-item text-rose-600 hover:!bg-rose-50 text-start border-0 bg-transparent">
                                        <span class="dropdown-item-icon bg-rose-100 text-rose-600">🚪</span>
                                        <div>
                                            <span class="dropdown-item-title text-rose-600">{{ $isEn ? 'Logout' : 'تسجيل الخروج' }}</span>
                                            <span class="dropdown-item-desc">{{ $isEn ? 'Sign out safely' : 'إنهاء الجلسة الحالية بأمان' }}</span>
                                        </div>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endauth

                {{-- 4. Hamburger Button for Mobile & iPad (< 992px) --}}
                <button class="mobile-menu-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNavDrawer" aria-controls="mobileNavDrawer" aria-label="Open Navigation Menu">
                    <span>☰</span>
                </button>
            </div>
        </div>
    </header>

    {{-- Offcanvas Drawer Navigation for Mobile Phones & iPad / Tablets --}}
    <div class="offcanvas offcanvas-{{ $isEn ? 'start' : 'end' }} mobile-drawer text-white" tabindex="-1" id="mobileNavDrawer" aria-labelledby="mobileNavDrawerLabel">
        <div class="offcanvas-header border-b border-white/10 pb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="text-xl">{{ $siteIcon }}</span>
                <div>
                    <h5 class="offcanvas-title text-sm font-bold text-white mb-0" id="mobileNavDrawerLabel">
                        {{ Auth::user()?->hotel?->name ?? $siteName }}
                    </h5>
                    <span class="text-[10px] text-slate-400 block">
                        {{ $isEn ? 'Quick Navigation' : 'القائمة الرئيسية' }}
                    </span>
                </div>
            </div>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body p-3">
            @auth
                {{-- User Profile Card --}}
                <div class="p-3 rounded-2xl bg-white/5 border border-white/10 mb-3 d-flex align-items-center gap-3">
                    <img src="{{ Auth::user()->avatar_url }}" alt="avatar" class="w-11 h-11 rounded-full object-cover border-2 border-white/30 bg-white/10">
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-sm font-bold text-white block truncate">{{ Auth::user()->name }}</span>
                        <span class="text-xs text-slate-400 block truncate">{{ Auth::user()->email }}</span>
                        <span class="badge bg-blue-600/80 text-white text-[10px] mt-1">{{ Auth::user()->role }}</span>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-light rounded-xl text-xs py-1 px-2" title="{{ $isEn ? 'Edit Profile' : 'تعديل البروفايل' }}">
                        ✏️
                    </a>
                </div>

                {{-- Notifications Link for Mobile --}}
                <a href="{{ route('notifications.index') }}" class="mobile-nav-link d-flex align-items-center justify-content-between mb-3 bg-blue-600/20 border-blue-500/30">
                    <div class="d-flex align-items-center gap-2.5">
                        <span class="text-lg">🔔</span>
                        <span class="font-bold">{{ $isEn ? 'Notifications Center' : 'مركز الإشعارات والتنبيهات' }}</span>
                    </div>
                    @if(Auth::user()->unreadNotificationsCount() > 0)
                        <span class="badge rounded-pill bg-rose-500 text-[11px] px-2 py-0.5">
                            {{ Auth::user()->unreadNotificationsCount() }}
                        </span>
                    @endif
                </a>

                {{-- NAVIGATION SECTIONS FOR MOBILE --}}

                {{-- GUEST USER (حساب زائر / نزيل) --}}
                @if(Auth::user()->role === 'guest')
                    <div class="mobile-category-title">🛎️ {{ $isEn ? 'Guest Portal' : 'بوابة النزيل' }}</div>
                    <a href="{{ route('home') }}" class="mobile-nav-link">
                        <span class="text-lg">🏠</span>
                        <span>{{ $isEn ? 'Home' : 'الرئيسية' }}</span>
                    </a>
                    <a href="{{ route('guest.bookings') }}" class="mobile-nav-link">
                        <span class="text-lg">🛎️</span>
                        <span>{{ $isEn ? 'My Bookings' : 'حجوزاتي' }}</span>
                    </a>
                    <a href="{{ route('offers.index') }}" class="mobile-nav-link">
                        <span class="text-lg">🏷️</span>
                        <span>{{ $isEn ? 'Current Offers' : 'العروض الحالية' }}</span>
                    </a>
                    <a href="{{ route('guest.messages') }}" class="mobile-nav-link d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-lg">✉️</span>
                            <span>{{ $isEn ? 'Messages' : 'صندوق الرسائل' }}</span>
                        </div>
                        @if($unreadMessagesCount > 0)
                            <span class="badge bg-danger rounded-pill">{{ $unreadMessagesCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('guest.profile') }}" class="mobile-nav-link">
                        <span class="text-lg">👤</span>
                        <span>{{ $isEn ? 'My Profile' : 'ملفي الشخصي' }}</span>
                    </a>

                {{-- SUPER ADMIN / PLATFORM ADMIN --}}
                @elseif(in_array(Auth::user()->role, ['super_admin', 'platform_admin']))
                    <div class="mobile-category-title">📊 {{ $isEn ? 'Platform Administration' : 'إدارة المنصة' }}</div>
                    <a href="{{ route('platform.dashboard') }}" class="mobile-nav-link">
                        <span class="text-lg">📊</span>
                        <span>{{ $isEn ? 'Platform Dashboard' : 'لوحة المنصة' }}</span>
                    </a>
                    @if(Auth::user()->hasPermission('hotels_create') || Auth::user()->hasPermission('hotels_edit') || Auth::user()->role === 'super_admin')
                        <a href="{{ route('platform.hotels.index') }}" class="mobile-nav-link">
                            <span class="text-lg">🏨</span>
                            <span>{{ $isEn ? 'Subscribed Hotels' : 'الفنادق المشتركة' }}</span>
                        </a>
                        <a href="{{ route('platform.destinations.index') }}" class="mobile-nav-link">
                            <span class="text-lg">📍</span>
                            <span>{{ $isEn ? 'Destinations & Cities' : 'الوجهات والمحافظات' }}</span>
                        </a>
                    @endif
                    @if(Auth::user()->hasPermission('appearance') || Auth::user()->role === 'super_admin')
                        <a href="{{ route('platform.settings.edit') }}" class="mobile-nav-link">
                            <span class="text-lg">🎨</span>
                            <span>{{ $isEn ? 'Appearance & Theme' : 'مظهر المنصة والواجهة' }}</span>
                        </a>
                    @endif
                    @if(Auth::user()->hasPermission('admins') || Auth::user()->role === 'super_admin')
                        <a href="{{ route('platform.admins.index') }}" class="mobile-nav-link">
                            <span class="text-lg">👥</span>
                            <span>{{ $isEn ? 'Platform Admins & Roles' : 'مدراء المنصة والصلاحيات' }}</span>
                        </a>
                    @endif
                    @if(Auth::user()->role === 'super_admin')
                        <a href="{{ route('platform.security.index') }}" class="mobile-nav-link text-danger font-bold">
                            <span class="text-lg">🛡️</span>
                            <span>{{ $isEn ? 'Security Monitoring' : 'لوحة الأمان والمراقبة' }}</span>
                        </a>
                    @endif

                {{-- HOTEL ADMIN / MANAGER / OWNER --}}
                @elseif(in_array(Auth::user()->role, ['hotel_admin', 'admin', 'hotel_owner']))
                    
                    {{-- 1. إدارة الفندق --}}
                    <div class="mobile-category-title">🏢 {{ $isEn ? 'Hotel Management' : 'إدارة الفندق' }}</div>
                    <a href="{{ route('admin.dashboard') }}" class="mobile-nav-link">
                        <span class="text-lg">📊</span>
                        <span>{{ $isEn ? 'Hotel Dashboard' : 'لوحة الفندق' }}</span>
                    </a>
                    <a href="{{ route('admin.offers.index') }}" class="mobile-nav-link">
                        <span class="text-lg">🏷️</span>
                        <span>{{ $isEn ? 'Promotional Offers' : 'العروض الترويجية' }}</span>
                    </a>
                    <a href="{{ route('admin.hotel-profile.edit') }}" class="mobile-nav-link">
                        <span class="text-lg">🏨</span>
                        <span>{{ $isEn ? 'Hotel Profile' : 'بروفايل الفندق' }}</span>
                    </a>
                    <a href="{{ route('employees.index') }}" class="mobile-nav-link">
                        <span class="text-lg">👥</span>
                        <span>{{ $isEn ? 'Staff Management' : 'شؤون الموظفين' }}</span>
                    </a>
                    <a href="{{ route('admin.reports') }}" class="mobile-nav-link">
                        <span class="text-lg">📈</span>
                        <span>{{ $isEn ? 'Comprehensive Reports' : 'التقارير الشاملة' }}</span>
                    </a>

                    {{-- 2. الحجوزات والتسكين --}}
                    <div class="mobile-category-title">🛎️ {{ $isEn ? 'Bookings & Stays' : 'الحجوزات والتسكين' }}</div>
                    <a href="{{ route('bookings.index') }}" class="mobile-nav-link">
                        <span class="text-lg">📅</span>
                        <span>{{ $isEn ? 'Bookings' : 'الحجوزات' }}</span>
                    </a>
                    <a href="{{ route('rooms.index') }}" class="mobile-nav-link">
                        <span class="text-lg">🚪</span>
                        <span>{{ $isEn ? 'Rooms' : 'الغرف' }}</span>
                    </a>
                    <a href="{{ route('room-types.index') }}" class="mobile-nav-link">
                        <span class="text-lg">🏷️</span>
                        <span>{{ $isEn ? 'Room Types' : 'أنواع الغرف' }}</span>
                    </a>
                    <a href="{{ route('guests.index') }}" class="mobile-nav-link">
                        <span class="text-lg">👤</span>
                        <span>{{ $isEn ? 'Guests Directory' : 'سجل النزلاء' }}</span>
                    </a>

                    {{-- 3. المحاسبة والمالية --}}
                    <div class="mobile-category-title">💼 {{ $isEn ? 'Finance & Accounts' : 'المحاسبة والمالية' }}</div>
                    <a href="{{ route('invoices.index') }}" class="mobile-nav-link">
                        <span class="text-lg">🧾</span>
                        <span>{{ $isEn ? 'Invoices' : 'الفواتير' }}</span>
                    </a>
                    <a href="{{ route('accounts.index') }}" class="mobile-nav-link">
                        <span class="text-lg">💰</span>
                        <span>{{ $isEn ? 'Accounting' : 'المحاسبة' }}</span>
                    </a>
                    <a href="{{ route('accounts.reports') }}" class="mobile-nav-link">
                        <span class="text-lg">🏦</span>
                        <span>{{ $isEn ? 'Bank Statement & Ledger' : 'كشف الحساب والستيتمنت' }}</span>
                    </a>
                    <a href="{{ route('deletion-logs.index') }}" class="mobile-nav-link">
                        <span class="text-lg">🗑️</span>
                        <span>{{ $isEn ? 'Deletion Logs & Audit' : 'سجل المحذوفات والرقابة' }}</span>
                    </a>

                    {{-- 4. التشغيل والخدمات --}}
                    <div class="mobile-category-title">🛠️ {{ $isEn ? 'Operations & Services' : 'التشغيل والخدمات' }}</div>
                    <a href="{{ route('maintenance.index') }}" class="mobile-nav-link">
                        <span class="text-lg">🔧</span>
                        <span>{{ $isEn ? 'Maintenance' : 'إدارة الصيانة' }}</span>
                    </a>
                    <a href="{{ route('cleaning.index') }}" class="mobile-nav-link">
                        <span class="text-lg">🧹</span>
                        <span>{{ $isEn ? 'Housekeeping & Cleaning' : 'التدبير الفندقي والتنظيف' }}</span>
                    </a>
                    <a href="{{ route('complaints.index') }}" class="mobile-nav-link">
                        <span class="text-lg">💬</span>
                        <span>{{ $isEn ? 'Guest Complaints' : 'شكاوى النزلاء' }}</span>
                    </a>
                    <a href="{{ route('messages.inbox') }}" class="mobile-nav-link d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-lg">✉️</span>
                            <span>{{ $isEn ? 'Message Inbox' : 'صندوق الرسائل' }}</span>
                        </div>
                        @if($unreadMessagesCount > 0)
                            <span class="badge bg-danger rounded-pill">{{ $unreadMessagesCount }}</span>
                        @endif
                    </a>

                    {{-- 5. أدوات النظام --}}
                    <div class="mobile-category-title">⚙️ {{ $isEn ? 'System Tools' : 'أدوات النظام' }}</div>
                    <a href="{{ route('admin.printer-settings.edit') }}" class="mobile-nav-link">
                        <span class="text-lg">🖨️</span>
                        <span>{{ $isEn ? 'Printers & Scanner' : 'إعدادات الطباعة والسكنر' }}</span>
                    </a>
                    <a href="{{ route('admin.backup.index') }}" class="mobile-nav-link">
                        <span class="text-lg">💾</span>
                        <span>{{ $isEn ? 'Backup & Restore' : 'النسخ الاحتياطي والاستعادة' }}</span>
                    </a>

                {{-- RECEPTION --}}
                @elseif(Auth::user()->role === 'reception')
                    <div class="mobile-category-title">🛎️ {{ $isEn ? 'Front Desk' : 'الاستقبال والتسكين' }}</div>
                    <a href="{{ route('bookings.index') }}" class="mobile-nav-link">
                        <span class="text-lg">📅</span>
                        <span>{{ $isEn ? 'Bookings' : 'الحجوزات' }}</span>
                    </a>
                    <a href="{{ route('guests.index') }}" class="mobile-nav-link">
                        <span class="text-lg">👤</span>
                        <span>{{ $isEn ? 'Guests' : 'النزلاء' }}</span>
                    </a>
                    <a href="{{ route('invoices.index') }}" class="mobile-nav-link">
                        <span class="text-lg">🧾</span>
                        <span>{{ $isEn ? 'Invoices' : 'الفواتير' }}</span>
                    </a>
                    <a href="{{ route('messages.inbox') }}" class="mobile-nav-link d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-lg">✉️</span>
                            <span>{{ $isEn ? 'Messages' : 'الرسائل' }}</span>
                        </div>
                        @if($unreadMessagesCount > 0)
                            <span class="badge bg-danger rounded-pill">{{ $unreadMessagesCount }}</span>
                        @endif
                    </a>

                {{-- ACCOUNTANT --}}
                @elseif(Auth::user()->role === 'accountant')
                    <div class="mobile-category-title">💼 {{ $isEn ? 'Finance & Accounts' : 'المحاسبة والمالية' }}</div>
                    <a href="{{ route('accounts.index') }}" class="mobile-nav-link">
                        <span class="text-lg">💰</span>
                        <span>{{ $isEn ? 'Accounting' : 'المحاسبة' }}</span>
                    </a>
                    <a href="{{ route('invoices.index') }}" class="mobile-nav-link">
                        <span class="text-lg">🧾</span>
                        <span>{{ $isEn ? 'Invoices' : 'الفواتير' }}</span>
                    </a>
                    <a href="{{ route('accounts.reports') }}" class="mobile-nav-link">
                        <span class="text-lg">🏦</span>
                        <span>{{ $isEn ? 'Bank Statement & Ledger' : 'كشف الحساب والستيتمنت' }}</span>
                    </a>
                    <a href="{{ route('deletion-logs.index') }}" class="mobile-nav-link">
                        <span class="text-lg">🗑️</span>
                        <span>{{ $isEn ? 'Deletion Logs & Audit' : 'سجل المحذوفات والرقابة' }}</span>
                    </a>
                    <a href="{{ route('messages.inbox') }}" class="mobile-nav-link d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-lg">✉️</span>
                            <span>{{ $isEn ? 'Messages' : 'الرسائل' }}</span>
                        </div>
                        @if($unreadMessagesCount > 0)
                            <span class="badge bg-danger rounded-pill">{{ $unreadMessagesCount }}</span>
                        @endif
                    </a>

                {{-- MAINTENANCE & CLEANER --}}
                @elseif(Auth::user()->role === 'maintenance')
                    <a href="{{ route('maintenance.index') }}" class="mobile-nav-link">
                        <span class="text-lg">🔧</span>
                        <span>{{ $isEn ? 'Maintenance' : 'الصيانة' }}</span>
                    </a>
                    <a href="{{ route('messages.inbox') }}" class="mobile-nav-link d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-lg">✉️</span>
                            <span>{{ $isEn ? 'Messages' : 'الرسائل' }}</span>
                        </div>
                        @if($unreadMessagesCount > 0)
                            <span class="badge bg-danger rounded-pill">{{ $unreadMessagesCount }}</span>
                        @endif
                    </a>
                @elseif(Auth::user()->role === 'cleaner')
                    <a href="{{ route('cleaning.index') }}" class="mobile-nav-link">
                        <span class="text-lg">🧹</span>
                        <span>{{ $isEn ? 'Cleaning Tasks' : 'مهام التنظيف' }}</span>
                    </a>
                    <a href="{{ route('messages.inbox') }}" class="mobile-nav-link d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-lg">✉️</span>
                            <span>{{ $isEn ? 'Messages' : 'الرسائل' }}</span>
                        </div>
                        @if($unreadMessagesCount > 0)
                            <span class="badge bg-danger rounded-pill">{{ $unreadMessagesCount }}</span>
                        @endif
                    </a>
                @endif

                {{-- Logout Button in Mobile Drawer --}}
                <div class="pt-3 mt-3 border-t border-white/10">
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="w-full mobile-nav-link !bg-rose-500/20 !border-rose-500/30 text-rose-300 hover:!bg-rose-500/30 justify-content-center">
                            <span>🚪</span>
                            <span class="font-bold">{{ $isEn ? 'Sign Out' : 'تسجيل الخروج' }}</span>
                        </button>
                    </form>
                </div>

            @else
                <a href="{{ route('home') }}" class="mobile-nav-link">
                    <span class="text-lg">🏠</span>
                    <span>{{ $isEn ? 'Home' : 'الرئيسية' }}</span>
                </a>
                <a href="{{ route('pricing') }}" class="mobile-nav-link">
                    <span class="text-lg">💳</span>
                    <span>{{ $isEn ? 'Plans & Subscriptions' : 'باقات وااشتراكات النظام' }}</span>
                </a>
                <a href="{{ route('demo.enter') }}" class="mobile-nav-link !bg-amber-500/15 !border-amber-400/30 text-amber-300 font-bold">
                    <span class="text-lg">⚡</span>
                    <span>{{ $isEn ? 'Live Demo (Sandbox)' : 'تجربة النظام الحي (Demo)' }}</span>
                </a>
                <a href="{{ route('offers.index') }}" class="mobile-nav-link">
                    <span class="text-lg">🏷️</span>
                    <span>{{ $isEn ? 'Current Offers' : 'العروض الحالية' }}</span>
                </a>
                <a href="{{ route('home') }}#downloads" class="mobile-nav-link">
                    <span class="text-lg">📲</span>
                    <span>{{ $isEn ? 'Download Apps' : 'تحميل تطبيقات النظام' }}</span>
                </a>
                <div class="d-flex gap-2 mt-2">
                    <a href="{{ route('login') }}" class="mobile-nav-link flex-grow-1 !bg-blue-600 !border-blue-500 text-white font-bold justify-content-center">
                        <span>🔐</span>
                        <span>{{ $isEn ? 'Login' : 'تسجيل الدخول' }}</span>
                    </a>
                    <a href="{{ route('register') }}" class="mobile-nav-link flex-grow-1 !bg-white/10 !border-white/20 text-white font-bold justify-content-center">
                        <span>📝</span>
                        <span>{{ $isEn ? 'Register' : 'إنشاء حساب' }}</span>
                    </a>
                </div>
            @endauth
        </div>
    </div>

    {{-- Main Page Content --}}
    <main class="container my-3 my-md-4 px-2.5 px-sm-3 px-md-4">
        {{-- Flash Messages --}}
        @if(session('status'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="text-center py-3">
        <div class="container text-muted small px-3">
            {{ \App\Models\SiteSetting::get('footer_text', $isEn ? '© 2026 All Rights Reserved | ' . $siteName : '© 2026 جميع الحقوق محفوظة | ' . $siteName) }}
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS (Includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Dark Mode & Client Translations Controller -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Theme Switcher Logic
            const themeBtn = document.getElementById('themeToggleBtn');
            if (themeBtn) {
                themeBtn.addEventListener('click', function () {
                    const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    
                    document.documentElement.setAttribute('data-bs-theme', newTheme);
                    if (newTheme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                    localStorage.setItem('hotel_theme', newTheme);
                });
            }

            // Comprehensive client-side text translator for all page UI & internal contents when in English
            @if($isEn)
            (function() {
                const dict = {
                    // Hero & Landing
                    'بوابتك لحجز أرقى الفنادق والمنتجعات السياحية': 'Your gateway to booking the finest hotels and luxury resorts',
                    'إقامتك المثالية تبدأ هنا': 'Your ideal stay starts here',
                    'بأعلى معايير الضيافة العالمية': 'With the highest standards of global hospitality',
                    'اختر من بين أفخم الفنادق والوجهات السياحية، استعرض الغرف المتوفرة لحظياً، واستمتع بخيارات تسعير منفصلة بالليرة السورية والدولار الأمريكي مع تأكيد فوري للحجز.': 'Choose from luxury hotels and top destinations, browse available rooms in real-time, and enjoy separate pricing in SYP and USD with instant booking confirmation.',
                    'أين ترغب بالإقامة؟': 'Where do you want to stay?',
                    'كل المحافظات والمدن': 'All Provinces & Cities',
                    'مستوى التصنيف': 'Star Rating',
                    'جميع التصنيفات': 'All Ratings',
                    'ابحث عن الفنادق': 'Search Hotels',
                    'استكشف الفندق': 'Explore Hotel',
                    'حجز الغرف': 'Book Rooms',
                    'غرف متاحة': 'Available rooms',
                    'ليلة': 'night',
                    'يبدأ سعر الإقامة من:': 'Starting from:',
                    'فندق خمس نجوم بإطلالة بحرية رائعة': '5-star hotel with stunning sea view',
                    'إطلالة بحرية رائعة': 'Stunning sea view',
                    'تقييم': 'reviews',
                    'واي فاي': 'Wi-Fi',
                    'تكييف': 'Air Conditioning',
                    'إفطار': 'Breakfast',
                    'مواقف': 'Parking',
                    'مسبح': 'Swimming Pool',
                    'مطعم': 'Restaurant',
                    'صالة رياضية': 'Gym',
                    'خدمة غرف': 'Room Service',
                    'أفضل الوجهات والمحافظات السياحية': 'Top Destinations & Provinces',
                    'استكشف أجمل المدن السورية وأرقى الفنادق والمنتجعات للإقامة': 'Explore Syrian cities and the finest hotels and resorts for your stay',

                    // Auth & Registration & Verification
                    'تسجيل الدخول': 'Sign In',
                    'إنشاء حساب جديد': 'Create New Account',
                    'إنشاء حساب': 'Register',
                    'تسجيل جديد': 'Register',
                    'تسجيل الخروج': 'Sign Out',
                    'البريد الإلكتروني': 'Email Address',
                    'كلمة المرور': 'Password',
                    'تأكيد كلمة المرور': 'Confirm Password',
                    'تذكرني': 'Remember Me',
                    'تسجيل الدخول عبر Google': 'Continue with Google',
                    'تسجيل الدخول السريع بحساب جوجل بضغطة زر واحدة': 'Quick 1-click Google Sign-in',
                    'أو التسجيل عبر البريد الإلكتروني': 'Or register via email',
                    'ليس لديك حساب؟': 'Don\'t have an account?',
                    'لديك حساب بالفعل؟': 'Already have an account?',
                    'تفعيل الحساب': 'Activate Account',
                    'رمز التحقق': 'Verification Code',
                    'رمز التحقق والتفعيل': 'Verification & Activation Code',
                    'أدخل رمز التحقق': 'Enter Verification Code',
                    'تم إرسال كود التفعيل المكون من 6 أرقام إلى بريدك الإلكتروني': 'A 6-digit verification code has been sent to your email',
                    'كود التحقق التجريبي السريع': 'Demo Quick-Fill Code',
                    'اضغط لتعبئة الكود تلقائياً': 'Click to auto-fill code',
                    'إعادة إرسال رمز جديد': 'Resend a new code',
                    'التحقق وتأكيد الحساب': 'Verify & Confirm Account',
                    'الاسم الكامل': 'Full Name',
                    'رقم الهاتف': 'Phone Number',
                    'الهاتف': 'Phone',

                    // Guest Portal (حجوزاتي، ملفي، العروض، الرسائل)
                    'حجوزاتي': 'My Bookings',
                    'سجل الحجوزات الخاصة بي': 'My Reservations History',
                    'ملفي الشخصي': 'My Profile',
                    'العروض الحالية': 'Current Offers',
                    'صندوق الرسائل': 'Message Mailbox',
                    'بوابة النزيل': 'Guest Portal',
                    'تعديل الملف الشخصي للنزيل': 'Edit Guest Profile',
                    'الاسم الأول': 'First Name',
                    'اسم الأب': 'Father Name',
                    'الكنية / العائلة': 'Last Name',
                    'الجنسية': 'Nationality',
                    'تاريخ الميلاد': 'Date of Birth',
                    'نوع الوثيقة': 'Document Type',
                    'بطاقة شخصية / هوية': 'National ID Card',
                    'جواز سفر': 'Passport',
                    'الرقم الوطني': 'National ID Number',
                    'رقم الوثيقة': 'Document / Passport Number',
                    'العنوان الدائم': 'Permanent Address',
                    'وثائق الهوية والجواز المرفقة': 'Uploaded Identity & Passport Documents',
                    'رفع صورة الهوية أو الجواز': 'Upload ID or Passport Document',
                    'يمكنك رفع صور أو ملفات PDF للبطاقة الشخصية أو جواز السفر': 'You can upload photos or PDF files of your ID or passport',
                    'حفظ وتحديث الملف الشخصي': 'Save & Update Profile',
                    'مدة الإقامة': 'Duration of Stay',
                    'تاريخ الوصول': 'Check-in Date',
                    'تاريخ المغادرة': 'Check-out Date',
                    'إجمالي المبلغ': 'Total Amount',
                    'عرض الفاتورة': 'View Invoice',
                    'إلغاء الحجز': 'Cancel Reservation',
                    'هل أنت متأكد من رغبتك في إلغاء هذا الحجز؟': 'Are you sure you want to cancel this booking?',
                    'لا توجد حجوزات حتى الآن': 'No bookings found yet',
                    'تصفح الفنادق واحجز إقامتك': 'Browse hotels and book your stay',
                    'عرض خاص': 'Special Offer',
                    'صالحة لغاية': 'Valid until',
                    'احجز الآن': 'Book Now',
                    'لا توجد عروض ترويجية نشطة حالياً': 'No active promotional offers right now',
                    'رسالة جديدة': 'New Message',
                    'صندوق الوارد': 'Inbox',
                    'الرسائل المرسلة': 'Sent Messages',
                    'الموضوع': 'Subject',
                    'نص الرسالة': 'Message Body',
                    'المرفق (صورة أو مستند)': 'Attachment (Image or Document)',
                    'إرسال الرسالة': 'Send Message',
                    'مراسلة إدارة الفندق': 'Message Hotel Management',
                    'مراسلة النزيل': 'Message Guest',
                    'تحميل المرفق': 'Download Attachment',

                    // Offers Admin
                    'العروض الترويجية': 'Promotional Offers',
                    'إضافة عرض ترويجي جديد': 'Add New Promotional Offer',
                    'تعديل العرض الترويجي': 'Edit Promotional Offer',
                    'عنوان العرض': 'Offer Title',
                    'تفاصيل وباقة العرض': 'Offer Details & Package Description',
                    'نسبة الخصم %': 'Discount Percentage %',
                    'السعر الخاص (ليرة سورية)': 'Special Price (SYP)',
                    'السعر الخاص ($ دولار)': 'Special Price ($ USD)',
                    'السعر الخاص (ل.س)': 'Special Price (SYP)',
                    'السعر الخاص ($ USD)': 'Special Price ($ USD)',
                    'تاريخ البدء': 'Start Date',
                    'تاريخ الانتهاء': 'End Date',
                    'صورة العرض': 'Offer Image',
                    'تفعيل العرض': 'Enable Offer',
                    'تعطيل': 'Disable',
                    'تفعيل': 'Enable',

                    // Hotel Visibility & Management
                    'ظاهر': 'Visible',
                    'مخفي': 'Hidden',
                    'إظهار الفندق': 'Show Hotel',
                    'إخفاء الفندق': 'Hide Hotel',
                    'الفنادق المشتركة': 'Subscribed Hotels',
                    'إدارة الفنادق': 'Hotel Management',
                    'إدارة الفندق': 'Hotel Management',
                    'بروفايل الفندق': 'Hotel Profile',
                    'لوحة الفندق': 'Hotel Dashboard',
                    'لوحة المنصة': 'Platform Dashboard',
                    'التقارير الشاملة': 'Comprehensive Reports',
                    'الحجوزات والتسكين': 'Bookings & Stays',
                    'المحاسبة والمالية': 'Finance & Accounts',
                    'التشغيل والخدمات': 'Operations & Services',
                    'أدوات النظام': 'System Tools',
                    'إعدادات المنصة': 'Platform Settings',
                    'شؤون الموظفين': 'Staff Management',

                    // Bank Statement & Reports
                    'كشف الحساب والستيتمنت': 'Bank Statement & Ledger',
                    'كشف حساب بنكي': 'Bank Account Statement',
                    'تقرير يومي': 'Daily Report',
                    'تقرير أسبوعي': 'Weekly Report',
                    'تقرير شهري': 'Monthly Report',
                    'تقرير سنوي': 'Annual Report',
                    'تقرير مخصص': 'Custom Report',
                    'صادر': 'Outflow / Debit',
                    'وارد': 'Inflow / Credit',
                    'الرصيد': 'Balance',
                    'الرصيد التراكمي': 'Cumulative Balance',
                    'صادر (ل.س)': 'Debit (SYP)',
                    'وارد (ل.س)': 'Credit (SYP)',
                    'صادر ($)': 'Debit ($ USD)',
                    'وارد ($)': 'Credit ($ USD)',
                    'رصيد الليرة السورية': 'SYP Balance',
                    'رصيد الدولار الأمريكي': 'USD Balance',
                    'المبالغ بالليرة': 'Amounts in SYP',
                    'المبالغ بالدولار': 'Amounts in USD',
                    'كشف الحساب المصرفي المفصل': 'Detailed Bank Account Statement',
                    'فترة التقرير': 'Report Period',
                    'حركات القبض والصرف والمصروفات': 'Inflows, outflows and cash movements',
                    'طباعة الكشف': 'Print Statement',
                    'تحميل PDF': 'Download PDF',
                    'تصدير Excel': 'Export Excel',

                    // Rooms, Bookings, Guests, Invoices
                    'الغرف': 'Rooms',
                    'إضافة غرفة': 'Add Room',
                    'أنواع الغرف': 'Room Types',
                    'إضافة نوع غرفة': 'Add Room Type',
                    'رقم الغرفة': 'Room Number',
                    'نوع الغرفة': 'Room Type',
                    'السعر بالليرة': 'Price in SYP',
                    'السعر بالدولار': 'Price in USD',
                    'سعر الإقامة بالليرة السورية': 'Price in Syrian Pounds',
                    'سعر الإقامة بالدولار الأمريكي': 'Price in US Dollars',
                    'الحجوزات': 'Bookings',
                    'إضافة حجز': 'New Booking',
                    'تفاصيل الحجز': 'Booking Details',
                    'تأكيد الحجز': 'Confirm Booking',
                    'تسجيل الدخول والتسكين': 'Check-in Guest',
                    'تسجيل الخروج وإنهاء الإقامة': 'Check-out & Finalize',
                    'تسجيل دخول': 'Check-in',
                    'تسجيل خروج': 'Check-out',
                    'سجل النزلاء': 'Guests Directory',
                    'إضافة نزيل': 'Add Guest',
                    'بيانات النزيل': 'Guest Details',
                    'اسم النزيل': 'Guest Name',
                    'رقم الهوية / الجواز': 'ID / Passport #',
                    'طباعة بطاقة النزيل': 'Print Guest Card',
                    'سجل الإقامات': 'Stay History',
                    'الفواتير': 'Invoices',
                    'إدارة الفواتير': 'Invoices Management',
                    'إصدار فاتورة جديدة': 'Issue New Invoice',
                    'رقم الفاتورة': 'Invoice Number',
                    'المبلغ': 'Amount',
                    'الضريبة': 'Tax',
                    'الخدمات الإضافية': 'Additional Services',
                    'الإجمالي': 'Total',
                    'العملة المعتمدة': 'Selected Currency',
                    'تسديد الفاتورة': 'Pay Invoice',
                    'تسديد': 'Pay',
                    'مدفوعة': 'Paid',
                    'غير مدفوعة': 'Unpaid',
                    'مسددة مسبقاً': 'Already Paid',
                    'طباعة الفاتورة': 'Print Invoice',

                    // Common UI
                    'حفظ': 'Save',
                    'حفظ التعديلات': 'Save Changes',
                    'تعديل': 'Edit',
                    'حذف': 'Delete',
                    'إلغاء': 'Cancel',
                    'تأكيد': 'Confirm',
                    'إضافة': 'Add',
                    'بحث': 'Search',
                    'عرض': 'View',
                    'طباعة': 'Print',
                    'تصدير': 'Export',
                    'تحميل': 'Download',
                    'العمليات': 'Actions',
                    'الإجراءات': 'Actions',
                    'الحالة': 'Status',
                    'السعر': 'Price',
                    'التاريخ': 'Date',
                    'الوصف': 'Description',
                    'ملاحظات': 'Notes',
                    'مدفوع': 'Paid',
                    'غير مدفوع': 'Unpaid',
                    'مسددة': 'Paid',
                    'مؤكد': 'Confirmed',
                    'ملغي': 'Cancelled',
                    'قيد الانتظار': 'Pending',
                    'حجز': 'Reserved',
                    'ليرة سورية': 'SYP',
                    'ل.س': 'SYP',
                    'دولار أمريكي': 'USD',
                    'دولار': 'USD',
                    'المحافظة': 'Province',
                    'العنوان': 'Address',
                    'الفندق': 'Hotel',
                    'الصيانة': 'Maintenance',
                    'التنظيف': 'Cleaning',
                    'التدبير الفندقي والتنظيف': 'Housekeeping & Cleaning',
                    'بلاغات الصيانة': 'Maintenance Requests',
                    'إضافة بلاغ صيانة': 'Add Maintenance Request',
                    'شكاوى النزلاء': 'Guest Complaints',
                    'إعدادات الطباعة والسكنر': 'Printers & Scanner Settings',
                    'النسخ الاحتياطي والاستعادة': 'Backup & Restore',
                    'سجل المحذوفات والرقابة': 'Deletion Logs & Audit',
                    'سجل العمليات والنشاطات': 'Activity & Audit Log',
                    'الرئيسية': 'Home',
                    'إغلاق': 'Close',
                    'رجوع': 'Back',
                    'متاح': 'Available',
                    'مشغول': 'Occupied',
                    'نعم': 'Yes',
                    'لا': 'No',
                    'الكل': 'All',
                    'اختر': 'Choose',
                    'من': 'From',
                    'إلى': 'To',
                };

                // 1. Recursive TreeWalker for all text nodes
                const walker = document.createTreeWalker(
                    document.body,
                    NodeFilter.SHOW_TEXT,
                    {
                        acceptNode: function(node) {
                            if (!node.nodeValue || !node.nodeValue.trim()) return NodeFilter.FILTER_REJECT;
                            const parent = node.parentElement;
                            if (!parent) return NodeFilter.FILTER_REJECT;
                            const tag = parent.tagName;
                            if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'CODE' || tag === 'PRE') {
                                return NodeFilter.FILTER_REJECT;
                            }
                            return NodeFilter.FILTER_ACCEPT;
                        }
                    }
                );

                const keys = Object.keys(dict).sort((a, b) => b.length - a.length);

                const textNodes = [];
                while (walker.nextNode()) {
                    textNodes.push(walker.currentNode);
                }

                textNodes.forEach(node => {
                    let val = node.nodeValue;
                    const trimmed = val.trim();
                    if (dict[trimmed]) {
                        node.nodeValue = val.replace(trimmed, dict[trimmed]);
                    } else {
                        // Replace matching phrases
                        for (let i = 0; i < keys.length; i++) {
                            const k = keys[i];
                            if (k.length > 2 && val.includes(k)) {
                                val = val.split(k).join(dict[k]);
                            }
                        }
                        node.nodeValue = val;
                    }
                });

                // 2. Translate input placeholders, textarea placeholders
                document.querySelectorAll('input[placeholder], textarea[placeholder]').forEach(el => {
                    const p = el.getAttribute('placeholder');
                    if (p && dict[p.trim()]) {
                        el.setAttribute('placeholder', dict[p.trim()]);
                    }
                });

                // 3. Translate select options
                document.querySelectorAll('select option').forEach(opt => {
                    const t = opt.textContent.trim();
                    if (dict[t]) {
                        opt.textContent = dict[t];
                    }
                });

                // 4. Translate button inputs
                document.querySelectorAll('input[type="submit"], input[type="button"]').forEach(btn => {
                    const v = btn.value.trim();
                    if (dict[v]) {
                        btn.value = dict[v];
                    }
                });
            })();
            @endif
        });
    </script>
</body>
</html>
