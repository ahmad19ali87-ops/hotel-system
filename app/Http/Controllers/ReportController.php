<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\RevenueExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\Guest;
use App\Models\Booking;
use App\Models\Maintenance;


class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function revenue(Request $request = null)
    {
        $query = Invoice::query()->where('status', 'paid');

        if ($request) {
            if ($request->filled('from_date') && $request->filled('to_date')) {
                $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
            }

            if ($request->filled('room_id')) {
                $query->whereHas('booking', function ($q) use ($request) {
                    $q->where('room_id', $request->room_id);
                });
            }

            if ($request->filled('guest_id')) {
                $query->whereHas('booking', function ($q) use ($request) {
                    $q->where('guest_id', $request->guest_id);
                });
            }
        }

        $invoices = $query->get();
        $totalRevenue = $invoices->sum('total');
        $daily = (clone $query)->whereDate('created_at', today())->sum('total');
        $monthly = (clone $query)->whereMonth('created_at', now()->month)->sum('total');
        $yearly = (clone $query)->whereYear('created_at', now()->year)->sum('total');


        return view('reports.revenue', [
            'invoices' => $invoices,
            'totalRevenue' => $totalRevenue,
            'daily' => $daily,
            'monthly' => $monthly,
            'yearly' => $yearly,
            'rooms' => Room::all(),
            'guests' => Guest::all(),
        ]);
    }

    public function occupancy()
    {
        $rooms = Room::count();
        $booked = Room::where('status', 'booked')->count();
        $hotel = Hotel::where('slug', $hotelSlug)->firstOrFail();

$rooms = Room::where('hotel_id', $hotel->id)->get();    

        $occupancyRate = $rooms > 0 ? round(($booked / $rooms) * 100) : 0;

        return view('reports.occupancy', compact('rooms', 'booked', 'occupancyRate'));
    }

    public function maintenance()
    {
        $open = Maintenance::where('status', 'open')->count();
        $closed = Maintenance::where('status', 'closed')->count();

        return view('reports.maintenance', compact('open', 'closed'));
    }

    public function guests()
    {
        $totalGuests = Guest::count();
        $repeatGuests = Guest::where('visits', '>', 1)->count();

        return view('reports.guests', compact('totalGuests', 'repeatGuests'));
    }


public function revenuePdf(Request $request)
{
    // نفس الفلاتر الموجودة في تقرير الإيرادات
    $query = Invoice::where('status', 'paid');

    if ($request->from_date && $request->to_date) {
        $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
    }

    if ($request->room_id) {
        $query->whereHas('booking', function ($q) use ($request) {
            $q->where('room_id', $request->room_id);
        });
    }

    if ($request->guest_id) {
        $query->whereHas('booking', function ($q) use ($request) {
            $q->where('guest_id', $request->guest_id);
        });
    }

    $invoices = $query->get();
    $totalRevenue = $invoices->sum('total');

    // توليد PDF
    $pdf = Pdf::loadView('reports.pdf.revenue', [
        'invoices' => $invoices,
        'totalRevenue' => $totalRevenue,
        'from_date' => $request->from_date,
        'to_date' => $request->to_date,
    ]);

    return $pdf->download('Revenue_Report.pdf');
}
public function unified(Request $request)
{
    // فلتر التاريخ
    $from = $request->from_date;
    $to = $request->to_date;

    // الإيرادات
    $revenueQuery = Invoice::where('status', 'paid');
    if ($from && $to) {
        $revenueQuery->whereBetween('created_at', [$from, $to]);
    }
    $revenue = $revenueQuery->sum('total');

    // الإشغال
    $rooms = Room::count();
    $booked = Room::where('status', 'booked')->count();
    $occupancyRate = $rooms > 0 ? round(($booked / $rooms) * 100) : 0;

    // الصيانة
    $maintenanceOpen = Maintenance::where('status', 'open')->count();
    $maintenanceClosed = Maintenance::where('status', 'closed')->count();

    // العملاء
    $totalGuests = Guest::count();
    $repeatGuests = Guest::where('visits', '>', 1)->count();

    // الحجوزات
    $totalBookings = Booking::count();
    $activeBookings = Booking::where('status', 'reserved')->count();

    return view('reports.unified', compact(
        'revenue',
        'rooms',
        'booked',
        'occupancyRate',
        'maintenanceOpen',
        'maintenanceClosed',
        'totalGuests',
        'repeatGuests',
        'totalBookings',
        'activeBookings',
        'from',
        'to'
    ));
}


public function unifiedPdf(Request $request)
{
    $from = $request->from_date;
    $to = $request->to_date;

    // الإيرادات
    $revenueQuery = Invoice::where('status', 'paid');
    if ($from && $to) {
        $revenueQuery->whereBetween('created_at', [$from, $to]);
    }
    $revenue = $revenueQuery->sum('total');

    // الإشغال
    $rooms = Room::count();
    $booked = Room::where('status', 'booked')->count();
    $occupancyRate = $rooms > 0 ? round(($booked / $rooms) * 100) : 0;

    // الصيانة
    $maintenanceOpen = Maintenance::where('status', 'open')->count();
    $maintenanceClosed = Maintenance::where('status', 'closed')->count();

    // العملاء
    $totalGuests = Guest::count();
    $repeatGuests = Guest::where('visits', '>', 1)->count();

    // الحجوزات
    $totalBookings = Booking::count();
    $activeBookings = Booking::where('status', 'reserved')->count();

    // توليد PDF
    $pdf = Pdf::loadView('reports.pdf.unified', [
        'from' => $from,
        'to' => $to,
        'revenue' => $revenue,
        'rooms' => $rooms,
        'booked' => $booked,
        'occupancyRate' => $occupancyRate,
        'maintenanceOpen' => $maintenanceOpen,
        'maintenanceClosed' => $maintenanceClosed,
        'totalGuests' => $totalGuests,
        'repeatGuests' => $repeatGuests,
        'totalBookings' => $totalBookings,
        'activeBookings' => $activeBookings,
    ]);

    return $pdf->download('Unified_Report.pdf');
}


public function revenueExcel(Request $request)
{
    return Excel::download(
        new RevenueExport($request->from_date, $request->to_date),
        'Revenue_Report.xlsx'
    );
}
public function dailySnapshot()
{
    $today = today();

    // الإيرادات اليومية
    $revenueToday = Invoice::where('status', 'paid')
        ->whereDate('created_at', $today)
        ->sum('total');

    // حجوزات اليوم
    $bookingsToday = Booking::whereDate('created_at', $today)->count();

    // تسجيل دخول اليوم
    $checkinsToday = Booking::whereDate('check_in', $today)->count();

    // تسجيل خروج اليوم
    $checkoutsToday = Booking::whereDate('check_out', $today)->count();

    // الإشغال
    $rooms = Room::count();
    $booked = Room::where('status', 'booked')->count();
    $occupancyRate = $rooms > 0 ? round(($booked / $rooms) * 100) : 0;

    // الصيانة
   // الصيانة
$maintenanceOpen = Maintenance::where('status', 'open')->count();
$maintenanceClosed = Maintenance::where('status', 'closed')->count();


    // التنظيف
    $cleaningRooms = Room::where('needs_cleaning', true)->count();

    return view('reports.daily', compact(
        'today',
        'revenueToday',
        'bookingsToday',
        'checkinsToday',
        'checkoutsToday',
        'occupancyRate',
        'maintenanceOpen',
        'cleaningRooms'
    ));
}


}

