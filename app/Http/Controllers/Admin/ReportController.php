<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\Maintenance;
use App\Models\CleaningLog;
use App\Models\Account;

class ReportController extends Controller
{
    public function index()
    {
        $hotel = auth()->user()->hotel;
        $hotel_id = auth()->user()->hotel_id;

        // --- 1. إيرادات الفواتير المنفصلة (Invoices Revenue Separated) ---
        // الليرة السورية (SYP)
        $sypInvoiceQuery = Invoice::where('hotel_id', $hotel_id)
            ->where(fn ($q) => $q->where('currency', 'SYP')->orWhereNull('currency'));

        $dailyRevenueSYP = (float)(clone $sypInvoiceQuery)->whereDate('created_at', today())->sum('total');
        $monthlyRevenueSYP = (float)(clone $sypInvoiceQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');
        $yearlyRevenueSYP = (float)(clone $sypInvoiceQuery)->whereYear('created_at', now()->year)->sum('total');
        $totalRevenueSYP = (float)(clone $sypInvoiceQuery)->sum('total');

        // الدولار الأمريكي (USD)
        $usdInvoiceQuery = Invoice::where('hotel_id', $hotel_id)
            ->where('currency', 'USD');

        $dailyRevenueUSD = (float)(clone $usdInvoiceQuery)->whereDate('created_at', today())->sum('total');
        $monthlyRevenueUSD = (float)(clone $usdInvoiceQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');
        $yearlyRevenueUSD = (float)(clone $usdInvoiceQuery)->whereYear('created_at', now()->year)->sum('total');
        $totalRevenueUSD = (float)(clone $usdInvoiceQuery)->sum('total');

        // --- 2. حركة الحسابات المالية والدفاتر المنفصلة (Accounting Ledger Separated) ---
        // الليرة السورية (SYP)
        $sypAccountQuery = Account::where('hotel_id', $hotel_id)
            ->where(fn ($q) => $q->where('currency', 'SYP')->orWhereNull('currency'));

        $incomeSYP = (float)(clone $sypAccountQuery)->where('type', 'income')->sum('amount');
        $expenseSYP = (float)(clone $sypAccountQuery)->where('type', 'expense')->sum('amount');
        $profitSYP = $incomeSYP - $expenseSYP;

        // الدولار الأمريكي (USD)
        $usdAccountQuery = Account::where('hotel_id', $hotel_id)
            ->where('currency', 'USD');

        $incomeUSD = (float)(clone $usdAccountQuery)->where('type', 'income')->sum('amount');
        $expenseUSD = (float)(clone $usdAccountQuery)->where('type', 'expense')->sum('amount');
        $profitUSD = $incomeUSD - $expenseUSD;

        // Backward compatibility
        $dailyRevenue = $dailyRevenueSYP;
        $monthlyRevenue = $monthlyRevenueSYP;
        $yearlyRevenue = $yearlyRevenueSYP;
        $totalRevenue = $totalRevenueSYP;
        $income = $incomeSYP;
        $expense = $expenseSYP;
        $profit = $profitSYP;

        // --- 3. مؤشرات الغرف والإشغال (Rooms & Occupancy) ---
        $roomsTotal = Room::where('hotel_id', $hotel_id)->count();
        $roomsReserved = Room::where('hotel_id', $hotel_id)->whereIn('status', ['reserved', 'booked'])->count();
        $roomsAvailable = Room::where('hotel_id', $hotel_id)->where('status', 'available')->count();
        $roomsMaintenance = Room::where('hotel_id', $hotel_id)->where('status', 'maintenance')->count();
        $roomsCleaning = Room::where('hotel_id', $hotel_id)
            ->where(fn ($query) => $query->where('needs_cleaning', true)->orWhere('status', 'cleaning'))
            ->count();

        $occupancyRate = $roomsTotal > 0 ? round(($roomsReserved / $roomsTotal) * 100, 1) : 0;

        // --- 4. مؤشرات الحجوزات (Bookings Metrics) ---
        $dailyBookings = Booking::where('hotel_id', $hotel_id)
            ->whereDate('created_at', today())
            ->count();

        $monthlyBookings = Booking::where('hotel_id', $hotel_id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $yearlyBookings = Booking::where('hotel_id', $hotel_id)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalBookings = Booking::where('hotel_id', $hotel_id)->count();
        $confirmedBookings = Booking::where('hotel_id', $hotel_id)->where('status', 'confirmed')->count();
        $checkedInBookings = Booking::where('hotel_id', $hotel_id)->where('status', 'checked_in')->count();
        $cancelledBookings = Booking::where('hotel_id', $hotel_id)->where('status', 'cancelled')->count();
        $completedBookings = Booking::where('hotel_id', $hotel_id)->where('status', 'checked_out')->count();

        // --- 5. الصيانة والنظافة (Maintenance & Housekeeping) ---
        $maintenanceOpen = Maintenance::where('hotel_id', $hotel_id)->where('status', 'open')->count();
        $maintenanceInProgress = Maintenance::where('hotel_id', $hotel_id)->where('status', 'in_progress')->count();
        $maintenanceDone = Maintenance::where('hotel_id', $hotel_id)->where('status', 'done')->count();

        $cleaningOpen = $roomsCleaning;
        $cleaningDone = CleaningLog::where('hotel_id', $hotel_id)->count();

        // --- 6. آخر الفواتير الصادرة بالعملتين (Recent Invoices Feed) ---
        $recentInvoices = Invoice::where('hotel_id', $hotel_id)
            ->with(['booking.guest', 'room'])
            ->latest()
            ->take(6)
            ->get();

        return view('admin.reports.index', compact(
            'hotel',
            'dailyRevenueSYP', 'monthlyRevenueSYP', 'yearlyRevenueSYP', 'totalRevenueSYP',
            'dailyRevenueUSD', 'monthlyRevenueUSD', 'yearlyRevenueUSD', 'totalRevenueUSD',
            'incomeSYP', 'expenseSYP', 'profitSYP',
            'incomeUSD', 'expenseUSD', 'profitUSD',
            'dailyRevenue', 'monthlyRevenue', 'yearlyRevenue', 'totalRevenue',
            'income', 'expense', 'profit',
            'roomsTotal', 'roomsReserved', 'roomsAvailable', 'roomsMaintenance', 'roomsCleaning', 'occupancyRate',
            'dailyBookings', 'monthlyBookings', 'yearlyBookings', 'totalBookings',
            'confirmedBookings', 'checkedInBookings', 'cancelledBookings', 'completedBookings',
            'maintenanceOpen', 'maintenanceInProgress', 'maintenanceDone',
            'cleaningOpen', 'cleaningDone',
            'recentInvoices'
        ));
    }
}
