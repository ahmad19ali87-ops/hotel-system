<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\Request;

class PrinterSettingController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        abort_unless($user && $user->hotel_id, 403, 'غير مصرح.');

        $hotel = Hotel::findOrFail($user->hotel_id);
        $settings = $hotel->printer_settings_list;

        return view('admin.settings.printer', compact('hotel', 'settings'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        abort_unless($user && $user->hotel_id, 403, 'غير مصرح.');

        $hotel = Hotel::findOrFail($user->hotel_id);

        $validated = $request->validate([
            'invoice_printer' => ['nullable', 'string', 'max:150'],
            'report_printer' => ['nullable', 'string', 'max:150'],
            'invoice_format' => ['required', 'string', 'in:thermal_80mm,a4_standard'],
            'scanner_source' => ['required', 'string', 'in:camera,scanner_hardware,custom'],
            'scanner_device_id' => ['nullable', 'string', 'max:255'],
            'auto_print' => ['nullable', 'boolean'],
            'print_header_logo' => ['nullable', 'boolean'],
            'footer_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $settingsData = [
            'invoice_printer' => $validated['invoice_printer'] ?? 'طابعة الفواتير الافتراضية',
            'report_printer' => $validated['report_printer'] ?? 'طابعة التقارير الافتراضية',
            'invoice_format' => $validated['invoice_format'],
            'scanner_source' => $validated['scanner_source'],
            'scanner_device_id' => $validated['scanner_device_id'] ?? null,
            'auto_print' => $request->has('auto_print'),
            'print_header_logo' => $request->has('print_header_logo'),
            'footer_notes' => $validated['footer_notes'] ?? null,
        ];

        $hotel->printer_settings = json_encode($settingsData, JSON_UNESCAPED_UNICODE);
        $hotel->save();

        return redirect()->route('admin.printer-settings.edit')->with('success', 'تم حفظ إعدادات الطابعات وجهاز السكنر بنجاح.');
    }
}

