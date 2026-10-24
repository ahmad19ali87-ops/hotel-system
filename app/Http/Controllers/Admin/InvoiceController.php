<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\DeletionLog;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::where('hotel_id', $this->hotelId())
            ->with(['booking.guest', 'room', 'payments'])
            ->latest()
            ->get();

        return view('admin.invoices.index', compact('invoices'));
    }

    public function create()
    {
        $bookings = Booking::where('hotel_id', $this->hotelId())
            ->whereDoesntHave('invoices')
            ->with('guest', 'room')
            ->latest()
            ->get();

        return view('admin.invoices.create', compact('bookings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => ['required', 'integer', Rule::exists('bookings', 'id')->where('hotel_id', $this->hotelId())],
            'currency' => ['required', Rule::in(['SYP', 'USD'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'service_amount' => ['nullable', 'numeric', 'min:0'],
            'service_description' => ['nullable', 'string', 'max:1000'],
            'tax_name' => ['nullable', 'string', 'max:100'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $booking = Booking::with('room')->where('hotel_id', $this->hotelId())->findOrFail($validated['booking_id']);
        $currency = $validated['currency'];
        $amount = (float) $validated['amount'];
        $serviceAmount = (float) ($validated['service_amount'] ?? 0);
        $taxRate = (float) ($validated['tax_rate'] ?? 0);
        $taxName = $taxRate > 0 ? (trim($validated['tax_name'] ?? '') ?: 'ضريبة') : null;
        $taxAmount = round(($amount + $serviceAmount) * $taxRate / 100, 2);
        $total = $amount + $serviceAmount + $taxAmount;

        $invoice = Invoice::create([
            'hotel_id' => $booking->hotel_id,
            'booking_id' => $booking->id,
            'room_id' => $booking->room_id,
            'amount' => $amount,
            'service_amount' => $serviceAmount,
            'service_description' => $validated['service_description'] ?? null,
            'tax' => $taxAmount,
            'tax_name' => $taxName,
            'tax_rate' => $taxRate,
            'total' => $total,
            'currency' => $currency,
            'status' => 'unpaid',
        ]);

        return redirect()->route('invoices.show', $invoice->id)->with('success', 'تم إنشاء الفاتورة بنجاح بالعملة المحددة.');
    }

    public function show(Invoice $invoice)
    {
        $this->ensureInvoiceBelongsToHotel($invoice);
        $invoice->load(['hotel', 'booking.guest', 'booking.creator', 'room', 'payments']);

        $publicUrl = URL::route('invoice.by-hotel', [
            'hotelSlug' => $invoice->hotel->slug,
            'invoice' => $invoice->id,
        ]);
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(130)->margin(1)->generate($publicUrl);

        return view('admin.invoices.show', compact('invoice', 'publicUrl', 'qrCode'));
    }

    public function markPaid(Request $request, Invoice $invoice, InvoicePdfService $pdfService)
    {
        $this->ensureInvoiceBelongsToHotel($invoice);
        $validated = $request->validate([
            'payment_method' => ['required', Rule::in(['cash', 'card', 'transfer'])],
            'transaction_id' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($invoice, $validated) {
            $lockedInvoice = Invoice::where('hotel_id', $this->hotelId())
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedInvoice->status === 'paid') {
                return;
            }

            Payment::create([
                'invoice_id' => $lockedInvoice->id,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'payment_method' => $validated['payment_method'],
                'amount' => $lockedInvoice->total,
                'currency' => $lockedInvoice->currency ?? 'SYP',
                'status' => 'success',
                'paid_at' => now(),
            ]);

            $lockedInvoice->update(['status' => 'paid']);

            Account::firstOrCreate(
                ['invoice_id' => $lockedInvoice->id],
                [
                    'hotel_id' => $lockedInvoice->hotel_id,
                    'type' => 'income',
                    'title' => 'تحصيل فاتورة ' . $lockedInvoice->invoice_number . ' (' . ($lockedInvoice->currency === 'USD' ? '$' : 'ل.س') . ')',
                    'amount' => $lockedInvoice->total,
                    'currency' => $lockedInvoice->currency ?? 'SYP',
                    'transaction_date' => now()->toDateString(),
                    'notes' => 'دفع حجز رقم ' . $lockedInvoice->booking_id . ' (' . ($lockedInvoice->currency ?: 'SYP') . ')',
                ]
            );
        });

        $invoice->refresh();
        $pdfService->store($invoice);

        return redirect()->route('invoices.show', $invoice)->with('status', 'تم تأكيد الدفع وحفظ الفاتورة PDF.');
    }

    public function downloadPdf(Invoice $invoice, InvoicePdfService $pdfService)
    {
        $this->ensureInvoiceBelongsToHotel($invoice);

        if (! $invoice->pdf_path || ! Storage::disk('public')->exists($invoice->pdf_path)) {
            $pdfService->store($invoice);
            $invoice->refresh();
        }

        return Storage::disk('public')->download(
            $invoice->pdf_path,
            $invoice->id . '.pdf'
        );
    }

    public function publicShow(Invoice $invoice)
    {
        return $this->renderPublicInvoice($invoice);
    }

    public function publicShowByHotel(string $hotelSlug, $invoice)
    {
        $invoiceModel = Invoice::where(function ($q) use ($invoice) {
            $q->where('id', $invoice)->orWhere('invoice_number', $invoice);
        })->firstOrFail();

        abort_unless($invoiceModel->hotel && strtolower(rtrim($invoiceModel->hotel->slug)) === strtolower(rtrim($hotelSlug)), 404);

        return $this->renderPublicInvoice($invoiceModel);
    }

    private function renderPublicInvoice(Invoice $invoice)
    {
        $invoice->load(['hotel', 'booking.guest', 'booking.creator', 'room', 'payments']);
        $hotelPhones = $invoice->hotel?->phones ? (json_decode($invoice->hotel->phones, true) ?: []) : [];
        if (! $hotelPhones && $invoice->hotel?->phone) {
            $hotelPhones = [$invoice->hotel->phone];
        }

        $publicUrl = URL::route('invoice.by-hotel', [
            'hotelSlug' => $invoice->hotel->slug,
            'invoice' => $invoice->invoice_number ?: $invoice->id,
        ]);
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(130)->margin(1)->generate($publicUrl);

        return view('public.invoice', compact('invoice', 'hotelPhones', 'publicUrl', 'qrCode'));
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        $this->ensureInvoiceBelongsToHotel($invoice);

        $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'reason.required' => 'يرجى توضيح سبب حذف الفاتورة.',
            'reason.min' => 'سبب الحذف يجب ألا يقل عن 3 أحرف.',
        ]);

        $invoice->load(['booking.guest', 'room', 'payments']);

        DB::transaction(function () use ($invoice, $request) {
            DeletionLog::create([
                'hotel_id' => $invoice->hotel_id,
                'user_id' => auth()->id(),
                'record_type' => 'invoice',
                'record_id' => $invoice->id,
                'reference_number' => $invoice->invoice_number ?: ('فاتورة #' . $invoice->id),
                'guest_name' => $invoice->booking?->guest?->name ?? '—',
                'room_number' => (string) ($invoice->room?->room_number ?? '—'),
                'amount' => (float) $invoice->total,
                'payment_status' => $invoice->status,
                'reason' => $request->input('reason'),
                'snapshot' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'booking_id' => $invoice->booking_id,
                    'room_number' => $invoice->room?->room_number,
                    'guest_name' => $invoice->booking?->guest?->name,
                    'guest_phone' => $invoice->booking?->guest?->phone,
                    'amount' => $invoice->amount,
                    'service_amount' => $invoice->service_amount,
                    'tax' => $invoice->tax,
                    'total' => $invoice->total,
                    'status' => $invoice->status,
                    'payments' => $invoice->payments->toArray(),
                ],
                'deleted_at' => now(),
            ]);

            Account::where('invoice_id', $invoice->id)->delete();
            $invoice->payments()->delete();
            $invoice->delete();
        });

        return redirect()
            ->route('invoices.index')
            ->with('status', 'تم حذف الفاتورة وتوثيق العملية في سجل المحذوفات.');
    }


    private function hotelId(): int
    {
        abort_unless(auth()->user()?->hotel_id, 403);

        return (int) auth()->user()->hotel_id;
    }

    private function ensureInvoiceBelongsToHotel(Invoice $invoice): void
    {
        abort_unless((int) $invoice->hotel_id === $this->hotelId(), 404);
    }
}