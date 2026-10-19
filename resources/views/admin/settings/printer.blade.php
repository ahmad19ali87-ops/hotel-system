@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl py-6 px-4">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-blue-600 block mb-1">لوحة تحكم الفندق</span>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900">إعدادات أجهزة الطباعة والمسح الضوئي (السكنر)</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">تحديد طابعة الفواتير وطابعة التقارير وتعيين جهاز السكنر أو الكاميرا لنسخ وثائق النزلاء</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary btn-sm font-medium">العودة للوحة الفندق</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.printer-settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- 1. Invoice Printer Card --}}
        <div class="card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <h2 class="text-lg font-extrabold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <span>🧾</span>
                    <span>طابعة الفواتير وإيصالات الدفع (Invoice Printer)</span>
                </span>
                <button type="button" onclick="testPrintInvoice()" class="btn btn-outline-primary btn-sm text-xs font-bold">
                    🖨️ طباعة إيصال تجريبي
                </button>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">اسم أو وجهة طابعة الفواتير</label>
                    <input type="text" name="invoice_printer" id="invoice_printer_input" value="{{ old('invoice_printer', $settings['invoice_printer'] ?? 'طابعة الفواتير الافتراضية') }}" placeholder="مثال: Epson TM-T20III أو طابعة الكاشير الحرارية" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <span class="text-[11px] text-slate-400 mt-1 block">يمكنك كتابة اسم الطابعة المثبتة على جهاز الكمبيوتر لتوجيه الطباعة إليها</span>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">نمط وتنسيق ورقة الفاتورة</label>
                    <select name="invoice_format" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="thermal_80mm" @selected(($settings['invoice_format'] ?? '') === 'thermal_80mm')>
                            🧾 طابعة إيصالات حرارية (Thermal POS 80mm) - مناسبة للكاشير والاستقبال
                        </option>
                        <option value="a4_standard" @selected(($settings['invoice_format'] ?? '') === 'a4_standard')>
                            📄 طابعة أوراق قياسية (Standard A4) - فواتير ضريبية كاملة
                        </option>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-6 pt-3 border-t border-slate-100">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="auto_print" value="1" @checked(!empty($settings['auto_print'])) class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4">
                    <span class="text-xs font-bold text-slate-800">فتح أمر الطباعة تلقائياً عند إتمام تسجيل المغادرة (Checkout)</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="print_header_logo" value="1" @checked(!empty($settings['print_header_logo'])) class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4">
                    <span class="text-xs font-bold text-slate-800">تضمين شعار الفندق في أعلى الإيصال المطبوع</span>
                </label>
            </div>
        </div>

        {{-- 2. Reports Printer Card --}}
        <div class="card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <h2 class="text-lg font-extrabold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <span>📊</span>
                    <span>طابعة التقارير المالية والإدارية (Reports Printer)</span>
                </span>
                <button type="button" onclick="testPrintReport()" class="btn btn-outline-primary btn-sm text-xs font-bold">
                    🖨️ طباعة تقرير تجريبي
                </button>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">اسم أو وجهة طابعة التقارير</label>
                    <input type="text" name="report_printer" id="report_printer_input" value="{{ old('report_printer', $settings['report_printer'] ?? 'طابعة تقارير A4 الافتراضية') }}" placeholder="مثال: HP LaserJet Pro A4 أو Microsoft Print to PDF" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <span class="text-[11px] text-slate-400 mt-1 block">تستخدم في طباعة كشوف الحسابات البنكية، الكشوفات اليومية، وسجلات المحذوفات</span>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">تذييل ثابت أسفل التقارير والفواتير</label>
                    <input type="text" name="footer_notes" value="{{ old('footer_notes', $settings['footer_notes'] ?? 'شكراً لإقامتكم معنا | نتمنى لكم رحلة سعيدة') }}" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
            </div>
        </div>

        {{-- 3. Document Scanner & Camera Hardware Selection Card --}}
        <div class="card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-2 border-b border-slate-100">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
                        <span>📹</span>
                        <span>تعيين جهاز مسح وثائق النزلاء (السكنر أو الكاميرا)</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">حدد هل ترغب باستخدام كاميرا الجهاز، أو جهاز سكنر نسخ خارجي متصل عبر USB</p>
                </div>
                <button type="button" id="btn_detect_devices" class="btn btn-outline-success btn-sm font-bold d-flex align-items-center gap-1.5">
                    <span>🔍</span>
                    <span>كشف أجهزة السكنر والكاميرات المتصلة الآن</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">نوع ومصدر جهاز المسح الافتراضي</label>
                    <select name="scanner_source" id="scanner_source_select" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="camera" @selected(($settings['scanner_source'] ?? '') === 'camera')>
                            📷 كاميرا الجهاز الافتراضية (Webcam / Laptop Camera)
                        </option>
                        <option value="scanner_hardware" @selected(($settings['scanner_source'] ?? '') === 'scanner_hardware')>
                            🖨️ جهاز مسح ضوئي خارجي (Document Scanner / USB Capture Device)
                        </option>
                        <option value="custom" @selected(($settings['scanner_source'] ?? '') === 'custom')>
                            ⚙️ جهاز محدد يدويًا من القائمة المكتشفة
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">جهاز الفيديو / السكنر المكتشف بالجهاز</label>
                    <select name="scanner_device_id" id="detected_devices_select" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        @if(!empty($settings['scanner_device_id']))
                            <option value="{{ $settings['scanner_device_id'] }}">الجهاز المحفوظ حالياً ({{ Str::limit($settings['scanner_device_id'], 25) }})</option>
                        @else
                            <option value="">-- اضغط زر الكشف أعلاه للتعرف على الأجهزة المتصلة --</option>
                        @endif
                    </select>
                    <span id="detected_status_label" class="text-[11px] text-slate-500 mt-1 block">سيتم تفعيل هذا الجهاز تلقائياً عند فتح سكنر وثائق النزلاء.</span>
                </div>
            </div>

            {{-- Live Scanner Test Preview --}}
            <div id="hardware_test_box" class="hidden p-4 rounded-2xl bg-slate-900 text-white border border-slate-800">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-emerald-400">● اختبار البث المباشر للجهاز المختار</span>
                    <button type="button" onclick="stopHardwareTest()" class="text-xs text-slate-400 hover:text-white">إيقاف المعاينة</button>
                </div>
                <div class="w-full max-w-sm mx-auto aspect-video rounded-xl overflow-hidden bg-black border border-slate-700">
                    <video id="test_hardware_video" autoplay playsinline class="w-full h-full object-cover"></video>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-light px-4 py-2 text-sm font-bold">العودة للوحة الفندق</a>
            @if(auth()->user()->role !== 'hotel_owner')
                <button type="submit" class="btn btn-primary px-6 py-2.5 text-sm font-bold shadow-sm">
                    💾 حفظ إعدادات الطابعات والأجهزة
                </button>
            @else
                <span class="badge bg-warning text-dark font-bold px-4 py-2.5 rounded-xl">👑 مالك الفندق - وضع الاطلاع فقط</span>
            @endif
        </div>
    </form>
