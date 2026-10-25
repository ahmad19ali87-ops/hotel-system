<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد تسجيل الوصول - {{ $data['hotel_name'] ?? 'الفندق' }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 20px 10px;
            direction: rtl;
            text-align: right;
            color: #1e293b;
        }
        .container {
            max-width: 620px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #1d4ed8 100%);
            color: #ffffff;
            padding: 36px 28px;
            text-align: center;
        }
        .header .badge {
            display: inline-block;
            background-color: rgba(254, 187, 2, 0.2);
            border: 1px solid #febb02;
            color: #febb02;
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 900;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 8px 0 0 0;
            font-size: 14px;
            color: #cbd5e1;
        }
        .content {
            padding: 32px 28px;
        }
        .greeting {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .intro-text {
            font-size: 15px;
            line-height: 1.7;
            color: #475569;
            margin-bottom: 24px;
        }
        .card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .card-title {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 8px 4px;
            font-size: 14px;
            vertical-align: middle;
        }
        .info-table .label {
            color: #64748b;
            font-weight: 600;
            width: 35%;
        }
        .info-table .value {
            color: #0f172a;
            font-weight: 700;
        }
        .highlight-box {
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 24px;
        }
        .box-existing {
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        .box-new {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
        }
        .box-title {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .box-desc {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 16px;
        }
        .btn-primary {
            display: inline-block;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 13px 26px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 800;
            text-align: center;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
        }
        .btn-gold {
            display: inline-block;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 13px 26px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 800;
            text-align: center;
            box-shadow: 0 4px 10px rgba(245, 158, 11, 0.25);
        }
        .btn-outline {
            display: inline-block;
            background-color: #ffffff;
            color: #1e293b !important;
            text-decoration: none;
            padding: 11px 20px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            text-align: center;
            margin-right: 8px;
        }
        .app-badges {
            text-align: center;
            background-color: #f1f5f9;
            border-radius: 14px;
            padding: 18px;
            margin-top: 24px;
        }
        .app-badges-title {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .app-badges-desc {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 14px;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 24px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.6;
        }
        .footer a {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <span class="badge">🛎️ تم تسجيل الوصول بنجاح (Check-In)</span>
            <h1>أهلاً بك في فندق {{ $data['hotel_name'] ?? 'الفندق' }}</h1>
            <p>حجز رقم #{{ $booking->id }} · نتمنى لك إقامة ممتعة ومريحة</p>
        </div>

        {{-- Content --}}
        <div class="content">
            <div class="greeting">مرحباً بك يا {{ $data['guest_name'] }}،</div>
            <p class="intro-text">
                يسعدنا إعلامك بأنه تم إتمام إجراءات تسجيل الوصول وتسكينك في الغرفة بنجاح. بيانات إقامتك أصبحت موثقة في النظام، ويسرنا تزويدك بالتفاصيل الكاملة:
            </p>

            {{-- Stay Details Card --}}
            <div class="card">
                <div class="card-title">
                    <span>📋 تفاصيل الحجز والإقامة</span>
                </div>
                <table class="info-table">
                    <tr>
                        <td class="label">اسم الفندق:</td>
                        <td class="value">{{ $data['hotel_name'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">رقم الغرفة:</td>
                        <td class="value">
                            <span style="font-size: 16px; color: #1d4ed8;">غرفة رقم {{ $data['room_number'] }}</span>
                            @if(!empty($data['room_type']))
                                <span style="font-size: 12px; color: #64748b;">({{ $data['room_type'] }})</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="label">تاريخ الدخول:</td>
                        <td class="value">{{ $data['check_in'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">تاريخ الخروج المتوقع:</td>
                        <td class="value">{{ $data['check_out'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">مدة الإقامة:</td>
                        <td class="value">{{ $data['nights'] }} {{ $data['nights'] > 1 ? 'ليالي' : 'ليلة واحدة' }}</td>
                    </tr>
                    <tr>
                        <td class="label">إجمالي تكلفة الإقامة:</td>
                        <td class="value" style="color: #059669; font-size: 15px;">
                            {{ $data['currency'] === 'USD' ? '$' . number_format($data['total_price'], 2) . ' USD' : number_format($data['total_price']) . ' ل.س' }}
                        </td>
                    </tr>
                </table>
            </div>

            {{-- CONDITION 1: Guest is ALREADY a registered user in DB --}}
            @if($data['is_existing_user'])
                <div class="highlight-box box-existing">
                    <div class="box-title">
                        <span>🌟 شكراً لولائك واختيارك الدائم لمنصتنا!</span>
                    </div>
                    <p class="box-desc">
                        يسر إدارة الموقع والمنصة الفندقية تقديم خالص الشكر والامتنان لاختيارك منصتنا مجدداً لحجز إقاماتك. كعضو معتمد، يمكنك إدارة هذا الحجز ومتابعة كافة خدمات غرفتك مباشرة عبر حسابك الشخصي أو تطبيق الهاتف.
                    </p>
                    <div style="margin-top: 14px;">
                        <a href="{{ $data['portal_url'] }}" class="btn-primary" target="_blank">
                            📂 استعراض حجزك في لوحة التحكم
                        </a>
                        <a href="{{ $data['apps_url'] }}" class="btn-outline" target="_blank">
                            📲 تطبيق الهاتف المحمول
                        </a>
                    </div>
                </div>

            {{-- CONDITION 2: Guest is NEW / NOT previously registered as user --}}
            @else
                <div class="highlight-box box-new">
                    <div class="box-title">
                        <span>🎉 تم إنشاء حساب عضويتك الفندقية بنجاح!</span>
                    </div>
                    <p class="box-desc">
                        شكراً لاختيارك فندق <strong>{{ $data['hotel_name'] }}</strong> عبر بوابتنا الفندقية. لقد تم إنشاء حساب مسجل لك تلقائياً ببيانات حجزك وبريدك (<strong>{{ $data['guest_email'] }}</strong>) لتتمكن من متابعة حجزك وتنزيل فواتيرك والاستفادة من خصومات النزلاء الحصرية!
                    </p>
                    <p class="box-desc" style="font-weight: 700; color: #1e3a8a;">
                        لإتمام تفعيل حسابك وتعيين كلمة المرور الخاصة بك، يرجى الضغط على الزر أدناه:
                    </p>
                    <div style="margin: 16px 0;">
                        @if(!empty($data['password_setup_url']))
                            <a href="{{ $data['password_setup_url'] }}" class="btn-gold" target="_blank">
                                🔐 تعيين كلمة المرور وتفعيل حسابك فوراً
                            </a>
                        @else
                            <a href="{{ $data['site_url'] }}/forgot-password" class="btn-gold" target="_blank">
                                🔐 تفعيل حسابك وتعيين كلمة المرور
                            </a>
                        @endif
                    </div>
                    <p style="font-size: 13px; color: #475569; margin: 0;">
                        بعد تعيين كلمة المرور، يمكنك تسجيل الدخول إلى الموقع أو التطبيق في أي وقت لحجز إقاماتك القادمة والحصول على باقات الأسعار التفضيلية.
                    </p>
                </div>
            @endif

            {{-- Hotel & Assistance Info --}}
            <div class="card" style="margin-bottom: 0;">
                <div class="card-title">
                    <span>🏨 معلومات ومساعدة الفندق</span>
                </div>
                <table class="info-table">
                    @if(!empty($data['hotel_phone']))
                        <tr>
                            <td class="label">هاتف الاستقبال:</td>
                            <td class="value"><a href="tel:{{ $data['hotel_phone'] }}" style="color: #2563eb; text-decoration: none;">{{ $data['hotel_phone'] }}</a></td>
                        </tr>
                    @endif
                    @if(!empty($data['hotel_address']))
                        <tr>
                            <td class="label">العنوان والموقع:</td>
                            <td class="value">{{ $data['hotel_address'] }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">موعد المغادرة:</td>
                        <td class="value">حتى الساعة 12:00 ظهراً من يوم {{ $data['check_out'] }}</td>
                    </tr>
                </table>
            </div>

            {{-- Apps Download CTA Banner --}}
            <div class="app-badges">
                <div class="app-badges-title">📲 حمّل تطبيق الحجوزات الفندقية الذكي</div>
                <div class="app-badges-desc">
                    تابع إقاماتك لحظياً، استعرض الفنادق المتاحة بالليرة السورية والدولار، واحصل على تأكيد حجز فوري مع فواتير إلكترونية موثقة بباركود!
                </div>
                <a href="{{ $data['apps_url'] }}" class="btn-outline" target="_blank">
                    📥 استعراض وتحميل التطبيق (Android & Desktop)
                </a>
                <a href="{{ $data['site_url'] }}" class="btn-outline" target="_blank">
                    🌐 زيارة الموقع الرسمي
                </a>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p style="margin: 0 0 6px 0;">
                تم إرسال هذا البريد الإلكتروني تلقائياً من المنظومة الفندقية الموحدة لإدارة الحجوزات وفندق <strong>{{ $data['hotel_name'] }}</strong>.
            </p>
            <p style="margin: 0;">
                <a href="{{ $data['site_url'] }}">الرئيسية</a> ·
                <a href="{{ $data['site_url'] }}/pricing">الاشتراكات والباقات</a> ·
                <a href="{{ $data['site_url'] }}/offers">العروض الحالية</a> ·
                <a href="{{ $data['apps_url'] }}">تحميل التطبيقات</a>
            </p>
        </div>
    </div>
</body>
</html>

