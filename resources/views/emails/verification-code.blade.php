<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كود تفعيل الحساب</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px;
            direction: rtl;
            text-align: right;
            color: #1e293b;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #003580;
            color: #ffffff;
            padding: 30px 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 6px 0 0 0;
            font-size: 14px;
            color: #febb02;
            font-weight: 600;
        }
        .content {
            padding: 32px 28px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .message {
            font-size: 15px;
            line-height: 1.6;
            color: #475569;
            margin-bottom: 24px;
        }
        .code-box {
            background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
            border: 2px dashed #003580;
            border-radius: 14px;
            padding: 24px;
            text-align: center;
            margin: 24px 0;
        }
        .code-title {
            font-size: 13px;
            font-weight: 700;
            color: #003580;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .code-number {
            font-size: 38px;
            font-weight: 900;
            letter-spacing: 8px;
            color: #003580;
            font-family: 'Consolas', 'Courier New', monospace;
            margin: 0;
        }
        .note {
            font-size: 13px;
            color: #64748b;
            background-color: #f8fafc;
            border-right: 4px solid #febb02;
            padding: 12px 16px;
            border-radius: 4px;
            margin-top: 24px;
            line-height: 1.5;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 24px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>بوابة حجز الفنادق الذكية</h1>
            <p>Smart Hotel Reservation Portal</p>
        </div>
        <div class="content">
            <div class="greeting">مرحباً بك، {{ $user->name ?? 'عزيزنا النزيل' }}! 👋</div>
            <div class="message">
                شكراً لانضمامك إلى بوابتنا الذكية لحجز الفنادق. لتأكيد وتفعيل حسابك والبدء في استكشاف الفنادق وحجز الغرف، يرجى استخدام رمز التحقق أدناه في شاشة التفعيل:
            </div>

            <div class="code-box">
                <div class="code-title">رمز التفعيل الخاص بك (Verification Code)</div>
                <div class="code-number">{{ $code }}</div>
            </div>

            <div class="note">
                🔒 <strong>تنبيه أمني:</strong> لا تشارك رمز التفعيل مع أي شخص آخر. هذا الرمز صالح للاستخدام مرة واحدة لتفعيل حسابك الشخصي.
            </div>
        </div>
        <div class="footer">
            تم إرسال هذا البريد تلقائياً من: <strong>info@ahmadinsights.online</strong><br>
            بوابة حجز الفنادق الذكية &copy; {{ date('Y') }} جميع الحقوق محفوظة.
        </div>
    </div>
</body>
</html>

