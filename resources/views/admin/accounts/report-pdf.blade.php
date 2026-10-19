<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>كشف حساب مالي - {{ $hotel->name }}</title>
    <style>
        body {
            font-family: dejavusans, sans-serif;
            direction: rtl;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .hotel-name {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
        }
        .hotel-sub {
            font-size: 9px;
            color: #64748b;
        }
        .doc-badge {
            text-align: left;
            direction: ltr;
        }
        .badge-box {
            display: inline-block;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 4px 8px;
            font-weight: bold;
            font-size: 11px;
            color: #0f172a;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 14px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .meta-table td {
            padding: 6px 10px;
            font-size: 9px;
        }
        .meta-label {
            color: #64748b;
            display: block;
        }
        .meta-val {
            font-weight: bold;
            color: #0f172a;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .summary-table td {
            width: 25%;
            padding: 8px;
            border: 1px solid #cbd5e1;
            text-align: center;
        }
        .summary-label {
            font-size: 8px;
            font-weight: bold;
            color: #64748b;
            display: block;
            margin-bottom: 4px;
        }
        .summary-val {
            font-size: 12px;
            font-weight: bold;
        }
        .val-inflow { color: #047857; }
        .val-outflow { color: #b91c1c; }
        .val-closing { color: #1d4ed8; }

        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .ledger-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            padding: 6px 8px;
            border: 1px solid #1e293b;
            text-align: right;
        }
        .ledger-table td {
            border: 1px solid #e2e8f0;
            padding: 5px 8px;
            font-size: 8.5px;
            text-align: right;
        }
        .ledger-table tr.opening-row td {
            background-color: #f1f5f9;
            font-weight: bold;
        }
        .ledger-table tr:nth-child(even) {
            background-color: #fcfcfd;
        }
        .ledger-table tfoot td {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            padding: 6px 8px;
            border: 1px solid #0f172a;
        }
        .text-center { text-align: center !important; }
        .text-left { text-align: left !important; }
        .text-danger { color: #b91c1c; font-weight: bold; }
        .text-success { color: #047857; font-weight: bold; }
        .text-muted { color: #94a3b8; }
        .currency-tag {
            background: #e2e8f0;
            padding: 1px 4px;
            font-size: 8px;
            border-radius: 3px;
        }

        .signatures-table {
            width: 100%;
            margin-top: 30px;
            text-align: center;
        }
        .signatures-table td {
            width: 33.33%;
            padding: 10px;
            vertical-align: top;
        }
        .sig-line {
            border-bottom: 1px dashed #94a3b8;
            width: 120px;
            margin: 30px auto 5px auto;
        }
    </style>
</head>
<body>

    {{-- Official Header --}}
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="hotel-name">{{ $hotel->name }}</div>
                <div class="hotel-sub">قسم الإدارة المالية والمحاسبية · FINANCIAL DEPARTMENT</div>
                <div style="font-size: 8.5px; color: #475569; margin-top: 4px;">
                    📍 {{ $hotel->address ?? 'الجمهورية العربية السورية' }} · هاتف: {{ $hotel->phone ?? '—' }}
                </div>
            </td>
            <td style="width: 40%;" class="doc-badge">
                <div class="badge-box">كشف حساب مالي · ACCOUNT STATEMENT</div>
                <div style="font-size: 8px; color: #64748b; margin-top: 4px;">
                    REF: STMT-{{ date('Ymd', strtotime($to)) }}-{{ str_pad($hotel->id, 4, '0', STR_PAD_LEFT) }}<br>
                    DATE: {{ now()->format('Y-m-d H:i') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Meta Details --}}
    <table class="meta-table">
        <tr>
            <td style="width: 25%;">
                <span class="meta-label">نوع التقرير</span>
                <span class="meta-val">{{ $periodLabel }}</span>
            </td>
            <td style="width: 30%;">
                <span class="meta-label">الفترة المغطاة</span>
                <span class="meta-val">{{ $from }} إلى {{ $to }}</span>
            </td>
            <td style="width: 25%;">
                <span class="meta-label">العملة</span>
                <span class="meta-val">{{ $currency === 'all' ? 'جميع العملات (SYP / USD)' : ($currency === 'USD' ? 'الدولار الأمريكي ($)' : 'الليرة السورية (ل.س)') }}</span>
            </td>
            <td style="width: 20%;">
                <span class="meta-label">عدد الحركات</span>
                <span class="meta-val">{{ count($statementRows) }} عملية</span>
            </td>
        </tr>
    </table>

    {{-- Bank Statement Summary Box --}}
    <table class="summary-table">
        <tr>
            <td style="background-color: #f8fafc;">
                <span class="summary-label">الرصيد الافتتاحي (السابق)</span>
                @if($currency === 'all')
                    <div class="summary-val" style="font-size: 10px;">{{ number_format($openingBalanceSYP, 2) }} <span style="font-size: 8px; font-weight: normal; color: #64748b;">ل.س</span></div>
                    <div class="summary-val" style="font-size: 10px; color: #1d4ed8;">${{ number_format($openingBalanceUSD, 2) }} <span style="font-size: 8px; font-weight: normal;">USD</span></div>
                @elseif($currency === 'USD')
                    <span class="summary-val" style="color: #1d4ed8;">${{ number_format($openingBalanceUSD, 2) }} USD</span>
                @else
                    <span class="summary-val">{{ number_format($openingBalanceSYP, 2) }} ل.س</span>
                @endif
            </td>
            <td style="background-color: #f0fdf4;">
                <span class="summary-label">إجمالي الوارد (دائن / إيرادات)</span>
                @if($currency === 'all')
                    <div class="summary-val val-inflow" style="font-size: 10px;">+{{ number_format($totalInflowSYP, 2) }} <span style="font-size: 8px; font-weight: normal;">ل.س</span></div>
                    <div class="summary-val val-inflow" style="font-size: 10px;">+${{ number_format($totalInflowUSD, 2) }} <span style="font-size: 8px; font-weight: normal;">USD</span></div>
                @elseif($currency === 'USD')
                    <span class="summary-val val-inflow">+${{ number_format($totalInflowUSD, 2) }} USD</span>
                @else
                    <span class="summary-val val-inflow">+{{ number_format($totalInflowSYP, 2) }} ل.س</span>
                @endif
            </td>
            <td style="background-color: #fef2f2;">
                <span class="summary-label">إجمالي الصادر (مدين / مصروفات)</span>
                @if($currency === 'all')
                    <div class="summary-val val-outflow" style="font-size: 10px;">-{{ number_format($totalOutflowSYP, 2) }} <span style="font-size: 8px; font-weight: normal;">ل.س</span></div>
                    <div class="summary-val val-outflow" style="font-size: 10px;">-${{ number_format($totalOutflowUSD, 2) }} <span style="font-size: 8px; font-weight: normal;">USD</span></div>
                @elseif($currency === 'USD')
                    <span class="summary-val val-outflow">-${{ number_format($totalOutflowUSD, 2) }} USD</span>
                @else
                    <span class="summary-val val-outflow">-{{ number_format($totalOutflowSYP, 2) }} ل.س</span>
                @endif
            </td>
            <td style="background-color: #eff6ff;">
                <span class="summary-label">الرصيد الختامي الحالي</span>
                @if($currency === 'all')
                    <div class="summary-val val-closing" style="font-size: 10px;">{{ number_format($closingBalanceSYP, 2) }} <span style="font-size: 8px; font-weight: normal; color: #1e293b;">ل.س</span></div>
                    <div class="summary-val val-closing" style="font-size: 10px;">${{ number_format($closingBalanceUSD, 2) }} <span style="font-size: 8px; font-weight: normal;">USD</span></div>
                @elseif($currency === 'USD')
                    <span class="summary-val val-closing">${{ number_format($closingBalanceUSD, 2) }} USD</span>
                @else
                    <span class="summary-val val-closing">{{ number_format($closingBalanceSYP, 2) }} ل.س</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- Statement Ledger Table --}}
    <table class="ledger-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 12%;">التاريخ</th>
                <th style="width: 15%;">رقم المرجع</th>
                <th style="width: 30%;">البيان والتفاصيل</th>
                <th style="width: 8%;" class="text-center">العملة</th>
                <th style="width: 10%;" class="text-left">صادر (مدين)</th>
                <th style="width: 10%;" class="text-left">وارد (دائن)</th>
                <th style="width: 10%;" class="text-left">الرصيد</th>
            </tr>
        </thead>
        <tbody>
            {{-- Opening Balance Row --}}
            <tr class="opening-row">
                <td class="text-center text-muted">—</td>
                <td>{{ $from }}</td>
                <td>START-BAL</td>
                <td><strong>📌 الرصيد الافتتاحي المرحل (Opening Balance)</strong></td>
                <td class="text-center">{{ $currency === 'all' ? 'SYP / USD' : $currency }}</td>
                <td class="text-left text-muted">—</td>
                <td class="text-left text-muted">—</td>
                <td class="text-left">
                    @if($currency === 'all')
                        <div>{{ number_format($openingBalanceSYP, 2) }} <span style="font-size: 7.5px; color: #64748b;">ل.س</span></div>
                        <div style="color: #1d4ed8;">${{ number_format($openingBalanceUSD, 2) }} <span style="font-size: 7.5px;">USD</span></div>
                    @elseif($currency === 'USD')
                        <div style="color: #1d4ed8;">${{ number_format($openingBalanceUSD, 2) }} USD</div>
                    @else
                        <div>{{ number_format($openingBalanceSYP, 2) }} ل.س</div>
                    @endif
                </td>
            </tr>

            {{-- Entries --}}
            @forelse($statementRows as $idx => $row)
                <tr>
                    <td class="text-center text-muted">{{ $idx + 1 }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['ref'] }}</td>
                    <td>
                        <strong>{{ $row['title'] }}</strong>
                        @if($row['notes'])
                            <div style="font-size: 7.5px; color: #64748b;">{{ $row['notes'] }}</div>
                        @endif
                    </td>
                    <td class="text-center"><span class="currency-tag">{{ $row['currency'] === 'USD' ? '$' : 'ل.س' }}</span></td>
                    <td class="text-left text-danger">
                        @if($row['outflow'] > 0)
                            -{{ number_format($row['outflow'], 2) }} <span style="font-size: 7px;">{{ $row['currency'] === 'USD' ? '$' : 'ل.س' }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-left text-success">
                        @if($row['inflow'] > 0)
                            +{{ number_format($row['inflow'], 2) }} <span style="font-size: 7px;">{{ $row['currency'] === 'USD' ? '$' : 'ل.س' }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-left">
                        @if($currency === 'all')
                            <div><strong>{{ number_format($row['balance_syp'], 2) }}</strong> <span style="font-size: 7px; color: #64748b;">ل.س</span></div>
                            <div style="color: #1d4ed8;"><strong>${{ number_format($row['balance_usd'], 2) }}</strong> <span style="font-size: 7px;">USD</span></div>
                        @elseif($currency === 'USD')
                            <div style="color: #1d4ed8;"><strong>${{ number_format($row['balance_usd'], 2) }}</strong> USD</div>
                        @else
                            <div><strong>{{ number_format($row['balance_syp'], 2) }}</strong> ل.س</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 15px; color: #64748b;">
                        لا توجد حركات مالية مسجلة خلال الفترة المحددة.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align: right;">الإجمالي والرصيد الختامي للفترة (Total & Closing Balance)</td>
                <td class="text-left" style="color: #fca5a5;">
                    @if($currency === 'all')
                        <div>-{{ number_format($totalOutflowSYP, 2) }} <span style="font-size: 7.5px;">ل.س</span></div>
                        <div>-${{ number_format($totalOutflowUSD, 2) }} <span style="font-size: 7.5px;">USD</span></div>
                    @elseif($currency === 'USD')
                        -${{ number_format($totalOutflowUSD, 2) }} USD
                    @else
                        -{{ number_format($totalOutflowSYP, 2) }} ل.س
                    @endif
                </td>
                <td class="text-left" style="color: #86efac;">
                    @if($currency === 'all')
                        <div>+{{ number_format($totalInflowSYP, 2) }} <span style="font-size: 7.5px;">ل.س</span></div>
                        <div>+${{ number_format($totalInflowUSD, 2) }} <span style="font-size: 7.5px;">USD</span></div>
                    @elseif($currency === 'USD')
                        +${{ number_format($totalInflowUSD, 2) }} USD
                    @else
                        +{{ number_format($totalInflowSYP, 2) }} ل.س
                    @endif
                </td>
                <td class="text-left" style="color: #ffffff; font-size: 8.5px;">
                    @if($currency === 'all')
                        <div>{{ number_format($closingBalanceSYP, 2) }} ل.س</div>
                        <div style="color: #93c5fd;">${{ number_format($closingBalanceUSD, 2) }} USD</div>
                    @elseif($currency === 'USD')
                        ${{ number_format($closingBalanceUSD, 2) }} USD
                    @else
                        {{ number_format($closingBalanceSYP, 2) }} ل.س
                    @endif
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- Signatures Section --}}
    <table class="signatures-table">
        <tr>
            <td>
                <strong>المحاسب المعتمد</strong>
                <div class="sig-line"></div>
                <span style="font-size: 8px; color: #94a3b8;">التوقيع والاسم</span>
            </td>
            <td>
                <strong>مدير الفندق</strong>
                <div class="sig-line"></div>
                <span style="font-size: 8px; color: #94a3b8;">الاعتماد والختم</span>
            </td>
            <td>
                <strong>ختم التدقيق المالي</strong>
                <div class="sig-line"></div>
                <span style="font-size: 8px; color: #94a3b8;">الختم الرسمي</span>
            </td>
        </tr>
    </table>

</body>
</html>