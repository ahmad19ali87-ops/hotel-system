<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة المغادرة وتفاصيل الإقامة - {{ $data['hotel_name'] ?? 'الفندق' }}</title>
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
            background: linear-gradient(135deg, #064e3b 0%, #047857 60%, #059669 100%);
            color: #ffffff;
            padding: 36px 28px;
            text-align: center;
        }
        .header .badge {
            display: inline-block;
            background-color: rgba(254, 240, 138, 0.25);
            border: 1px solid #fef08a;
            color: #fef08a;
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
            color: #d1fae5;
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
            width: 40%;
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
        .btn-emerald {
            display: inline-block;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 800;
            text-align: center;
            box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25);
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
            <span class="badge">🚪 تم إتمام تسجيل المغادرة (Check-Out)</span>
            <h1>شكراً لإقامتك في فندق {{ $data['hotel_name'] ?? 'الفندق' }}</h1>
            <p>فاتورة إلكترونية موثقة · نسعد دائماً بخدمتك ونتطلع لزيارتك القادمة</p>
        </div>

        {{-- Content --}}
        <div class="content">
            <div class="greeting">شكراً لك يا {{ $data['guest_name'] }}،</div>
            <p class="intro-text">
                يسرنا إعلامك بأنه تم إتمام إجراءات تسجيل المغادرة وتسديد فاتورة إقامتك في فندق <strong>{{ $data['hotel_name'] }}</strong> بنجاح. نأمل أن تكون قد حظيت بتجربة ضيافة ممتعة ومريحة!
            </p>

            {{-- Invoice & Departure Summary Card --}}
            <div class="card">
                <div class="card-title">
                    <span>🧾 تفاصيل الفاتورة والمغادرة</span>
                </div>
                <table class="info-table">
                    <tr>
                        <td class="label">رقم الفاتورة:</td>
                        <td class="value"><span style="font-family: monospace; font-size: 15px; color: #1e3a8a;">{{ $data['invoice_number'] }}</span></td>
                    </tr>
                    <tr>
                        <td class="label">رقم الحجز والغرفة:</td>
                        <td class="value">حجز #{{ $booking->id }} · غرفة رقم {{ $data['room_number'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">فترة الإقامة:</td>
                        <td class="value">من {{ $data['check_in'] }} إلى {{ $data['check_out'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">تاريخ ووقت المغادرة:</td>
                        <td class="value">{{ $data['checked_out_at'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">طريقة السداد:</td>
                        <td class="value">{{ $data['payment_method'] }}</td>
                    </tr>
                    @if(!empty($data['service_amount']) && $data['service_amount'] > 0)
                        <tr>
                            <td class="label">رسوم خدمات إضافية:</td>
                            <td class="value">
                                {{ $data['invoice_currency'] === 'USD' ? '$' . number_format($data['service_amount'], 2) : number_format($data['service_amount']) . ' ل.س' }}
                                @if(!empty($data['service_description']))
                                    <span style="font-size: 11px; color: #64748b;">({{ $data['service_description'] }})</span>
                                @endif
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">إجمالي المبلغ المسدد:</td>
                        <td class="value" style="color: #059669; font-size: 17px; font-weight: 900;">
                            {{ $data['invoice_currency'] === 'USD' ? '$' . number_format($data['invoice_total'], 2) . ' USD' : number_format($data['invoice_total']) . ' ل.س' }}
                            <span style="font-size: 11px; background-color: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 6px; font-weight: bold; margin-right: 6px;">مسددة بالكامل ✔</span>
                        </td>
                    </tr>
                </table>

                @if(!empty($data['invoice_url']))
                    <div style="margin-top: 18px; text-align: center; border-top: 1px dashed #cbd5e1; padding-top: 16px;">
                        <a href="{{ $data['invoice_url'] }}" class="btn-emerald" target="_blank">
                            🧾 استعراض الفاتورة الإلكترونية والباركود المعتمد
                        </a>
                    </div>
                @endif
            </div>

            {{-- CONDITION 1: Guest is ALREADY a registered user in DB --}}
            @if($data['is_existing_user'])
                <div class="highlight-box box-existing">
                    <div class="box-title">
                        <span>⭐ تقييم إقامتك ومكافآت الأعضاء</span>
                    </div>
                    <p class="box-desc">
                        نتقدم لك بجزيل الشكر باسم إدارة المنصة وفريق فندق <strong>{{ $data['hotel_name'] }}</strong>. يسعدنا جداً مشاركتنا تقييمك ورأيك حول الإقامة لمساعدتنا في تقديم أفضل معايير الضيافة باستمرار.
                    </p>
                    <div style="margin-top: 14px;">
                        @if(!empty($data['review_url']))
                            <a href="{{ $data['review_url'] }}" class="btn-gold" target="_blank">
                                ⭐ قيّم الفندق وشارك تجربتك
                            </a>
                        @endif
                        <a href="{{ $data['apps_url'] }}" class="btn-outline" target="_blank">
                            📲 إدارة الحجوزات المستقبلية عبر التطبيق
                        </a>
                    </div>
                </div>

            {{-- CONDITION 2: Guest is NEW / NOT previously registered as user --}}
            @else
                <div class="highlight-box box-new">
                    <div class="box-title">
                        <span>🎁 حفظ سجل إقامتك وتفعيل حسابك الفندقي</span>
                    </div>
                    <p class="box-desc">
                        لقد تم حفظ وتوثيق سجل إقامتك وفاتورتك هذه ضمن حساب عضويتك الفندقية الجديد (<strong>{{ $data['guest_email'] }}</strong>). لتفعيل حسابك، وحفظ إقاماتك القادمة، والحصول على أسعار وعروض حصرية:
                    </p>
                    <p class="box-desc" style="font-weight: 700; color: #1e3a8a;">
                        يرجى الضغط على الزر التالي لتعيين كلمة المرور الخاصة بحسابك:
                    </p>
                    <div style="margin: 16px 0;">
                        @if(!empty($data['password_setup_url']))
                            <a href="{{ $data['password_setup_url'] }}" class="btn-gold" target="_blank">
                                🔐 تعيين كلمة المرور وتفعيل حسابك الآن
                            </a>
                        @else
                            <a href="{{ $data['site_url'] }}/forgot-password" class="btn-gold" target="_blank">
                                🔐 تفعيل حسابك وتعيين كلمة المرور
                            </a>
                        @endif
                    </div>
                    <p style="font-size: 13px; color: #475569; margin: 0;">
                        بمجرد تفعيل حسابك، يمكنك الدخول للتطبيق والموقع لحجز إقاماتك القادمة بلمسة واحدة والاستفادة من باقات الخصم المخصصة للنزلاء الدائمين.
                    </p>
                </div>
            @endif

            {{-- Apps Download CTA Banner --}}
            <div class="app-badges">
                <div class="app-badges-title">📲 احجز إقامتك القادمة بلمسة واحدة عبر التطبيق</div>
                <div class="app-badges-desc">
                    استكشف أفخم الفنادق والوجهات السياحية، وقارن الأسعار بالليرة والدولار، واستفد من عروض اللحظة الأخيرة الحصرية عبر تطبيقنا الذكي.
                </div>
                <a href="{{ $data['apps_url'] }}" class="btn-outline" target="_blank">
                    📥 تحميل تطبيق الهاتف والكمبيوتر
                </a>
                <a href="{{ $data['site_url'] }}/offers" class="btn-outline" target="_blank">
                    🏷️ استعراض العروض الفندقية الحالية
                </a>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p style="margin: 0 0 6px 0;">
                تم إصدار هذا الإشعار والفاتورة تلقائياً من المنظومة الفندقية الموحدة لإدارة الحجوزات وفندق <strong>{{ $data['hotel_name'] }}</strong>.
            </p>
            <p style="margin: 0;">
                <a href="{{ $data['site_url'] }}">الرئيسية</a> ·
                <a href="{{ $data['site_url'] }}/pricing">باقات المنصة</a> ·
                <a href="{{ $data['site_url'] }}/offers">العروض والخصومات</a> ·
                <a href="{{ $data['apps_url'] }}">تحميل التطبيق</a>
            </p>
        </div>
    </div>
</body>
</html>

