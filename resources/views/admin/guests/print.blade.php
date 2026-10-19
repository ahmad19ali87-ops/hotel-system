<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>بطاقة تسجيل نزيل - {{ $guest->full_name }}</title>
    
    <!-- Google Fonts: Tajawal -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
        }

        @media print {
            body {
                background-color: #ffffff;
                color: #000000;
            }
            .no-print {
                display: none !important;
            }
            .page-container {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 10mm !important;
                max-width: 100% !important;
            }
            @page {
                size: A4;
                margin: 10mm;
            }
        }
    </style>
</head>
<body class="p-4 sm:p-8">

    {{-- Control Buttons (Hidden on Print) --}}
    <div class="no-print mx-auto max-w-4xl mb-6 flex items-center justify-between bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex items-center gap-2">
            <span class="text-xl">🖨️</span>
            <div>
                <h3 class="font-bold text-slate-800 text-sm">معاينة طباعة بطاقة النزيل الرسمية</h3>
                <p class="text-xs text-slate-500">جاهزة للطباعة بجودة عالية على ورق A4 وتصدير PDF</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-bold text-sm rounded-xl shadow transition flex items-center gap-1.5">
                <span>🖨️</span>
                <span>طباعة الآن (Print)</span>
            </button>
            <button onclick="window.close()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-xl transition">
                إغلاق
            </button>
        </div>
    </div>

    {{-- Printable Card Container --}}
    <div class="page-container mx-auto max-w-4xl bg-white p-8 sm:p-12 rounded-3xl shadow-lg border border-slate-200">
        
        {{-- Header Section --}}
        <div class="flex items-start justify-between border-b-2 border-slate-800 pb-6 mb-6">
            <div class="flex items-center gap-4">
                @if($hotel && $hotel->logo_url)
                    <img src="{{ $hotel->logo_url }}" alt="logo" class="h-16 w-16 object-contain rounded-xl border border-slate-200 p-1">
                @else
                    <div class="h-16 w-16 rounded-xl bg-slate-900 text-white flex items-center justify-center text-3xl font-black">
                        🏨
                    </div>
                @endif
                <div>
                    <h1 class="text-2xl font-black text-slate-900">{{ $hotel->name ?? 'فندق' }}</h1>
                    @if($hotel && $hotel->stars)
                        <div class="text-amber-500 text-xs mt-0.5 tracking-wider">{{ str_repeat('★', $hotel->stars) }} فندق سياحي</div>
                    @endif
                    <p class="text-xs text-slate-500 mt-1">
                        <span>📍 {{ $hotel->address ?? $hotel->province ?? 'سوريا' }}</span>
                        @if($hotel && $hotel->phone) <span class="ms-3">📞 {{ $hotel->phone }}</span> @endif
                    </p>
                </div>
            </div>

            <div class="text-left">
                <span class="inline-block px-3 py-1 bg-slate-900 text-white text-xs font-black rounded-lg uppercase tracking-wider mb-1">
                    GUEST REGISTRATION CARD
                </span>
                <div class="text-lg font-black text-slate-900">بطاقة تسجيل نزيل</div>
                <div class="text-[11px] text-slate-400 mt-1">
                    تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}
                </div>
            </div>
        </div>

        {{-- Guest Main Identity Table --}}
        <div class="mb-6">
            <h2 class="text-sm font-black uppercase tracking-wider text-slate-800 bg-slate-100 px-3 py-1.5 rounded-lg mb-3">
                1. بيانات النزيل والهوية الشخصية (Guest Identity Details)
            </h2>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs border border-slate-200 rounded-2xl p-4 bg-slate-50/50">
                <div>
                    <span class="text-slate-400 block mb-0.5">الاسم والكنية:</span>
                    <strong class="text-sm font-black text-slate-900">{{ $guest->full_name }}</strong>
                </div>

                <div>
                    <span class="text-slate-400 block mb-0.5">اسم الأب:</span>
                    <strong class="text-sm font-bold text-slate-800">{{ $guest->father_name ?: '—' }}</strong>
                </div>

                <div>
                    <span class="text-slate-400 block mb-0.5">الجنسية:</span>
                    <strong class="text-sm font-bold text-slate-800">{{ $guest->nationality ?: 'سوري' }}</strong>
                </div>

                <div>
                    <span class="text-slate-400 block mb-0.5">تاريخ الميلاد:</span>
                    <strong class="text-sm font-bold text-slate-800">{{ $guest->birth_date ?: '—' }}</strong>
                </div>

                @php
                    $idTypeLabels = [
                        'national_id' => 'بطاقة شخصية / هوية وطنية',
                        'passport' => 'جواز سفر رسمي',
                        'residence' => 'بطاقة إقامة',
                        'driver_license' => 'رخصة قيادة',
                    ];
                @endphp

                <div>
                    <span class="text-slate-400 block mb-0.5">نوع الوثيقة:</span>
                    <strong class="text-slate-800 font-bold">{{ $idTypeLabels[$guest->id_type] ?? 'هوية شخصية' }}</strong>
                </div>

                <div>
                    <span class="text-slate-400 block mb-0.5">رقم الوثيقة / الجواز:</span>
                    <strong class="text-sm font-black text-blue-900 font-mono tracking-wider">{{ $guest->id_number ?: $guest->national_id ?: '—' }}</strong>
                </div>

                <div>
                    <span class="text-slate-400 block mb-0.5">رقم الهاتف:</span>
                    <strong class="text-slate-800 font-bold" dir="ltr">{{ $guest->phone ?: '—' }}</strong>
                </div>

                <div>
                    <span class="text-slate-400 block mb-0.5">البريد الإلكتروني:</span>
                    <strong class="text-slate-800 font-medium">{{ $guest->email ?: '—' }}</strong>
                </div>
            </div>

            @if($guest->notes)
                <div class="mt-2 text-xs p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900">
                    <strong class="font-bold">ملاحظات خاصة:</strong> {{ $guest->notes }}
                </div>
            @endif
        </div>

        {{-- Document Scans & Attachments Section --}}
        @php $attachments = $guest->attachments_list; @endphp
        <div class="mb-8">
            <h2 class="text-sm font-black uppercase tracking-wider text-slate-800 bg-slate-100 px-3 py-1.5 rounded-lg mb-3">
                2. صور الوثائق الرسمية والمستندات الممسوحة ضوئياً (Attached Documents & Scans)
            </h2>

            @if(count($attachments))
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($attachments as $att)
                        @if(in_array(strtolower($att['type']), ['jpg', 'jpeg', 'png', 'webp']))
                            <div class="rounded-2xl border-2 border-slate-300 overflow-hidden bg-slate-100 p-2 text-center shadow-xs">
                                <img src="{{ $att['url'] }}" alt="وثيقة" class="w-full max-h-72 object-contain rounded-xl bg-white">
                                <span class="text-[11px] font-bold text-slate-500 block mt-1.5">نسخة طبق الأصل للوثيقة الرسمية</span>
                            </div>
                        @else
                            <div class="rounded-2xl border border-slate-300 p-4 text-center bg-slate-50">
                                <span class="text-3xl block mb-1">📄</span>
                                <span class="text-xs font-bold text-slate-700 block">{{ $att['name'] }}</span>
                                <span class="text-[10px] text-slate-400 uppercase font-mono">{{ $att['type'] }} Document</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="p-6 text-center border border-dashed border-slate-300 rounded-2xl text-slate-400 text-xs">
                    لم يتم إرفاق صور وثائق رقمية في سجل هذا النزيل.
                </div>
            @endif
        </div>

        {{-- Declaration & Signatures --}}
        <div class="border-t-2 border-slate-800 pt-6 mt-8">
            <p class="text-[11px] text-slate-500 leading-relaxed text-justify mb-6">
                أقر أنا الموقع أدناه بصحة البيانات المدونة أعلاه والوثائق المرفقة، وباطلاعي وموافقتي على كافة الشروط واللوائح والتعليمات الداخلية الخاصة بالفندق والجهات المختصة.
            </p>

            <div class="grid grid-cols-2 gap-8 text-center text-xs">
                <div class="border-t border-slate-300 pt-3">
                    <span class="font-bold text-slate-800 block mb-12">توقيع النزيل (Guest Signature)</span>
                    <span class="text-[11px] text-slate-400">...................................................</span>
                </div>

                <div class="border-t border-slate-300 pt-3">
                    <span class="font-bold text-slate-800 block mb-12">ختم وتوقيع موظف الاستقبال (Receptionist)</span>
                    <span class="text-[11px] text-slate-400">...................................................</span>
                </div>
            </div>
        </div>

        {{-- Footer Note --}}
        <div class="mt-8 pt-4 border-t border-slate-100 text-center text-[10px] text-slate-400">
            نظام إدارة الفنادق © 2026 | وثيقة ضيافة رسمية معتمدة
        </div>

    </div>

</body>
</html>

