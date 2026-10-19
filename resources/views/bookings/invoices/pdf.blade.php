<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 14px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .hotel-name {
            font-size: 24px;
            font-weight: bold;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 10px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 6px 0;
        }
        .total-box {
            margin-top: 20px;
            padding: 10px;
            border: 2px solid #000;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #555;
        }
    </style>
</head>

<body>

    <!-- رأس الفاتورة -->
    <div class="header">
        <div class="hotel-name">فندق النخبة</div>
        <div>شارع العليا – الرياض</div>
        <div>هاتف: 0555555555</div>
    </div>

    <!-- رقم الفاتورة -->
    <div class="section-title">فاتورة رقم {{ $invoice->id }}</div>

    <!-- بيانات العميل -->
    <table class="info-table">
        <tr><td><strong>اسم العميل:</strong> {{ $invoice->booking->guest->name }}</td></tr>
        <tr><td><strong>الهاتف:</strong> {{ $invoice->booking->guest->phone }}</td></tr>
        <tr><td><strong>البريد الإلكتروني:</strong> {{ $invoice->booking->guest->email }}</td></tr>
        <tr><td><strong>رقم الهوية:</strong> {{ $invoice->booking->guest->national_id }}</td></tr>
    </table>

    <!-- بيانات الحجز -->
    <div class="section-title">تفاصيل الحجز</div>

    <table class="info-table">
        <tr><td><strong>رقم الغرفة:</strong> {{ $invoice->room->room_number }}</td></tr>
        <tr><td><strong>تاريخ الدخول:</strong> {{ $invoice->booking->check_in }}</td></tr>
        <tr><td><strong>تاريخ الخروج:</strong> {{ $invoice->booking->check_out }}</td></tr>
    </table>

    <!-- تفاصيل الفاتورة -->
    <div class="section-title">تفاصيل الفاتورة</div>

    <table class="info-table">
        <tr><td><strong>المبلغ قبل الضريبة:</strong> {{ $invoice->amount }} ريال</td></tr>
        <tr><td><strong>الضريبة (15%):</strong> {{ $invoice->tax }} ريال</td></tr>
    </table>

    <!-- الإجمالي -->
    <div class="total-box">
        الإجمالي النهائي: {{ $invoice->total }} ريال
    </div>

    <!-- تذييل -->
    <div class="footer">
        شكراً لاختياركم فندق النخبة — نتمنى لكم إقامة سعيدة
    </div>

</body>
</html>
