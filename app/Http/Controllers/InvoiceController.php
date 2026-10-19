<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;


class InvoiceController extends Controller
{

public function index($hotelSlug)
{
    $hotel = Hotel::where('slug', $hotelSlug)->firstOrFail();

    $invoices = Invoice::where('hotel_id', $hotel->id)->get();

    return view('invoices.index', compact('invoices', 'hotel'));
}

public function store(Request $request, $hotelSlug)
{
    $hotel = Hotel::where('slug', $hotelSlug)->firstOrFail();

    Invoice::create([
        'hotel_id' => $hotel->id,
        'booking_id' => $request->booking_id,
        'total' => $request->total,
        'status' => 'paid',
    ]);

    return redirect()->route('invoices.index', $hotelSlug);
}


    public function indexAll()
    {
        // تحميل الفواتير مع الغرفة والحجز والعميل
        $invoices = Invoice::with('room', 'booking.guest')->get();

        return view('invoices.index', compact('invoices'));
    }

    public function show($id)
{
    $invoice = Invoice::with(['booking.room', 'booking.guest'])->findOrFail($id);

    // حساب الإجمالي
    $subtotal = $invoice->amount;
    $tax = $invoice->tax;
    $discount = $invoice->discount;
    $total = $subtotal + $tax - $discount;

    return view('invoices.show', compact('invoice', 'subtotal', 'tax', 'discount', 'total'));
}
public function occupancy()
{
    $rooms = Room::all();

    $total = $rooms->count();
    $available = $rooms->where('status', 'available')->count();
    $booked = $rooms->where('status', 'booked')->count();
    $cleaning = $rooms->where('status', 'cleaning')->count();
    $maintenance = $rooms->where('status', 'maintenance')->count();
    $hotel = Hotel::where('slug', $hotelSlug)->firstOrFail();

Room::where('hotel_id', $hotel->id)->get();


    // نسبة الإشغال
    $occupancyRate = $total > 0 ? round(($booked / $total) * 100) : 0;

    return view('invoices.occupancy', compact(
        'rooms', 'total', 'available', 'booked', 'cleaning', 'maintenance', 'occupancyRate'
    ));
}   


public function download($id)
{
    $invoice = Invoice::with(['booking.room', 'booking.guest'])->findOrFail($id);

    // حساب الإجمالي
    $subtotal = $invoice->amount;
    $tax = $invoice->tax;
    $discount = $invoice->discount;
    $total = $subtotal + $tax - $discount;
 $invoice = Invoice::with(['booking.room', 'booking.guest'])->findOrFail($id);

    // رابط الفاتورة
    $invoiceUrl = url('/invoice/'.$invoice->id);

    // توليد QR كصورة base64
    $qr = base64_encode(\QrCode::format('png')->size(200)->generate($invoiceUrl));

    // حساب الإجمالي
    $subtotal = $invoice->amount;
    $tax = $invoice->tax;
    $discount = $invoice->discount;
    $total = $subtotal + $tax - $discount;

    // توليد PDF
    $pdf = Pdf::loadView('invoices.pdf', compact(
        'invoice', 'subtotal', 'tax', 'discount', 'total', 'qr'
    ));

    // حفظ الفاتورة داخل مجلد invoices
    $path = storage_path('app/invoices/'.$invoice->invoice_number.'.pdf');
    $pdf->save($path);

    // تحميل الفاتورة
    return $pdf->download($invoice->invoice_number.'.pdf');
$hotel = Hotel::where('slug', $hotelSlug)->firstOrFail();

$rooms = Room::where('hotel_id', $hotel->id)->get();


}
public function markAsPaid($invoiceId, $transactionId, $method)
{
    $invoice = Invoice::findOrFail($invoiceId);

    // تحديث حالة الفاتورة
    $invoice->status = 'paid';
    $invoice->save();

    // تسجيل عملية الدفع
    Payment::create([
        'invoice_id' => $invoice->id,
        'transaction_id' => $transactionId,
        'payment_method' => $method,
        'amount' => $invoice->total,
        'status' => 'success',
        'paid_at' => now(),
    ]);
  // توليد PDF وحفظه
    $pdf = Pdf::loadView('invoices.pdf', [
        'invoice' => $invoice,
        'subtotal' => $invoice->amount,
        'tax' => $invoice->tax,
        'discount' => $invoice->discount,
        'total' => $invoice->amount + $invoice->tax - $invoice->discount,
        'qr' => base64_encode(\QrCode::format('png')->size(200)->generate(url('/invoice/'.$invoice->id))),
    ]);

    $pdfPath = storage_path('app/invoices/'.$invoice->invoice_number.'.pdf');
    $pdf->save($pdfPath);

    // إرسال الإيميل مع المرفق
    Mail::to($invoice->booking->guest->email)
        ->send(new InvoicePaidMail($invoice, $payment, $pdfPath));

    return true;
}
    // باقي الدوال غير ضرورية الآن لأن الفاتورة تُنشأ تلقائيًا عند Check-out
    public function create() {}
    public function edit($id) {}
    public function update(Request $request, $id) {}
    public function destroy($id) {}
}
