<?php

namespace App\Services;

use App\Models\Invoice;
use App\Support\StorageFolderManager;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InvoicePdfService
{
    public function store(Invoice $invoice): string
    {
        $invoice->loadMissing(['hotel', 'booking.guest', 'room', 'payments']);
        $publicUrl = URL::route('invoice.by-hotel', [
            'hotelSlug' => $invoice->hotel->slug,
            'invoice' => $invoice->id,
        ]);
        $qrCode = QrCode::format('svg')->size(180)->margin(1)->generate($publicUrl);
        $logoData = $this->hotelLogoData($invoice);

        $html = view('admin.invoices.pdf', compact('invoice', 'publicUrl', 'qrCode', 'logoData'))->render();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'directionality' => 'rtl',
            'default_font' => 'dejavusans',
            'tempDir' => storage_path('app/mpdf-temp'),
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 12,
        ]);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle('فاتورة ' . $invoice->invoice_number);
        $mpdf->WriteHTML($html);

        $hotelDirectory = Str::slug($invoice->hotel->slug ?: $invoice->hotel->name);
        StorageFolderManager::ensureHotelDirectories($hotelDirectory);

        $path = $hotelDirectory . '/invoices/' . $invoice->id . '.pdf';
        Storage::disk('public')->put($path, $mpdf->Output('', Destination::STRING_RETURN));

        $invoice->forceFill(['pdf_path' => $path])->save();

        return $path;
    }

    private function hotelLogoData(Invoice $invoice): ?string
    {
        $logo = $invoice->hotel?->logo;
        if (! $logo) {
            return null;
        }

        if (Storage::disk('public')->exists($logo)) {
            $contents = Storage::disk('public')->get($logo);
            $mime = Storage::disk('public')->mimeType($logo) ?: 'image/png';
        } else {
            $legacyPath = public_path('uploads/hotels/' . $logo);
            if (! is_file($legacyPath)) {
                return null;
            }
            $contents = file_get_contents($legacyPath);
            $mime = mime_content_type($legacyPath) ?: 'image/png';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }
}