</div>

<script>
let testStream = null;

// الكشف التلقائي عن أجهزة الفيديو والسكنر
document.getElementById('btn_detect_devices')?.addEventListener('click', async function() {
    const select = document.getElementById('detected_devices_select');
    const statusLabel = document.getElementById('detected_status_label');

    try {
        // طلب إذن مؤقت للكشف عن أسماء الأجهزة الحقيقية
        const tempStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        tempStream.getTracks().forEach(t => t.stop());

        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(d => d.kind === 'videoinput');

        select.innerHTML = '';
        if (videoDevices.length === 0) {
            select.innerHTML = '<option value="">لم يتم العثور على أجهزة إدخال متصلة</option>';
            statusLabel.textContent = 'تأكد من توصيل الكاميرا أو السكنر بجهاز الكمبيوتر ومنح الإذن في المتصفح.';
            return;
        }

        videoDevices.forEach((dev, idx) => {
            const opt = document.createElement('option');
            opt.value = dev.deviceId;
            opt.textContent = (dev.label || `جهاز مسح رقم ${idx + 1}`) + ` [${dev.deviceId ? dev.deviceId.substring(0, 8) : ''}]`;
            select.appendChild(opt);
        });

        statusLabel.textContent = `تم اكتشاف ${videoDevices.length} أجهزة متصلة بنجاح (كاميرات وماسحات ضوئية).`;
        statusLabel.classList.add('text-emerald-600', 'font-bold');

        // اختبار تشغيل الجهاز الأول فورياً
        testSelectedDevice(videoDevices[0].deviceId);

    } catch (err) {
        alert('تعذر كشف الأجهزة: ' + err.message + '\nيرجى التأكد من السماح للمتصفح بالوصول للأجهزة.');
    }
});

