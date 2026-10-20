<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استعادة كلمة المرور</title>
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
        .btn-container {
            text-align: center;
            margin: 28px 0;
        }
        .btn {
            display: inline-block;
            background-color: #003580;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 4px 10px rgba(0, 53, 128, 0.25);
        }
        .code-box {
            background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
            border: 2px dashed #003580;
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            margin: 24px 0;
        }
        .code-title {
            font-size: 13px;
            font-weight: 700;
            color: #003580;
            margin-bottom: 6px;
        }
        .code-number {
            font-size: 34px;
            font-weight: 900;
            letter-spacing: 6px;
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
        .link-text {
            word-break: break-all;
            direction: ltr;
            text-align: left;
            font-size: 12px;
            color: #2563eb;
            margin-top: 10px;
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
            <p>طلب استعادة كلمة المرور</p>
        </div>
        
        <div class="content">
            <div class="greeting">مرحباً {{ $user->name }}،</div>
            <div class="message">
                تلقينا طلباً لاستعادة كلمة المرور الخاصة بحسابك المسجل لدينا. يمكنك تعيين كلمة مرور جديدة بالضغط على الزر أدناه:
            </div>
            
            <div class="btn-container">
                <a href="{{ $resetUrl }}" class="btn" target="_blank">
                    🔑 إعادة تعيين كلمة المرور
                </a>
            </div>

            <div class="code-box">
                <div class="code-title">أو استخدم كود الاستعادة التالي في تطبيق الجوال:</div>
                <div class="code-number">{{ $code }}</div>
            </div>

            <div class="message" style="font-size: 13px; color: #64748b; margin-top: 20px;">
                إذا كان الزر لا يعمل، يمكنك نسخ الرابط التالي ولصقه في المتصفح:
                <div class="link-text">{{ $resetUrl }}</div>
            </div>

            <div class="note">
                <strong>ملاحظة أمان:</strong> صلاحية هذا الرابط والكود تنتهي خلال 60 دقيقة. إذا لم تكن أنت من طلب استعادة كلمة المرور، يمكنك تجاهل هذه الرسالة وسيبقى حسابك آمناً.
            </div>
        </div>

        <div class="footer">
            هذه رسالة آلية تم إرسالها من <strong>info@ahmadinsights.online</strong><br>
            بوابة حجز الفنادق الذكية &copy; {{ date('Y') }} جميع الحقوق محفوظة.
        </div>
    </div>
</body>
</html>
