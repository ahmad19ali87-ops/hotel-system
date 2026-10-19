<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'تنبيه في النظام') - نظام إدارة الفنادق</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            margin: 0;
            color: #1e293b;
        }

        .error-card {
            background: #ffffff;
            border-radius: 1.25rem;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.08), 0 0 1px 1px rgba(0, 0, 0, 0.05);
            max-width: 580px;
            width: 100%;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .error-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #ef4444, #f59e0b, #3b82f6);
        }

        .error-icon-box {
            width: 84px;
            height: 84px;
            margin: 0 auto 1.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.75rem;
            box-shadow: 0 10px 20px -5px rgba(0,0,0,0.06);
        }

        .error-code {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .error-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: #0f172a;
        }

        .error-message {
            font-size: 1rem;
            line-height: 1.7;
            color: #475569;
            margin-bottom: 1.75rem;
        }

        .action-btns {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
        }

        .support-box {
            margin-top: 2rem;
            padding: 0.85rem 1rem;
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 0.75rem;
            font-size: 0.85rem;
            color: #64748b;
        }
    </style>
</head>
<body>

    <div class="error-card">
        <div class="error-icon-box @yield('icon-bg', 'bg-warning-subtle text-warning')">
            @yield('icon', '⚠️')
        </div>

        <div class="error-code text-muted">
            رمز التنبيه: @hasSection('code') @yield('code') @else {{ $code ?? 'خطأ في العملية' }} @endif
        </div>

        <h1 class="error-title">
            @hasSection('title') @yield('title') @else {{ $title ?? 'تنبيه: العملية خاطئة أو غير معرفة' }} @endif
        </h1>

        <div class="error-message">
            @hasSection('message') @yield('message') @else {{ $message ?? 'نعتذر، يبدو أن العملية المطلوبة غير صحيحة أو أن الرابط غير معرف في النظام. يرجى مراجعة مشغل البرنامج أو المحاولة لاحقاً.' }} @endif
        </div>

        <div class="action-btns">
            <button onclick="window.history.back()" class="btn btn-outline-secondary px-4 py-2 d-inline-flex align-items-center gap-1">
                <span>↩️</span>
                <span>الرجوع للخلف</span>
            </button>

            @auth
                @if(in_array(auth()->user()->role, ['hotel_admin', 'admin']))
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-1">
                        <span>🏢</span>
                        <span>لوحة التحكم الرئيسية</span>
                    </a>
                @elseif(auth()->user()->role === 'super_admin')
                    <a href="{{ route('platform.dashboard') }}" class="btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-1">
                        <span>🌐</span>
                        <span>لوحة المنصة</span>
                    </a>
                @else
                    <a href="{{ route('home') }}" class="btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-1">
                        <span>🏨</span>
                        <span>الرئيسية</span>
                    </a>
                @endif
            @else
                <a href="{{ route('home') }}" class="btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-1">
                    <span>🏨</span>
                    <span>الصفحة الرئيسية</span>
                </a>
            @endauth

            <a href="javascript:location.reload()" class="btn btn-light border px-3 py-2" title="إعادة المحاولة">
                🔄
            </a>
        </div>

        <div class="support-box">
            <span>💡 <strong>إرشاد:</strong> إذا كنت تعتقد أن هذا الأمر ينبغي أن يعمل بشكل طبيعي، يرجى التواصل مع <strong>مشغل البرنامج</strong> وإبلاغه بنوع الإجراء الذي كنت تقوم به.</span>
        </div>
    </div>

</body>
</html>