async function testSelectedDevice(deviceId) {
    stopHardwareTest();
    const testBox = document.getElementById('hardware_test_box');
    const video = document.getElementById('test_hardware_video');
    try {
        const constraints = deviceId ? { video: { deviceId: { exact: deviceId } } } : { video: true };
        testStream = await navigator.mediaDevices.getUserMedia(constraints);
        video.srcObject = testStream;
        testBox.classList.remove('hidden');
    } catch (e) {
        console.error(e);
    }
}

function stopHardwareTest() {
    if (testStream) {
        testStream.getTracks().forEach(t => t.stop());
        testStream = null;
    }
    const testBox = document.getElementById('hardware_test_box');
    if (testBox) testBox.classList.add('hidden');
}

// اختبار طباعة الفاتورة والتقرير
function testPrintInvoice() {
    const printer = document.getElementById('invoice_printer_input').value || 'طابعة الفواتير';
    const printWindow = window.open('', '_blank', 'width=400,height=600');
    printWindow.document.write(`
        <html dir="rtl">
        <head><title>اختبار طابعة الفواتير</title>
        <style>body{font-family:sans-serif;text-align:center;padding:20px;font-size:12px;}</style>
        </head>
        <body>
            <h3>🧾 اختبار طابعة الفواتير</h3>
            <p><strong>الموجّه إلى:</strong> ${printer}</p>
            <p>تاريخ الاختبار: ${new Date().toLocaleString('ar-SA')}</p>
            <hr>
            <p>إيصال تجريبي لاختبار سلامة الاتصال وجودة الطباعة الحرارية.</p>
            <p>================================</p>
            <p>تم بنجاح ✓</p>
            <script>window.onload = function() { window.print(); }<\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

function testPrintReport() {
    const printer = document.getElementById('report_printer_input').value || 'طابعة التقارير';
    const printWindow = window.open('', '_blank', 'width=800,height=800');
    printWindow.document.write(`
        <html dir="rtl">
        <head><title>اختبار طابعة التقارير A4</title>
        <style>body{font-family:sans-serif;padding:40px;line-height:1.6;}</style>
        </head>
        <body>
            <h2>📊 صفحة اختبار طابعة التقارير (A4)</h2>
            <p><strong>الطابعة المستهدفة:</strong> ${printer}</p>
            <p><strong>التاريخ والوقت:</strong> ${new Date().toLocaleString('ar-SA')}</p>
            <hr>
            <p>هذه صفحة تجريبية لاختبار مقاس الصفحة وهوامش الطباعة وتوافق الطابعة المكتبية المحددة.</p>
            <table border="1" cellpadding="8" style="width:100%;border-collapse:collapse;margin-top:20px;">
                <tr style="background:#f1f5f9;"><th>البند</th><th>الوصف</th><th>الحالة</th></tr>
                <tr><td>الاتصال بالطابعة</td><td>توجيه الطباعة إلى ${printer}</td><td>ناجح ✓</td></tr>
                <tr><td>تنسيق الخطوط</td><td>دعم اللغة العربية والترميز UTF-8</td><td>سليم ✓</td></tr>
            </table>
            <script>window.onload = function() { window.print(); }<\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}
</script>
@endsection

