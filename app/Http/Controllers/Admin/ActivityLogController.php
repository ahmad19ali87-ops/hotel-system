<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index()
    {
        $hotelId = auth()->user()->hotel_id;
        $logs = ActivityLog::with('user')
            ->when($hotelId, function ($query, $hotelId) {
                $query->whereHas('user', fn ($q) => $q->where('hotel_id', $hotelId));
            })
            ->latest('id')
            ->limit(150)
            ->get();

        return view('admin.activity.index', compact('logs'));
    }
}
