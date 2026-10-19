<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\Account;
use App\Models\Complaint;
use App\Models\CleaningLog;
use App\Models\DeletionLog;

class DashboardController extends Controller
{
    public function index()
    {
        $hotel_id = (int) auth()->user()->hotel_id;

        // إحصائيات سريعة للغرف
        $totalRooms = Room::where('hotel_id', $hotel_id)->count();
        $reservedRooms = Room::where('hotel_id', $hotel_id)->whereIn('status', ['booked', 'reserved'])->count();
        $availableRooms = Room::where('hotel_id', $hotel_id)->where('status', 'available')->count();

        $todayBookings = Booking::where('hotel_id', $hotel_id)
                                ->whereDate('created_at', today())
                                ->count();

        // إيرادات اليوم منفصلة بالعملتين (SYP & USD)
        $todayRevenueSYP = (float) Invoice::where('hotel_id', $hotel_id)
            ->where('status', 'paid')
            ->where(fn ($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))
            ->whereDate('created_at', today())
            ->sum('total');

        $todayRevenueUSD = (float) Invoice::where('hotel_id', $hotel_id)
            ->where('status', 'paid')
            ->where('currency', 'USD')
            ->whereDate('created_at', today())
            ->sum('total');

        $todayRevenue = $todayRevenueSYP; // for backward compatibility

        // بيانات الرسم البياني للإيرادات الشهرية بالعملتين المنفصلتين
        $monthlyRevenueSYP = [];
        $monthlyRevenueUSD = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyRevenueSYP[] = (float) Invoice::where('hotel_id', $hotel_id)
                ->where('status', 'paid')
                ->where(fn ($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))
                ->whereMonth('created_at', $i)
                ->whereYear('created_at', now()->year)
                ->sum('total');

            $monthlyRevenueUSD[] = (float) Invoice::where('hotel_id', $hotel_id)
                ->where('status', 'paid')
                ->where('currency', 'USD')
                ->whereMonth('created_at', $i)
                ->whereYear('created_at', now()->year)
                ->sum('total');
        }
        $monthlyRevenue = $monthlyRevenueSYP;

        // بيانات الرسم البياني للحجوزات الشهرية
        $monthlyBookings = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyBookings[] = Booking::where('hotel_id', $hotel_id)
                                        ->whereMonth('created_at', $i)
                                        ->whereYear('created_at', now()->year)
                                        ->count();
        }

        // بيانات الإشغال
        $occupancyRate = $totalRooms > 0 ? round(($reservedRooms / $totalRooms) * 100) : 0;

        // الحسابات المالية بالعملتين المنفصلتين (SYP & USD)
        $incomeSYP = (float) Account::where('hotel_id', $hotel_id)
            ->where(fn ($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))
            ->where('type', 'income')
            ->sum('amount');
        $expensesSYP = (float) Account::where('hotel_id', $hotel_id)
            ->where(fn ($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))
            ->where('type', 'expense')
            ->sum('amount');
        $profitSYP = $incomeSYP - $expensesSYP;

        $incomeUSD = (float) Account::where('hotel_id', $hotel_id)
            ->where('currency', 'USD')
            ->where('type', 'income')
            ->sum('amount');
        $expensesUSD = (float) Account::where('hotel_id', $hotel_id)
            ->where('currency', 'USD')
            ->where('type', 'expense')
            ->sum('amount');
        $profitUSD = $incomeUSD - $expensesUSD;

        $income = $incomeSYP;
        $expenses = $expensesSYP;

        $recentBookings = Booking::where('hotel_id', $hotel_id)
            ->with(['room', 'guest', 'creator'])
            ->latest()
            ->limit(10)
            ->get();
        $recentCleaningLogs = CleaningLog::where('hotel_id', $hotel_id)
            ->with(['room', 'cleaner'])
            ->latest('cleaned_at')
            ->limit(10)
            ->get();
        $newComplaints = Complaint::where('hotel_id', $hotel_id)
            ->where('status', 'new')
            ->latest()
            ->limit(10)
            ->get();

        // سجل المحذوفات للداشبورد (فواتير وحجوزات محذوفة مع الأسباب)
        $recentDeletionLogs = DeletionLog::where('hotel_id', $hotel_id)
            ->with('user')
            ->latest('deleted_at')
            ->limit(8)
            ->get();
        $deletionCount = DeletionLog::where('hotel_id', $hotel_id)->count();

        $deletedTotalSYP = (float) DeletionLog::where('hotel_id', $hotel_id)
            ->where(fn ($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))
            ->sum('amount');
        $deletedTotalUSD = (float) DeletionLog::where('hotel_id', $hotel_id)
            ->where('currency', 'USD')
            ->sum('amount');
        $deletedTotalAmount = $deletedTotalSYP;

        return view('admin.dashboard.index', compact(
            'totalRooms', 'reservedRooms', 'availableRooms',
            'todayBookings', 'todayRevenue',
            'todayRevenueSYP', 'todayRevenueUSD',
            'monthlyRevenue', 'monthlyRevenueSYP', 'monthlyRevenueUSD', 'monthlyBookings',
            'occupancyRate',
            'income', 'expenses',
            'incomeSYP', 'expensesSYP', 'profitSYP',
            'incomeUSD', 'expensesUSD', 'profitUSD',
            'recentBookings', 'recentCleaningLogs', 'newComplaints',
            'recentDeletionLogs', 'deletionCount', 'deletedTotalAmount',
            'deletedTotalSYP', 'deletedTotalUSD'
        ));
    }
}
