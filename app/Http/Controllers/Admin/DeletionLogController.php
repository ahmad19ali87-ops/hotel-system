<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeletionLog;
use Illuminate\Http\Request;

class DeletionLogController extends Controller
{
    public function index(Request $request)
    {
        $hotelId = (int) auth()->user()->hotel_id;

        $query = DeletionLog::where('hotel_id', $hotelId)
            ->with('user')
            ->latest('deleted_at');

        if ($request->filled('type') && in_array($request->type, ['invoice', 'booking'])) {
            $query->where('record_type', $request->type);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('guest_name', 'like', "%{$search}%")
                  ->orWhere('room_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        // إحصائيات سريعة لسجل المحذوفات مفصولة بالعملتين
        $totalDeletedCount = DeletionLog::where('hotel_id', $hotelId)->count();
        $deletedInvoicesCount = DeletionLog::where('hotel_id', $hotelId)->where('record_type', 'invoice')->count();
        $deletedBookingsCount = DeletionLog::where('hotel_id', $hotelId)->where('record_type', 'booking')->count();

        $totalDeletedAmountSYP = (float) DeletionLog::where('hotel_id', $hotelId)
            ->where(fn ($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))
            ->sum('amount');

        $totalDeletedAmountUSD = (float) DeletionLog::where('hotel_id', $hotelId)
            ->where('currency', 'USD')
            ->sum('amount');

        $totalDeletedAmount = $totalDeletedAmountSYP; // for backward compatibility

        return view('admin.deletion-logs.index', compact(
            'logs',
            'totalDeletedCount',
            'deletedInvoicesCount',
            'deletedBookingsCount',
            'totalDeletedAmount',
            'totalDeletedAmountSYP',
            'totalDeletedAmountUSD'
        ));
    }
}

