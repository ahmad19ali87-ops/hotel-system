<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\CleaningLog;
use App\Models\Complaint;
use App\Models\DeletionLog;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Invoice;
use App\Models\Maintenance;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    /**
     * Ensure user is hotel staff / owner / manager.
     */
    private function checkStaffAccess(Request $request): User
    {
        $user = $request->user();

        if (in_array($user->role, ['guest'])) {
            abort(403, 'غير مسموح لحسابات الزوار بالوصول إلى لوحة الموظفين.');
        }

        if (! $user->hotel_id && ! in_array($user->role, ['super_admin', 'platform_admin'])) {
            abort(403, 'لا يوجد فندق مرتبط بهذا الحساب.');
        }

        return $user;
    }

    /**
     * Dashboard statistics for Desktop App.
     */
    public function dashboard(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $hotelId = $user->hotel_id;

        $totalRooms = Room::where('hotel_id', $hotelId)->count();
        $availableRooms = Room::where('hotel_id', $hotelId)->where('status', 'available')->count();
        $occupiedRooms = Room::where('hotel_id', $hotelId)->whereIn('status', ['occupied', 'booked', 'reserved'])->count();
        $cleaningRooms = Room::where('hotel_id', $hotelId)->where(fn($q) => $q->where('needs_cleaning', true)->orWhere('status', 'cleaning'))->count();
        $maintenanceRooms = Room::where('hotel_id', $hotelId)->where('status', 'maintenance')->count();

        $today = Carbon::today()->toDateString();
        $todayCheckins = Booking::where('hotel_id', $hotelId)->where('check_in', $today)->count();
        $todayCheckouts = Booking::where('hotel_id', $hotelId)->where('check_out', $today)->count();
        $todayBookings = Booking::where('hotel_id', $hotelId)->whereDate('created_at', $today)->count();
        $activeBookings = Booking::where('hotel_id', $hotelId)->whereIn('status', ['confirmed', 'checked_in'])->count();

        // Today revenues
        $todayRevenueSyp = (float) Invoice::where('hotel_id', $hotelId)
            ->where('status', 'paid')
            ->where(fn ($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))
            ->whereDate('created_at', $today)
            ->sum('total');

        $todayRevenueUsd = (float) Invoice::where('hotel_id', $hotelId)
            ->where('status', 'paid')
            ->where('currency', 'USD')
            ->whereDate('created_at', $today)
            ->sum('total');

        // Wallets / Accounts separated by currency
        $incomeSyp = (float) Account::where('hotel_id', $hotelId)
            ->where('type', 'income')
            ->where(fn ($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))
            ->sum('amount');

        $incomeUsd = (float) Account::where('hotel_id', $hotelId)
            ->where('type', 'income')
            ->where('currency', 'USD')
            ->sum('amount');

        $expenseSyp = (float) Account::where('hotel_id', $hotelId)
            ->where('type', 'expense')
            ->where(fn ($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))
            ->sum('amount');

        $expenseUsd = (float) Account::where('hotel_id', $hotelId)
            ->where('type', 'expense')
            ->where('currency', 'USD')
            ->sum('amount');

        // 12-Month Charts Data (like in website dashboard)
        $monthlyRevenueSyp = [];
        $monthlyRevenueUsd = [];
        $monthlyBookings = [];
        $currentYear = now()->year;

        for ($i = 1; $i <= 12; $i++) {
            $monthlyRevenueSyp[] = (float) Invoice::where('hotel_id', $hotelId)
                ->where('status', 'paid')
                ->where(fn ($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))
                ->whereMonth('created_at', $i)
                ->whereYear('created_at', $currentYear)
                ->sum('total');

            $monthlyRevenueUsd[] = (float) Invoice::where('hotel_id', $hotelId)
                ->where('status', 'paid')
                ->where('currency', 'USD')
                ->whereMonth('created_at', $i)
                ->whereYear('created_at', $currentYear)
                ->sum('total');

            $monthlyBookings[] = Booking::where('hotel_id', $hotelId)
                ->whereMonth('created_at', $i)
                ->whereYear('created_at', $currentYear)
                ->count();
        }

        // Deletion logs summary
        $deletionCount = DeletionLog::where('hotel_id', $hotelId)->count();
        $deletedTotalSyp = (float) DeletionLog::where('hotel_id', $hotelId)
            ->where(fn ($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))
            ->sum('amount');
        $deletedTotalUsd = (float) DeletionLog::where('hotel_id', $hotelId)
            ->where('currency', 'USD')
            ->sum('amount');

        $recentDeletionLogs = DeletionLog::where('hotel_id', $hotelId)
            ->with('user')
            ->latest('deleted_at')
            ->take(6)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'record_type' => $log->record_type,
                    'reference_number' => $log->reference_number ?: ('#' . $log->record_id),
                    'guest_name' => $log->guest_name ?: '—',
                    'room_number' => $log->room_number ?: '',
                    'amount' => (float) $log->amount,
                    'currency' => $log->currency ?: 'SYP',
                    'reason' => $log->reason,
                    'user_name' => $log->user?->name ?? 'مدير الفندق',
                    'deleted_at' => $log->deleted_at ? $log->deleted_at->format('Y-m-d H:i') : '',
                    'snapshot' => $log->snapshot,
                ];
            });

        // Recent complaints
        $recentComplaints = Complaint::where('hotel_id', $hotelId)
            ->where('status', 'new')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'message' => $c->message,
                    'status' => $c->status,
                    'date' => $c->created_at->format('Y-m-d'),
                ];
            });

        // Recent invoices
        $recentInvoices = Invoice::where('hotel_id', $hotelId)
            ->with(['booking.guest'])
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'guest_name' => $inv->booking?->guest?->name ?? 'نزيل',
                    'amount' => (float) $inv->amount,
                    'total' => (float) $inv->total,
                    'currency' => $inv->currency,
                    'status' => $inv->status,
                    'date' => $inv->created_at->format('Y-m-d'),
                ];
            });

        // Unread notifications count
        $unreadNotifs = AppNotification::where('hotel_id', $hotelId)
            ->where('is_read', false)
            ->count();

        // Recent bookings
        $recentBookings = Booking::where('hotel_id', $hotelId)
            ->with(['guest', 'room.roomType'])
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'guest_name' => $b->guest?->name ?? 'نزيل',
                    'room_number' => $b->room?->room_number ?? '—',
                    'room_type' => $b->room?->roomType?->name ?? 'غرفة',
                    'check_in' => $b->check_in,
                    'check_out' => $b->check_out,
                    'status' => $b->status,
                    'currency' => $b->currency ?? 'USD',
                    'total_price' => (float) $b->total_price,
                ];
            });

        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'hotel_name' => $user->hotel?->name ?? 'الفندق',
                'user_role' => $user->role,
                'is_owner' => $user->isHotelOwner(),
                'permissions' => $user->permissions_list,
                'printer_settings' => $user->hotel?->printer_settings_list ?? [],
                'unread_notifications_count' => $unreadNotifs,
                'rooms' => [
                    'total' => $totalRooms,
                    'available' => $availableRooms,
                    'occupied' => $occupiedRooms,
                    'cleaning' => $cleaningRooms,
                    'maintenance' => $maintenanceRooms,
                    'occupancy_rate' => $occupancyRate,
                ],
                'today_stats' => [
                    'today_bookings' => $todayBookings,
                    'today_checkins' => $todayCheckins,
                    'today_checkouts' => $todayCheckouts,
                    'today_revenue_syp' => $todayRevenueSyp,
                    'today_revenue_usd' => $todayRevenueUsd,
                ],
                'bookings' => [
                    'active' => $activeBookings,
                    'today_checkins' => $todayCheckins,
                    'today_checkouts' => $todayCheckouts,
                ],
                'wallets' => [
                    'syp' => [
                        'income' => $incomeSyp,
                        'expense' => $expenseSyp,
                        'profit' => $incomeSyp - $expenseSyp,
                    ],
                    'usd' => [
                        'income' => $incomeUsd,
                        'expense' => $expenseUsd,
                        'profit' => $incomeUsd - $expenseUsd,
                    ],
                ],
                'financials' => [
                    'income_syp' => $incomeSyp,
                    'income_usd' => $incomeUsd,
                    'expense_syp' => $expenseSyp,
                    'expense_usd' => $expenseUsd,
                    'balance_syp' => $incomeSyp - $expenseSyp,
                    'balance_usd' => $incomeUsd - $expenseUsd,
                ],
                'charts' => [
                    'months_labels' => ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
                    'monthly_revenue_syp' => $monthlyRevenueSyp,
                    'monthly_revenue_usd' => $monthlyRevenueUsd,
                    'monthly_bookings' => $monthlyBookings,
                    'occupancy_rate' => $occupancyRate,
                ],
                'deletion_logs_summary' => [
                    'count' => $deletionCount,
                    'total_syp' => $deletedTotalSyp,
                    'total_usd' => $deletedTotalUsd,
                    'recent' => $recentDeletionLogs,
                ],
                'recent_complaints' => $recentComplaints,
                'recent_invoices' => $recentInvoices,
                'recent_bookings' => $recentBookings,
            ],
        ]);
    }

    /**
     * List rooms.
     */
    public function rooms(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $hotelId = $user->hotel_id;

        $rooms = Room::where('hotel_id', $hotelId)
            ->with([
                'roomType',
                'bookings' => function ($q) {
                    $q->whereIn('status', ['confirmed', 'checked_in', 'occupied', 'booked'])
                        ->with(['guest', 'invoices'])
                        ->latest();
                },
            ])
            ->orderBy('room_number')
            ->get();

        $data = $rooms->map(function ($r) {
            $activeBooking = $r->bookings->first();
            $latestInvoice = $activeBooking?->invoices?->sortByDesc('id')->first();

            // Status normalization: cleaning priority if needs_cleaning is true
            $status = $r->status;
            if ($r->needs_cleaning && $status !== 'maintenance' && $status !== 'occupied') {
                $status = 'cleaning';
            }

            return [
                'id' => $r->id,
                'name' => $r->name ?: ('غرفة ' . $r->room_number),
                'room_number' => $r->room_number,
                'room_type_id' => $r->room_type_id,
                'room_type' => $r->roomType?->name ?? 'قياسي',
                'price' => (float) $r->price,
                'price_syp' => (float) ($r->price_syp ?: $r->price),
                'price_usd' => (float) ($r->price_usd ?: 0),
                'status' => $status,
                'needs_cleaning' => (bool) $r->needs_cleaning,
                'maintenance_notes' => $r->maintenance_notes,
                'active_booking' => $activeBooking ? [
                    'id' => $activeBooking->id,
                    'guest_name' => $activeBooking->guest?->name ?? $activeBooking->guest_name ?? 'نزيل',
                    'guest_phone' => $activeBooking->guest?->phone ?? $activeBooking->guest_phone ?? '',
                    'check_in' => $activeBooking->check_in,
                    'check_out' => $activeBooking->check_out,
                    'status' => $activeBooking->status,
                    'total_price' => (float) $activeBooking->total_price,
                    'currency' => $activeBooking->currency ?? 'USD',
                    'invoice' => $latestInvoice ? [
                        'id' => $latestInvoice->id,
                        'invoice_number' => $latestInvoice->invoice_number,
                        'amount' => (float) $latestInvoice->amount,
                        'service_amount' => (float) ($latestInvoice->service_amount ?? 0),
                        'service_description' => $latestInvoice->service_description,
                        'total' => (float) $latestInvoice->total,
                        'currency' => $latestInvoice->currency ?? 'USD',
                        'status' => $latestInvoice->status,
                        'paid_at' => $latestInvoice->paid_at ? (string)$latestInvoice->paid_at : null,
                    ] : null,
                ] : null,
            ];
        });

        $roomTypes = RoomType::where('hotel_id', $hotelId)->get();

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'room_types' => $roomTypes,
        ]);
    }

    /**
     * List room types.
     */
    public function roomTypes(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $types = RoomType::where('hotel_id', $user->hotel_id)->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $types,
        ]);
    }

    /**
     * Store new room type.
     */
    public function storeRoomType(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'عذراً، صلاحيات مالك الفندق مخصصة للاطلاع فقط.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $type = RoomType::create([
            'hotel_id' => $user->hotel_id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تمت إضافة نوع الغرفة بنجاح.',
            'data' => $type,
        ]);
    }

    /**
     * Update room type.
     */
    public function updateRoomType(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'عذراً، صلاحيات مالك الفندق مخصصة للاطلاع فقط.'], 403);
        }

        $type = RoomType::where('hotel_id', $user->hotel_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $type->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث نوع الغرفة بنجاح.',
            'data' => $type,
        ]);
    }

    /**
     * Delete room type.
     */
    public function deleteRoomType(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'عذراً، صلاحيات مالك الفندق مخصصة للاطلاع فقط.'], 403);
        }

        $type = RoomType::where('hotel_id', $user->hotel_id)->findOrFail($id);

        $roomsCount = Room::where('room_type_id', $id)->count();
        if ($roomsCount > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "لا يمكن حذف هذا النوع لوجود {$roomsCount} غرفة مرتبطة به. يرجى تعديل الغرف أولاً.",
            ], 422);
        }

        $type->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف نوع الغرفة بنجاح.',
        ]);
    }

    /**
     * Store new room.
     */
    public function storeRoom(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'عذراً، صلاحيات مالك الفندق مخصصة للاطلاع فقط.'], 403);
        }

        $validated = $request->validate([
            'room_number' => ['required', 'string', 'max:50'],
            'room_type_id' => ['nullable', 'exists:room_types,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_syp' => ['nullable', 'numeric', 'min:0'],
            'price_usd' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:available,occupied,cleaning,maintenance'],
        ]);

        $priceSyp = (float) ($request->input('price_syp', $request->input('price', 0)));
        $priceUsd = (float) ($request->input('price_usd', 0));

        $room = Room::create([
            'hotel_id' => $user->hotel_id,
            'room_number' => $validated['room_number'],
            'room_type_id' => $validated['room_type_id'] ?? null,
            'price' => $priceSyp,
            'price_syp' => $priceSyp,
            'price_usd' => $priceUsd,
            'status' => $validated['status'] ?? 'available',
            'needs_cleaning' => false,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تمت إضافة الغرفة بنجاح.',
            'room' => $room->load('roomType'),
        ]);
    }

    /**
     * Update room.
     */
    public function updateRoom(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'عذراً، صلاحيات مالك الفندق مخصصة للاطلاع فقط.'], 403);
        }

        $room = Room::where('hotel_id', $user->hotel_id)->findOrFail($id);

        $request->validate([
            'room_number' => 'required|string|max:50',
            'room_type_id' => 'nullable|exists:room_types,id',
            'price' => 'nullable|numeric|min:0',
            'price_syp' => 'nullable|numeric|min:0',
            'price_usd' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:available,occupied,cleaning,maintenance',
            'needs_cleaning' => 'nullable|boolean',
        ]);

        $priceSyp = (float) ($request->input('price_syp', $request->input('price', $room->price)));
        $priceUsd = (float) ($request->input('price_usd', $room->price_usd));

        $room->update([
            'room_number' => $request->room_number,
            'room_type_id' => $request->room_type_id ?? $room->room_type_id,
            'price' => $priceSyp,
            'price_syp' => $priceSyp,
            'price_usd' => $priceUsd,
            'status' => $request->status ?? $room->status,
            'needs_cleaning' => $request->has('needs_cleaning') ? $request->boolean('needs_cleaning') : $room->needs_cleaning,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث بيانات الغرفة بنجاح.',
            'room' => $room->fresh()->load('roomType'),
        ]);
    }

    /**
     * Delete room.
     */
    public function deleteRoom(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'عذراً، صلاحيات مالك الفندق مخصصة للاطلاع فقط.'], 403);
        }

        $room = Room::where('hotel_id', $user->hotel_id)->findOrFail($id);
        if ($room->status === 'occupied') {
            return response()->json(['status' => 'error', 'message' => 'لا يمكن حذف غرفة مشغولة بنزيل حالياً.'], 422);
        }

        $room->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف الغرفة بنجاح.',
        ]);
    }

    /**
     * List bookings.
     */
    public function bookings(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $hotelId = $user->hotel_id;

        $query = Booking::where('hotel_id', $hotelId)
            ->with(['hotel', 'guest', 'room.roomType', 'invoices.hotel'])
            ->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate(50),
        ]);
    }

    /**
     * Store new booking directly from Desktop App.
     */
    public function storeBooking(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'عذراً، صلاحيات مالك الفندق مخصصة للاطلاع فقط.'], 403);
        }

        $hotelId = $user->hotel_id;

        $request->validate([
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')->where('hotel_id', $hotelId)],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:40'],
            'guest_id' => ['nullable', 'exists:guests,id'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'currency' => ['required', 'in:SYP,USD'],
            'status' => ['nullable', 'in:confirmed,checked_in'],
            'total_price' => ['nullable', 'numeric'],
        ]);

        $room = Room::where('hotel_id', $hotelId)->findOrFail($request->room_id);

        if ($room->status === 'occupied') {
            return response()->json(['status' => 'error', 'message' => 'الغرفة المختارة مشغولة حالياً بنزيل آخر.'], 422);
        }

        $guest = null;
        if ($request->guest_id) {
            $guest = Guest::where('hotel_id', $hotelId)->find($request->guest_id);
        }
        if (! $guest) {
            $guest = Guest::create([
                'hotel_id' => $hotelId,
                'name' => $request->guest_name,
                'phone' => $request->guest_phone,
                'father_name' => $request->input('guest_father_name'),
                'nationality' => $request->input('guest_nationality', 'سوري'),
                'national_id' => $request->input('guest_national_id'),
                'id_type' => $request->input('guest_id_type', 'national_id'),
                'id_number' => $request->input('guest_id_number') ?? $request->input('guest_national_id'),
                'email' => $request->input('guest_email'),
                'address' => $request->input('guest_address'),
                'notes' => $request->input('guest_notes'),
            ]);
        } else {
            $updates = [];
            if ($request->filled('guest_phone') && empty($guest->phone)) $updates['phone'] = $request->guest_phone;
            if ($request->filled('guest_father_name') && empty($guest->father_name)) $updates['father_name'] = $request->input('guest_father_name');
            if ($request->filled('guest_national_id') && empty($guest->national_id)) $updates['national_id'] = $request->input('guest_national_id');
            if ($request->filled('guest_id_number') && empty($guest->id_number)) $updates['id_number'] = $request->input('guest_id_number');
            if (!empty($updates)) {
                $guest->update($updates);
            }
        }

        $currency = $request->currency;
        $nights = max(1, (int) Carbon::parse($request->check_in)->diffInDays(Carbon::parse($request->check_out)));

        $priceSYP = (float) $room->price * $nights;
        $priceUSD = (float) ($room->price_usd ?: 0) * $nights;
        $totalPrice = $request->filled('total_price') ? (float) $request->total_price : (($currency === 'USD') ? $priceUSD : $priceSYP);

        $bookingStatus = $request->input('status', 'confirmed');

        $booking = Booking::create([
            'hotel_id' => $hotelId,
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'created_by' => $user->id,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'currency' => $currency,
            'total_price' => $totalPrice,
            'total_price_usd' => $priceUSD,
            'status' => $bookingStatus,
        ]);

        if ($bookingStatus === 'checked_in') {
            $room->update(['status' => 'occupied']);
        } else {
            $room->update(['status' => 'booked']);
        }

        NotificationService::sendToHotelStaff(
            $hotelId,
            'حجز فندقي جديد 🏨',
            "تم إنشاء حجز جديد برقم #{$booking->id} للنزيل {$guest->name} في الغرفة {$room->room_number}.",
            'booking',
            '🏨',
            '/bookings'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم إنشاء الحجز وتسكين النزيل بنجاح.',
            'booking' => $booking->load(['guest', 'room.roomType']),
        ]);
    }

    /**
     * 1-Click Check-in.
     */
    public function checkInBooking(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'صلاحية مالك الفندق للمعاينة فقط.'], 403);
        }

        $booking = Booking::where('hotel_id', $user->hotel_id)->findOrFail($id);
        $booking->update(['status' => 'checked_in']);
        $booking->room?->update(['status' => 'occupied']);

        NotificationService::sendToHotelStaff(
            $user->hotel_id,
            'تسجيل دخول نزيل 🔑',
            "تم تأكيد تسجيل الدخول (Check-in) للنزيل {$booking->guest?->name} في الغرفة {$booking->room?->room_number}.",
            'check_in',
            '🔑',
            '/bookings'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل دخول وتسكين النزيل بنجاح في الغرفة رقم ' . ($booking->room?->room_number ?? ''),
            'booking' => $booking->fresh()->load(['guest', 'room.roomType']),
        ]);
    }

    /**
     * 1-Click Check-out (Releases room, moves to cleaning, issues paid invoice & cash entry).
     */
    public function checkOutBooking(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'صلاحية مالك الفندق للمعاينة فقط.'], 403);
        }

        $booking = Booking::where('hotel_id', $user->hotel_id)->with(['room', 'guest'])->findOrFail($id);
        $booking->update(['status' => 'checked_out']);

        // Set room status to cleaning & needs_cleaning
        $booking->room?->update([
            'status' => 'cleaning',
            'needs_cleaning' => true,
        ]);

        // Auto issue & pay invoice if not yet issued
        $invoice = Invoice::where('hotel_id', $user->hotel_id)->where('booking_id', $booking->id)->first();
        if (! $invoice) {
            $invoice = Invoice::create([
                'hotel_id' => $user->hotel_id,
                'booking_id' => $booking->id,
                'room_id' => $booking->room_id,
                'invoice_number' => 'INV-' . strtoupper(uniqid()),
                'amount' => $booking->total_price,
                'currency' => $booking->currency ?? 'USD',
                'service_amount' => $request->input('service_amount', 0),
                'service_description' => $request->input('service_description', null),
                'tax' => 0,
                'total' => (float) $booking->total_price + (float) $request->input('service_amount', 0),
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Create income accounting entry
            Account::create([
                'hotel_id' => $user->hotel_id,
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total,
                'type' => 'income',
                'title' => "تحصيل فاتورة مغادرة الحجز رقم #{$booking->id} للنزيل {$booking->guest?->name}",
                'currency' => $invoice->currency,
                'notes' => 'إيراد إقامة فندقية (تسجيل خروج)',
                'transaction_date' => now()->toDateString(),
            ]);
        } else {
            $wasPaid = $invoice->status === 'paid';
            $serviceAmount = (float) $request->input('service_amount', 0);
            if ($serviceAmount > 0) {
                $invoice->service_amount = ((float) $invoice->service_amount) + $serviceAmount;
                $invoice->total = ((float) $invoice->total) + $serviceAmount;
                if ($request->filled('service_description')) {
                    $invoice->service_description = trim(($invoice->service_description ? $invoice->service_description . ' | ' : '') . $request->input('service_description'));
                }
            }
            $invoice->status = 'paid';
            $invoice->paid_at = now();
            $invoice->save();

            if (! $wasPaid) {
                Account::create([
                    'hotel_id' => $user->hotel_id,
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->total,
                    'type' => 'income',
                    'title' => "تحصيل فاتورة مغادرة الحجز رقم #{$booking->id} للنزيل {$booking->guest?->name}",
                    'currency' => $invoice->currency,
                    'notes' => 'إيراد إقامة فندقية (تسجيل خروج)',
                    'transaction_date' => now()->toDateString(),
                ]);
            }
        }

        NotificationService::sendToHotelStaff(
            $user->hotel_id,
            'تسجيل مغادرة نزيل 🚪',
            "أتم النزيل {$booking->guest?->name} تسجيل المغادرة من الغرفة {$booking->room?->room_number}. تم تحويل الغرفة للتنظيف وتسديد الفاتورة.",
            'check_out',
            '🚪',
            '/cleaning'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل مغادرة النزيل، تحويل الغرفة لقسم التنظيف، وتسجيل الفاتورة والإيراد بنجاح.',
            'booking' => $booking->fresh()->load(['guest', 'room.roomType']),
            'invoice' => $invoice->fresh()->load(['booking.guest', 'room']),
        ]);
    }

    /**
     * Cancel booking.
     */
    public function cancelBooking(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'صلاحية مالك الفندق للمعاينة فقط.'], 403);
        }

        $booking = Booking::where('hotel_id', $user->hotel_id)->findOrFail($id);
        $booking->update(['status' => 'cancelled']);
        if ($booking->room?->status === 'occupied' || $booking->room?->status === 'booked') {
            $booking->room->update(['status' => 'available']);
        }

        NotificationService::sendToHotelStaff(
            $user->hotel_id,
            'إلغاء حجز ❌',
            "تم إلغاء الحجز رقم #{$booking->id} للغرفة {$booking->room?->room_number}.",
            'booking_cancelled',
            '❌',
            '/bookings'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم إلغاء الحجز وإعادة الغرفة لحالة المتاحة.',
            'booking' => $booking,
        ]);
    }

    /**
     * Update booking status.
     */
    public function updateBookingStatus(Request $request, $id)
    {
        $status = $request->input('status');
        if ($status === 'checked_in') {
            return $this->checkInBooking($request, $id);
        } elseif ($status === 'checked_out') {
            return $this->checkOutBooking($request, $id);
        } elseif ($status === 'cancelled') {
            return $this->cancelBooking($request, $id);
        }

        $user = $this->checkStaffAccess($request);
        $booking = Booking::where('hotel_id', $user->hotel_id)->findOrFail($id);
        $booking->update(['status' => $status]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث حالة الحجز بنجاح.',
            'booking' => $booking,
        ]);
    }

    /**
     * Update booking dates, room, guest info, and status.
     */
    public function updateBooking(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'عذراً، صلاحيات مالك الفندق مخصصة للاطلاع فقط.'], 403);
        }

        $hotelId = $user->hotel_id;
        $booking = Booking::where('hotel_id', $hotelId)->with(['guest', 'room'])->findOrFail($id);

        $request->validate([
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'room_id' => ['nullable', 'integer', Rule::exists('rooms', 'id')->where('hotel_id', $hotelId)],
            'guest_name' => ['nullable', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:40'],
            'total_price' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'in:SYP,USD'],
            'status' => ['nullable', 'string', 'in:pending,confirmed,checked_in,checked_out,cancelled'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $room = $request->filled('room_id')
            ? Room::where('hotel_id', $hotelId)->findOrFail($request->room_id)
            : $booking->room;

        if ($booking->guest && $request->filled('guest_name')) {
            $booking->guest->update([
                'name' => $request->guest_name,
                'phone' => $request->guest_phone ?? $booking->guest->phone,
            ]);
        }

        $currency = $request->input('currency', $booking->currency ?: 'USD');
        $days = max(1, (int) Carbon::parse($request->check_in)->diffInDays(Carbon::parse($request->check_out)));
        $priceSYP = (float)($room?->price ?? 0) * $days;
        $priceUSD = (float)($room?->price_usd ?? 0) * $days;

        $totalPrice = $request->filled('total_price')
            ? (float)$request->total_price
            : (($currency === 'USD') ? $priceUSD : $priceSYP);

        $status = $request->input('status', $booking->status);

        $booking->update([
            'room_id' => $room?->id ?? $booking->room_id,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'currency' => $currency,
            'total_price' => $totalPrice,
            'total_price_usd' => $priceUSD,
            'status' => $status,
            'notes' => $request->input('notes', $booking->notes),
        ]);

        if ($status === 'checked_in') {
            $room?->update(['status' => 'occupied']);
        } elseif ($status === 'confirmed') {
            $room?->update(['status' => 'booked']);
        } elseif ($status === 'cancelled' || $status === 'checked_out') {
            if ($room && ($room->status === 'occupied' || $room->status === 'booked')) {
                $room->update([
                    'status' => ($status === 'checked_out') ? 'cleaning' : 'available',
                    'needs_cleaning' => ($status === 'checked_out'),
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث بيانات وتواريخ الحجز بنجاح.',
            'booking' => $booking->fresh(['hotel', 'guest', 'room.roomType', 'invoices']),
        ]);
    }

    /**
     * Guests list and search.
     */
    public function guests(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $q = $request->input('q');

        $query = Guest::where('hotel_id', $user->hotel_id)->withCount('bookings')->latest();
        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('national_id', 'like', "%{$q}%")
                    ->orWhere('id_number', 'like', "%{$q}%");
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate(50),
        ]);
    }

    /**
     * Store new guest.
     */
    public function storeGuest(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'صلاحية مالك الفندق للمعاينة فقط.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'first_name' => 'nullable|string|max:100',
            'father_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:100',
            'id_type' => 'nullable|string|in:national_id,passport,residence,driver_license',
            'id_number' => 'nullable|string|max:100',
            'national_id' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:255',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $guest = Guest::create([
            ...$validated,
            'hotel_id' => $user->hotel_id,
            'nationality' => $validated['nationality'] ?? 'سوري',
            'id_type' => $validated['id_type'] ?? 'national_id',
            'id_number' => $validated['id_number'] ?? $validated['national_id'] ?? null,
            'national_id' => $validated['national_id'] ?? $validated['id_number'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل بيانات النزيل في السجل بنجاح.',
            'guest' => $guest->loadCount('bookings'),
        ]);
    }

    /**
     * Update existing guest details.
     */
    public function updateGuest(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'صلاحية مالك الفندق للمعاينة فقط.'], 403);
        }

        $guest = Guest::where('hotel_id', $user->hotel_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'first_name' => 'nullable|string|max:100',
            'father_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:100',
            'id_type' => 'nullable|string|in:national_id,passport,residence,driver_license',
            'id_number' => 'nullable|string|max:100',
            'national_id' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:255',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $guest->update([
            ...$validated,
            'id_number' => $validated['id_number'] ?? $validated['national_id'] ?? $guest->id_number,
            'national_id' => $validated['national_id'] ?? $validated['id_number'] ?? $guest->national_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث بيانات النزيل بنجاح.',
            'guest' => $guest->fresh()->loadCount('bookings'),
        ]);
    }

    /**
     * Delete guest.
     */
    public function deleteGuest(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'صلاحية مالك الفندق للمعاينة فقط.'], 403);
        }

        $guest = Guest::where('hotel_id', $user->hotel_id)->findOrFail($id);

        // Check if guest has active bookings
        $hasActiveBookings = Booking::where('hotel_id', $user->hotel_id)
            ->where('guest_id', $guest->id)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->exists();

        if ($hasActiveBookings) {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن حذف النزيل لوجود حجوزات نشطة أو إقامة حالية له في الفندق.',
            ], 422);
        }

        $guest->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف بيانات النزيل من السجل بنجاح.',
        ]);
    }

    /**
     * Get stay history for a guest with accurate nightly price and currency.
     */
    public function guestStays(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        $guest = Guest::where('hotel_id', $user->hotel_id)->findOrFail($id);

        $bookings = Booking::where('hotel_id', $user->hotel_id)
            ->where('guest_id', $guest->id)
            ->with(['room.roomType', 'invoices.payments'])
            ->latest('check_in')
            ->get();

        $stays = $bookings->map(function ($b) {
            $checkIn = \Carbon\Carbon::parse($b->check_in);
            $checkOut = \Carbon\Carbon::parse($b->check_out);
            $nights = max(1, $checkIn->diffInDays($checkOut));

            $invoice = $b->invoices->first();
            $currency = $invoice?->currency ?? $b->currency ?? 'SYP';

            // Calculate accurate nightly rate
            if ($invoice && (float)$invoice->amount > 0) {
                $nightlyRate = round((float)$invoice->amount / $nights, 2);
            } elseif ($currency === 'USD' && (float)$b->total_price_usd > 0) {
                $nightlyRate = round((float)$b->total_price_usd / $nights, 2);
            } else {
                $nightlyRate = round((float)$b->total_price / $nights, 2);
            }

            // Calculate exact total and paid amount
            if ($invoice) {
                $invoiceTotal = (float)$invoice->total;
                $isPaid = $invoice->status === 'paid';
                $actualPaid = $isPaid ? $invoiceTotal : (float)$invoice->payments->where('status', 'success')->sum('amount');
            } else {
                $invoiceTotal = ($currency === 'USD' && (float)$b->total_price_usd > 0) ? (float)$b->total_price_usd : (float)$b->total_price;
                $isPaid = $b->status === 'checked_out';
                $actualPaid = $isPaid ? $invoiceTotal : 0.0;
            }

            return [
                'booking_id' => $b->id,
                'room_id' => $b->room_id,
                'room_number' => $b->room?->room_number ?? '—',
                'room_type' => $b->room?->roomType?->name ?? 'غرفة فندقية',
                'check_in' => $b->check_in,
                'check_out' => $b->check_out,
                'nights' => $nights,
                'nightly_rate' => $nightlyRate,
                'currency' => $currency,
                'currency_symbol' => $currency === 'USD' ? '$' : 'ل.س',
                'status' => $b->status,
                'invoice_id' => $invoice?->id,
                'invoice_number' => $invoice?->invoice_number,
                'invoice_status' => $invoice?->status ?? 'no_invoice',
                'invoice_total' => $invoiceTotal,
                'actual_paid' => $actualPaid,
                'is_paid' => ($invoice?->status === 'paid') || ($actualPaid >= $invoiceTotal && $invoiceTotal > 0),
            ];
        });

        return response()->json([
            'status' => 'success',
            'guest' => [
                'id' => $guest->id,
                'name' => $guest->name,
                'phone' => $guest->phone,
                'national_id' => $guest->national_id ?? $guest->id_number,
            ],
            'stays' => $stays,
        ]);
    }

    /**
     * List and store accounting records.
     */
    public function accounts(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $hotelId = $user->hotel_id;

        $accounts = Account::where('hotel_id', $hotelId)
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => $accounts,
        ]);
    }

    public function storeAccount(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'عذراً، صلاحيات مالك الفندق مخصصة للاطلاع فقط.'], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:income,expense',
            'currency' => 'required|in:SYP,USD',
            'category' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'date' => 'nullable|date',
            'transaction_date' => 'nullable|date',
        ]);

        $title = $validated['title'] ?? $validated['description'] ?? $validated['category'] ?? 'قيد مالي';
        $notes = $validated['notes'] ?? $validated['description'] ?? $validated['category'] ?? null;
        $date = $validated['transaction_date'] ?? $validated['date'] ?? now()->toDateString();

        $entry = Account::create([
            'hotel_id' => $user->hotel_id,
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'title' => $title,
            'currency' => $validated['currency'],
            'transaction_date' => $date,
            'notes' => $notes,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل القيد المحاسبي بنجاح.',
            'data' => $entry,
        ]);
    }

    /**
     * Delete an accounting voucher entry with DeletionLog audit record.
     */
    public function deleteAccount(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'عذراً، صلاحيات مالك الفندق مخصصة للاطلاع فقط.'], 403);
        }

        $account = Account::where('hotel_id', $user->hotel_id)->findOrFail($id);
        if ($account->invoice_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن حذف قيد محاسبي مرتبط بفاتورة حجز مسددة. يرجى حذف الفاتورة المرتبطة لإلغاء القيد تلقائياً.',
            ], 422);
        }

        $reason = $request->input('reason', 'حذف يدوي لقيد في دفتر اليومية');

        DeletionLog::create([
            'hotel_id' => $user->hotel_id,
            'user_id' => $user->id,
            'record_type' => 'account',
            'record_id' => $account->id,
            'reference_number' => $account->reference_number ?: ('قيد #' . $account->id),
            'guest_name' => '—',
            'room_number' => '—',
            'amount' => $account->amount,
            'currency' => $account->currency ?: 'USD',
            'payment_status' => $account->type,
            'reason' => $reason,
            'snapshot' => $account->toArray(),
            'deleted_at' => now(),
        ]);

        $account->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف القيد المحاسبي بنجاح وتوثيق العملية في سجل المحذوفات والرقابة.',
        ]);
    }

    /**
     * List employees and their granted extra permissions.
     */
    public function employees(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $hotelId = $user->hotel_id;

        $employees = User::where('hotel_id', $hotelId)
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get()
            ->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'name' => $emp->name,
                    'email' => $emp->email,
                    'role' => $emp->role,
                    'permissions' => $emp->permissions_list,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $employees,
            'available_permissions' => \App\Http\Controllers\Admin\UserController::AVAILABLE_PERMISSIONS,
            'available_roles' => \App\Http\Controllers\Admin\UserController::EMPLOYEE_ROLES,
        ]);
    }

    /**
     * Store new employee.
     */
    public function storeEmployee(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner() || ! in_array($user->role, ['hotel_admin', 'admin', 'super_admin'])) {
            return response()->json(['status' => 'error', 'message' => 'إضافة الموظفين مخصصة لمدير الفندق فقط.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:' . implode(',', \App\Http\Controllers\Admin\UserController::EMPLOYEE_ROLES),
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $employee = User::create([
            'hotel_id' => $user->hotel_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'permissions' => $request->input('permissions', []),
            'is_verified' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إنشاء حساب الموظف وتحديد صلاحياته بنجاح.',
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'role' => $employee->role,
                'permissions' => $employee->permissions_list,
            ],
        ]);
    }

    /**
     * Grant / Revoke additional permissions for an employee.
     */
    public function updateEmployeePermissions(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner() || ! in_array($user->role, ['hotel_admin', 'admin', 'super_admin'])) {
            return response()->json(['status' => 'error', 'message' => 'صلاحية تعديل أذونات الموظفين مخصصة لمدير الفندق فقط.'], 403);
        }

        $employee = User::where('hotel_id', $user->hotel_id)->findOrFail($id);

        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $employee->permissions = $request->input('permissions', []);
        $employee->save();

        return response()->json([
            'status' => 'success',
            'message' => "تم تحديث الصلاحيات الممنوحة للموظف {$employee->name} بنجاح.",
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'role' => $employee->role,
                'permissions' => $employee->permissions_list,
            ],
        ]);
    }

    /**
     * Invoices list.
     */
    public function invoices(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $hotelId = $user->hotel_id;

        $query = Invoice::where('hotel_id', $hotelId)->with(['hotel', 'booking.guest', 'room'])->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('invoice_number', 'like', "%{$q}%")
                    ->orWhereHas('booking.guest', function ($g) use ($q) {
                        $g->where('name', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    })
                    ->orWhereHas('room', function ($r) use ($q) {
                        $r->where('room_number', 'like', "%{$q}%");
                    });
            });
        }

        $invoices = $query->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => $invoices,
        ]);
    }

    /**
     * Store custom invoice.
     */
    public function storeInvoice(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'صلاحية مالك الفندق للمعاينة فقط.'], 403);
        }

        $validated = $request->validate([
            'booking_id' => 'nullable|exists:bookings,id',
            'room_id' => 'nullable|exists:rooms,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:SYP,USD',
            'service_amount' => 'nullable|numeric|min:0',
            'service_description' => 'nullable|string|max:1000',
            'tax' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:paid,unpaid',
        ]);

        $amount = (float) $validated['amount'];
        $serviceAmount = (float) ($validated['service_amount'] ?? 0);
        $tax = (float) ($validated['tax'] ?? 0);
        $total = $amount + $serviceAmount + $tax;
        $status = $validated['status'] ?? 'paid';

        $invoice = Invoice::create([
            'hotel_id' => $user->hotel_id,
            'booking_id' => $validated['booking_id'] ?? null,
            'room_id' => $validated['room_id'] ?? null,
            'invoice_number' => 'INV-' . strtoupper(uniqid()),
            'amount' => $amount,
            'currency' => $validated['currency'],
            'service_amount' => $serviceAmount,
            'service_description' => $validated['service_description'] ?? null,
            'tax' => $tax,
            'total' => $total,
            'status' => $status,
            'paid_at' => $status === 'paid' ? now() : null,
        ]);

        if ($status === 'paid') {
            Account::create([
                'hotel_id' => $user->hotel_id,
                'invoice_id' => $invoice->id,
                'amount' => $total,
                'type' => 'income',
                'title' => "فاتورة رقم #{$invoice->invoice_number}: " . ($validated['service_description'] ?? 'خدمات إقامة'),
                'currency' => $validated['currency'],
                'notes' => 'تحصيل فاتورة خدمات وإقامة',
                'transaction_date' => now()->toDateString(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم إنشاء الفاتورة بنجاح.',
            'invoice' => $invoice->fresh()->load(['booking.guest', 'room']),
        ]);
    }

    /**
     * Pay invoice.
     */
    public function payInvoice(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'عذراً، صلاحيات مالك الفندق مخصصة للاطلاع فقط.'], 403);
        }

        $invoice = Invoice::where('hotel_id', $user->hotel_id)->findOrFail($id);

        $invoice->status = 'paid';
        $invoice->paid_at = now();
        $invoice->save();

        // Create accounting entry
        Account::create([
            'hotel_id' => $user->hotel_id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->total,
            'type' => 'income',
            'title' => "سداد الفاتورة رقم #{$invoice->invoice_number}",
            'currency' => $invoice->currency ?? 'USD',
            'notes' => 'إيراد إقامة فندقية',
            'transaction_date' => now()->toDateString(),
        ]);

        NotificationService::sendToHotelStaff(
            $user->hotel_id,
            'سداد فاتورة 💵',
            "تم سداد الفاتورة رقم #{$invoice->invoice_number} بقيمة {$invoice->total} {$invoice->currency}.",
            'invoice_paid',
            '💵',
            '/invoices'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل سداد الفاتورة وإيداع القيد المحاسبي بنجاح.',
            'invoice' => $invoice->fresh()->load(['booking.guest', 'room']),
        ]);
    }

    /**
     * Delete invoice with DeletionLog audit trail.
     */
    public function deleteInvoice(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'عذراً، صلاحيات مالك الفندق مخصصة للاطلاع فقط.'], 403);
        }

        $invoice = Invoice::where('hotel_id', $user->hotel_id)->with(['booking.guest', 'room'])->findOrFail($id);
        $reason = $request->input('reason', 'حذف يدوي للفاتورة من قبل الموظف');

        DeletionLog::create([
            'hotel_id' => $user->hotel_id,
            'user_id' => $user->id,
            'record_type' => 'invoice',
            'record_id' => $invoice->id,
            'reference_number' => $invoice->invoice_number,
            'guest_name' => $invoice->booking?->guest?->name ?: '—',
            'room_number' => $invoice->room?->room_number ?: ($invoice->booking?->room?->room_number ?: '—'),
            'amount' => $invoice->total,
            'currency' => $invoice->currency ?: 'USD',
            'payment_status' => $invoice->status,
            'reason' => $reason,
            'snapshot' => $invoice->toArray(),
            'deleted_at' => now(),
        ]);

        $invoice->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف الفاتورة بنجاح وتوثيق العملية في سجل المحذوفات والرقابة.',
        ]);
    }

    /**
     * Housekeeping: rooms needing cleaning.
     */
    public function cleaningRooms(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $rooms = Room::where('hotel_id', $user->hotel_id)
            ->where(function ($q) {
                $q->where('needs_cleaning', true)->orWhere('status', 'cleaning');
            })
            ->with('roomType')
            ->orderBy('room_number')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $rooms,
        ]);
    }

    /**
     * Housekeeping: complete cleaning.
     */
    public function completeCleaning(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        $room = Room::where('hotel_id', $user->hotel_id)->findOrFail($id);

        $room->update([
            'needs_cleaning' => false,
            'status' => 'available',
        ]);

        CleaningLog::create([
            'hotel_id' => $user->hotel_id,
            'room_id' => $room->id,
            'user_id' => $user->id,
            'cleaned_at' => now(),
            'notes' => $request->input('notes', 'تم التنظيف والتعقيم بنجاح'),
        ]);

        NotificationService::sendToHotelStaff(
            $user->hotel_id,
            'اكتمال تنظيف الغرفة ✨',
            "تم تأكيد تنظيف وتعقيم الغرفة رقم {$room->room_number} بنجاح وأصبحت جاهزة للتسكين.",
            'cleaning_done',
            '✨',
            '/rooms'
        );

        return response()->json([
            'status' => 'success',
            'message' => "تم تأكيد تنظيف الغرفة {$room->room_number} وتجهيزها للاستقبال.",
            'room' => $room,
        ]);
    }

    /**
     * Housekeeping: request cleaning.
     */
    public function requestCleaning(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        $room = Room::where('hotel_id', $user->hotel_id)->findOrFail($id);

        $room->update([
            'needs_cleaning' => true,
            'status' => ($room->status === 'occupied') ? 'occupied' : 'cleaning',
        ]);

        NotificationService::sendToHotelStaff(
            $user->hotel_id,
            'طلب تنظيف غرفة 🧹',
            "تم إرسال طلب تنظيف وتجهيز للغرفة رقم {$room->room_number}.",
            'cleaning',
            '🧹',
            '/cleaning'
        );

        return response()->json([
            'status' => 'success',
            'message' => "تم إرسال طلب تنظيف للغرفة {$room->room_number}.",
            'room' => $room,
        ]);
    }

    /**
     * Maintenance list.
     */
    public function maintenanceList(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $records = Maintenance::where('hotel_id', $user->hotel_id)
            ->with(['room.roomType'])
            ->latest()
            ->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => $records,
        ]);
    }

    /**
     * Store maintenance issue.
     */
    public function storeMaintenance(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'issue_type' => 'required|string|max:255',
            'priority' => 'required|in:low,normal,urgent',
            'notes' => 'nullable|string|max:1000',
        ]);

        $ticket = Maintenance::create([
            ...$validated,
            'hotel_id' => $user->hotel_id,
            'status' => 'pending',
            'opened_at' => now(),
        ]);

        Room::where('id', $validated['room_id'])->update(['status' => 'maintenance']);

        NotificationService::sendToHotelStaff(
            $user->hotel_id,
            'بلاغ صيانة جديد 🔧',
            "تم تسجيل بلاغ صيانة ({$validated['issue_type']}) للغرفة رقم {$ticket->room?->room_number}.",
            'maintenance',
            '🔧',
            '/maintenance'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل بلاغ الصيانة بنجاح.',
            'ticket' => $ticket->load('room'),
        ]);
    }

    /**
     * Update maintenance status.
     */
    public function updateMaintenanceStatus(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        $ticket = Maintenance::where('hotel_id', $user->hotel_id)->findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $ticket->status = $request->status;
        if ($request->status === 'completed') {
            $ticket->closed_at = now();
            if ($ticket->room?->status === 'maintenance') {
                $ticket->room->update(['status' => 'available']);
            }

            NotificationService::sendToHotelStaff(
                $user->hotel_id,
                'اكتمال أعمال الصيانة 🛠️',
                "تم الانتهاء من أعمال الصيانة للغرفة رقم {$ticket->room?->room_number} وإعادتها متاحة.",
                'maintenance_done',
                '🛠️',
                '/rooms'
            );
        }
        $ticket->save();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث حالة بلاغ الصيانة بنجاح.',
            'ticket' => $ticket,
        ]);
    }

    /**
     * Deletion logs (audit trail) with search & filters.
     */
    public function deletionLogs(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $hotelId = $user->hotel_id;

        $query = DeletionLog::where('hotel_id', $hotelId)->with('user')->latest('deleted_at');

        if ($request->filled('record_type') && $request->record_type !== 'all') {
            $query->where('record_type', $request->record_type);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($b) use ($q) {
                $b->where('reference_number', 'like', "%{$q}%")
                    ->orWhere('guest_name', 'like', "%{$q}%")
                    ->orWhere('room_number', 'like', "%{$q}%")
                    ->orWhere('reason', 'like', "%{$q}%");
            });
        }

        $logs = $query->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => $logs,
            'summary' => [
                'total_count' => DeletionLog::where('hotel_id', $hotelId)->count(),
                'total_syp' => (float) DeletionLog::where('hotel_id', $hotelId)
                    ->where(fn ($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))
                    ->sum('amount'),
                'total_usd' => (float) DeletionLog::where('hotel_id', $hotelId)
                    ->where('currency', 'USD')
                    ->sum('amount'),
            ],
        ]);
    }

    /**
     * Activity logs.
     */
    public function activityLogs(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $hotelStaffIds = User::where('hotel_id', $user->hotel_id)->pluck('id');

        $logs = ActivityLog::whereIn('user_id', $hotelStaffIds)
            ->with('user')
            ->latest()
            ->take(100)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $logs,
        ]);
    }

    /**
     * Complaints list.
     */
    public function complaints(Request $request)
    {
        $user = $this->checkStaffAccess($request);

        $complaints = Complaint::where('hotel_id', $user->hotel_id)
            ->with(['guest', 'room', 'handler'])
            ->latest()
            ->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => $complaints,
        ]);
    }

    /**
     * Store complaint.
     */
    public function storeComplaint(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'عذراً، صلاحيات مالك الفندق مخصصة للاطلاع فقط.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'room_id' => 'nullable|exists:rooms,id',
            'guest_id' => 'nullable|exists:guests,id',
        ]);

        $complaint = Complaint::create([
            ...$validated,
            'hotel_id' => $user->hotel_id,
            'status' => 'new',
        ]);

        NotificationService::sendToHotelStaff(
            $user->hotel_id,
            'شكوى / مقترح جديد ⚠️',
            "تم تسجيل شكوى جديدة ({$complaint->title}) في الغرفة " . ($complaint->room?->room_number ?? 'عامة') . ".",
            'complaint',
            '⚠️',
            '/complaints'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الشكوى/المقترح بنجاح.',
            'data' => $complaint->load(['guest', 'room']),
        ]);
    }

    /**
     * Update complaint status and response.
     */
    public function updateComplaint(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'عذراً، صلاحيات مالك الفندق مخصصة للاطلاع فقط.'], 403);
        }

        $complaint = Complaint::where('hotel_id', $user->hotel_id)->findOrFail($id);

        $request->validate([
            'status' => 'required|in:new,in_progress,closed',
            'response' => 'nullable|string|max:2000',
        ]);

        $complaint->update([
            'status' => $request->status,
            'response' => $request->response ?? $complaint->response,
            'handled_by' => $user->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث حالة الشكوى والرد عليها بنجاح.',
            'data' => $complaint->fresh()->load(['guest', 'room', 'handler']),
        ]);
    }

    /**
     * Comprehensive reports.
     */
    public function reports(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $hotelId = $user->hotel_id;

        $now = now();
        $today = $now->toDateString();
        $startOfWeek = $now->copy()->startOfWeek()->toDateString();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $startOfYear = $now->copy()->startOfYear()->toDateString();

        // Income queries helper
        $getRevenue = function ($startDate, $currency) use ($hotelId) {
            return (float) Account::where('hotel_id', $hotelId)
                ->where('type', 'income')
                ->where('currency', $currency)
                ->whereDate('transaction_date', '>=', $startDate)
                ->sum('amount');
        };

        $getExpense = function ($startDate, $currency) use ($hotelId) {
            return (float) Account::where('hotel_id', $hotelId)
                ->where('type', 'expense')
                ->where('currency', $currency)
                ->whereDate('transaction_date', '>=', $startDate)
                ->sum('amount');
        };

        // Room status counts
        $totalRooms = Room::where('hotel_id', $hotelId)->count();
        $occupiedRooms = Room::where('hotel_id', $hotelId)->where('status', 'occupied')->count();
        $availableRooms = Room::where('hotel_id', $hotelId)->where('status', 'available')->count();
        $cleaningRooms = Room::where('hotel_id', $hotelId)->where(fn ($q) => $q->where('status', 'cleaning')->orWhere('needs_cleaning', true))->count();
        $maintenanceRooms = Room::where('hotel_id', $hotelId)->where('status', 'maintenance')->count();
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        // Bookings count by status
        $bookingsConfirmed = Booking::where('hotel_id', $hotelId)->where('status', 'confirmed')->count();
        $bookingsCheckedIn = Booking::where('hotel_id', $hotelId)->where('status', 'checked_in')->count();
        $bookingsCheckedOut = Booking::where('hotel_id', $hotelId)->where('status', 'checked_out')->count();
        $bookingsCancelled = Booking::where('hotel_id', $hotelId)->where('status', 'cancelled')->count();

        // Pending maintenance & cleaning
        $pendingMaintenance = Maintenance::where('hotel_id', $hotelId)->where('status', '!=', 'completed')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'periods' => [
                    'today' => [
                        'revenue_syp' => $getRevenue($today, 'SYP'),
                        'expense_syp' => $getExpense($today, 'SYP'),
                        'revenue_usd' => $getRevenue($today, 'USD'),
                        'expense_usd' => $getExpense($today, 'USD'),
                        'bookings_count' => Booking::where('hotel_id', $hotelId)->whereDate('created_at', $today)->count(),
                    ],
                    'this_week' => [
                        'revenue_syp' => $getRevenue($startOfWeek, 'SYP'),
                        'expense_syp' => $getExpense($startOfWeek, 'SYP'),
                        'revenue_usd' => $getRevenue($startOfWeek, 'USD'),
                        'expense_usd' => $getExpense($startOfWeek, 'USD'),
                        'bookings_count' => Booking::where('hotel_id', $hotelId)->whereDate('created_at', '>=', $startOfWeek)->count(),
                    ],
                    'this_month' => [
                        'revenue_syp' => $getRevenue($startOfMonth, 'SYP'),
                        'expense_syp' => $getExpense($startOfMonth, 'SYP'),
                        'revenue_usd' => $getRevenue($startOfMonth, 'USD'),
                        'expense_usd' => $getExpense($startOfMonth, 'USD'),
                        'bookings_count' => Booking::where('hotel_id', $hotelId)->whereDate('created_at', '>=', $startOfMonth)->count(),
                    ],
                    'this_year' => [
                        'revenue_syp' => $getRevenue($startOfYear, 'SYP'),
                        'expense_syp' => $getExpense($startOfYear, 'SYP'),
                        'revenue_usd' => $getRevenue($startOfYear, 'USD'),
                        'expense_usd' => $getExpense($startOfYear, 'USD'),
                        'bookings_count' => Booking::where('hotel_id', $hotelId)->whereDate('created_at', '>=', $startOfYear)->count(),
                    ],
                ],
                'rooms' => [
                    'total' => $totalRooms,
                    'occupied' => $occupiedRooms,
                    'available' => $availableRooms,
                    'cleaning' => $cleaningRooms,
                    'maintenance' => $maintenanceRooms,
                    'occupancy_rate' => $occupancyRate,
                ],
                'bookings_summary' => [
                    'confirmed' => $bookingsConfirmed,
                    'checked_in' => $bookingsCheckedIn,
                    'checked_out' => $bookingsCheckedOut,
                    'cancelled' => $bookingsCancelled,
                ],
                'operations' => [
                    'pending_maintenance' => $pendingMaintenance,
                    'cleaning_rooms' => $cleaningRooms,
                ],
            ],
        ]);
    }

    /**
     * Detailed Accounting Bank Statement Report (Daily, Weekly, Monthly, Yearly, Custom).
     */
    public function accountingReport(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $hotelId = $user->hotel_id;
        $hotel = $user->hotel;

        $period = $request->query('period', 'monthly');
        if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly', 'custom'])) {
            $period = 'monthly';
        }

        $now = Carbon::now();
        $date = $request->query('date', $now->toDateString());
        $weekDate = $request->query('week_date', $now->toDateString());
        $month = $request->query('month', $now->format('Y-m'));
        $year = (int)$request->query('year', $now->year);
        $currency = $request->query('currency', 'all');
        if (!in_array($currency, ['all', 'SYP', 'USD'])) {
            $currency = 'all';
        }
        $type = $request->query('type', 'all');
        if (!in_array($type, ['all', 'income', 'expense'])) {
            $type = 'all';
        }

        switch ($period) {
            case 'daily':
                $targetDate = Carbon::parse($date)->toDateString();
                $from = $targetDate;
                $to = $targetDate;
                $periodLabel = 'تقرير يومي ليوم ' . $targetDate;
                break;

            case 'weekly':
                $carbonWeek = Carbon::parse($weekDate);
                $from = $carbonWeek->copy()->startOfWeek()->toDateString();
                $to = $carbonWeek->copy()->endOfWeek()->toDateString();
                $periodLabel = 'تقرير أسبوعي للفترة من ' . $from . ' إلى ' . $to;
                break;

            case 'yearly':
                $from = Carbon::createFromDate($year, 1, 1)->startOfYear()->toDateString();
                $to = Carbon::createFromDate($year, 12, 31)->endOfYear()->toDateString();
                $periodLabel = 'تقرير سنوي لعام ' . $year;
                break;

            case 'custom':
                $from = $request->query('from', $now->copy()->startOfMonth()->toDateString());
                $to = $request->query('to', $now->toDateString());
                if ($from > $to) {
                    $tmp = $from;
                    $from = $to;
                    $to = $tmp;
                }
                $periodLabel = 'تقرير مخصص للفترة من ' . $from . ' إلى ' . $to;
                break;

            case 'monthly':
            default:
                try {
                    $carbonMonth = Carbon::parse($month . '-01');
                } catch (\Exception $e) {
                    $carbonMonth = $now;
                }
                $from = $carbonMonth->copy()->startOfMonth()->toDateString();
                $to = $carbonMonth->copy()->endOfMonth()->toDateString();
                $periodLabel = 'تقرير شهري لشهر ' . $carbonMonth->format('m/Y');
                break;
        }

        // Opening Balance: Transactions before $from
        $priorQuery = Account::where('hotel_id', $hotelId)
            ->whereDate('transaction_date', '<', $from);

        if ($currency === 'USD') {
            $priorIncomeUSD = (float)(clone $priorQuery)->where('currency', 'USD')->where('type', 'income')->sum('amount');
            $priorExpenseUSD = (float)(clone $priorQuery)->where('currency', 'USD')->where('type', 'expense')->sum('amount');
            $openingBalanceUSD = $priorIncomeUSD - $priorExpenseUSD;
            $openingBalanceSYP = 0.0;
        } elseif ($currency === 'SYP') {
            $priorIncomeSYP = (float)(clone $priorQuery)->where(fn($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))->where('type', 'income')->sum('amount');
            $priorExpenseSYP = (float)(clone $priorQuery)->where(fn($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))->where('type', 'expense')->sum('amount');
            $openingBalanceSYP = $priorIncomeSYP - $priorExpenseSYP;
            $openingBalanceUSD = 0.0;
        } else {
            $priorIncomeSYP = (float)(clone $priorQuery)->where(fn($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))->where('type', 'income')->sum('amount');
            $priorExpenseSYP = (float)(clone $priorQuery)->where(fn($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))->where('type', 'expense')->sum('amount');
            $openingBalanceSYP = $priorIncomeSYP - $priorExpenseSYP;

            $priorIncomeUSD = (float)(clone $priorQuery)->where('currency', 'USD')->where('type', 'income')->sum('amount');
            $priorExpenseUSD = (float)(clone $priorQuery)->where('currency', 'USD')->where('type', 'expense')->sum('amount');
            $openingBalanceUSD = $priorIncomeUSD - $priorExpenseUSD;
        }

        // Transactions within period
        $periodQuery = Account::where('hotel_id', $hotelId)
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to);

        if ($currency === 'USD') {
            $periodQuery->where('currency', 'USD');
        } elseif ($currency === 'SYP') {
            $periodQuery->where(fn($q) => $q->where('currency', 'SYP')->orWhereNull('currency'));
        }

        $incomeSYP = (float) (clone $periodQuery)->where(fn($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))->where('type', 'income')->sum('amount');
        $expenseSYP = (float) (clone $periodQuery)->where(fn($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))->where('type', 'expense')->sum('amount');
        $incomeUSD = (float) (clone $periodQuery)->where('currency', 'USD')->where('type', 'income')->sum('amount');
        $expenseUSD = (float) (clone $periodQuery)->where('currency', 'USD')->where('type', 'expense')->sum('amount');

        $totalIncome = (float) (clone $periodQuery)->where('type', 'income')->sum('amount');
        $totalExpense = (float) (clone $periodQuery)->where('type', 'expense')->sum('amount');

        // Entries
        $entries = (clone $periodQuery)
            ->when($type !== 'all', fn ($q) => $q->where('type', $type))
            ->with(['invoice.booking.guest', 'invoice.room'])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalanceSYP = $openingBalanceSYP;
        $runningBalanceUSD = $openingBalanceUSD;
        $statementRows = [];

        foreach ($entries as $entry) {
            $entryCurrency = ($entry->currency === 'USD') ? 'USD' : 'SYP';
            $isIncome = ($entry->type === 'income');
            $inflow = $isIncome ? (float)$entry->amount : 0.0;
            $outflow = !$isIncome ? (float)$entry->amount : 0.0;

            if ($entryCurrency === 'USD') {
                $runningBalanceUSD += ($inflow - $outflow);
            } else {
                $runningBalanceSYP += ($inflow - $outflow);
            }

            $statementRows[] = [
                'id' => $entry->id,
                'date' => $entry->transaction_date ? Carbon::parse($entry->transaction_date)->format('Y-m-d') : '',
                'ref' => $entry->invoice?->invoice_number ?? ('TXN-' . str_pad($entry->id, 5, '0', STR_PAD_LEFT)),
                'title' => $entry->title,
                'notes' => $entry->notes,
                'type' => $entry->type,
                'currency' => $entryCurrency,
                'inflow' => $inflow,
                'outflow' => $outflow,
                'balance' => $entryCurrency === 'USD' ? $runningBalanceUSD : $runningBalanceSYP,
                'balance_syp' => $runningBalanceSYP,
                'balance_usd' => $runningBalanceUSD,
            ];
        }

        $closingBalanceSYP = $openingBalanceSYP + $incomeSYP - $expenseSYP;
        $closingBalanceUSD = $openingBalanceUSD + $incomeUSD - $expenseUSD;

        return response()->json([
            'status' => 'success',
            'data' => [
                'hotel_name' => $hotel?->name ?? 'الفندق',
                'hotel_address' => $hotel?->address ?? 'الجمهورية العربية السورية',
                'hotel_phone' => $hotel?->phone ?? '—',
                'period' => $period,
                'period_label' => $periodLabel,
                'from' => $from,
                'to' => $to,
                'currency' => $currency,
                'type' => $type,
                'date' => $date,
                'week_date' => $weekDate,
                'month' => $month,
                'year' => $year,
                'opening_balance_syp' => $openingBalanceSYP,
                'opening_balance_usd' => $openingBalanceUSD,
                'opening_balance' => $currency === 'USD' ? $openingBalanceUSD : $openingBalanceSYP,
                'income_syp' => $incomeSYP,
                'expense_syp' => $expenseSYP,
                'profit_syp' => $incomeSYP - $expenseSYP,
                'income_usd' => $incomeUSD,
                'expense_usd' => $expenseUSD,
                'profit_usd' => $incomeUSD - $expenseUSD,
                'closing_balance_syp' => $closingBalanceSYP,
                'closing_balance_usd' => $closingBalanceUSD,
                'closing_balance' => $currency === 'USD' ? $closingBalanceUSD : $closingBalanceSYP,
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_balance' => $totalIncome - $totalExpense,
                'statement_rows' => $statementRows,
                'transactions_count' => count($statementRows),
            ],
        ]);
    }

    /**
     * Printer & Scanner settings.
     */
    public function printerSettings(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $hotel = Hotel::findOrFail($user->hotel_id);

        return response()->json([
            'status' => 'success',
            'data' => $hotel->printer_settings_list,
        ]);
    }

    /**
     * Update printer and scanner settings.
     */
    public function updatePrinterSettings(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        if ($user->isHotelOwner()) {
            return response()->json(['status' => 'error', 'message' => 'عذراً، صلاحيات مالك الفندق مخصصة للاطلاع فقط.'], 403);
        }

        $hotel = Hotel::findOrFail($user->hotel_id);

        $validated = $request->validate([
            'invoice_printer' => 'nullable|string|max:255',
            'report_printer' => 'nullable|string|max:255',
            'invoice_format' => 'required|in:thermal_80mm,a4',
            'scanner_source' => 'required|in:camera,scanner_hardware,custom',
            'auto_print' => 'nullable|boolean',
        ]);

        $settings = [
            'invoice_printer' => $validated['invoice_printer'] ?? 'طابعة الفواتير الافتراضية',
            'report_printer' => $validated['report_printer'] ?? 'طابعة التقارير الافتراضية',
            'invoice_format' => $validated['invoice_format'],
            'scanner_source' => $validated['scanner_source'],
            'auto_print' => (bool) ($validated['auto_print'] ?? false),
        ];

        $hotel->update([
            'printer_settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم حفظ إعدادات الطباعة والسكنر بنجاح.',
            'data' => $settings,
        ]);
    }

    /**
     * Barcode & QR Scanner instant lookup.
     */
    public function scannerLookup(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $hotelId = $user->hotel_id;
        $code = trim($request->input('code', ''));

        if (empty($code)) {
            return response()->json(['status' => 'error', 'message' => 'الرمز الممسوح فارغ.'], 422);
        }

        // Try booking by booking_number or ID
        $booking = Booking::where('hotel_id', $hotelId)
            ->where(function ($q) use ($code) {
                $q->where('booking_number', $code)
                    ->orWhere('id', $code);
            })
            ->with(['guest', 'room.roomType', 'invoices'])
            ->first();

        if ($booking) {
            return response()->json([
                'status' => 'success',
                'type' => 'booking',
                'title' => "حجز رقم: {$booking->booking_number}",
                'data' => $booking,
            ]);
        }

        // Try invoice by invoice_number
        $invoice = Invoice::where('hotel_id', $hotelId)
            ->where('invoice_number', $code)
            ->with(['booking.guest'])
            ->first();

        if ($invoice) {
            return response()->json([
                'status' => 'success',
                'type' => 'invoice',
                'title' => "فاتورة رقم: {$invoice->invoice_number}",
                'data' => $invoice,
            ]);
        }

        // Try guest by phone, id_number or passport_number
        $guest = Guest::where(function ($q) use ($code) {
            $q->where('phone', $code)
                ->orWhere('id_number', $code)
                ->orWhere('passport_number', $code);
        })->first();

        if ($guest) {
            return response()->json([
                'status' => 'success',
                'type' => 'guest',
                'title' => "نزيل: {$guest->name}",
                'data' => $guest,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => "لم يتم العثور على أي حجز أو فاتورة أو نزيل بالرمز: {$code}",
        ], 404);
    }

    /**
     * Get hotel subscription details for desktop and mobile clients.
     */
    public function subscriptionInfo(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $hotel = Hotel::with(['rooms'])->findOrFail($user->hotel_id);
        
        \App\Models\SubscriptionPlan::seedDefaultsIfNeeded();
        $sub = $hotel->active_subscription;

        if (!$sub) {
            $defaultPlan = \App\Models\SubscriptionPlan::where('slug', 'pro')->first() ?? \App\Models\SubscriptionPlan::first();
            if ($defaultPlan) {
                $sub = \App\Models\HotelSubscription::create([
                    'hotel_id' => $hotel->id,
                    'plan_id' => $defaultPlan->id,
                    'status' => 'active',
                    'billing_cycle' => 'monthly',
                    'currency' => 'USD',
                    'price_paid' => $defaultPlan->price_monthly_usd,
                    'starts_at' => now()->toDateString(),
                    'expires_at' => now()->addMonths(1)->toDateString(),
                    'notes' => 'اشتراك تلقائي مفعل من النظام',
                ]);
                $sub->load('plan');
            }
        }

        $plan = $sub?->plan;
        $roomsCount = $hotel->rooms()->count();
        $maxRooms = $plan?->max_rooms ?? -1;
        $daysRemaining = $sub ? $sub->days_remaining : 0;
        $isExpiringSoon = $sub ? $sub->is_expiring_soon : false;
        $isExpired = $sub ? $sub->is_expired : false;

        $warningMessage = null;
        if ($isExpired) {
            $warningMessage = "⚠️ تنبيه هام: لقد انتهت صلاحية اشتراك الفندق بتاريخ {$sub?->expires_at?->format('Y-m-d')}. يرجى التجديد الفوري لتجنب انقطاع الخدمات.";
        } elseif ($isExpiringSoon) {
            $warningMessage = "⚠️ تنبيه: اشتراك الفندق ينتهي خلال {$daysRemaining} أيام (بتاريخ {$sub?->expires_at?->format('Y-m-d')}). يرجى تجديد الاشتراك.";
        }

        return response()->json([
            'status' => 'success',
            'subscription' => [
                'plan_name' => $plan?->name ?? 'باقة قياسية',
                'plan_slug' => $plan?->slug ?? 'standard',
                'status' => $sub?->status ?? 'active',
                'status_label' => $sub?->status_label ?? 'ساري',
                'starts_at' => $sub?->starts_at?->format('Y-m-d'),
                'expires_at' => $sub?->expires_at?->format('Y-m-d'),
                'days_remaining' => $daysRemaining,
                'is_expiring_soon' => $isExpiringSoon,
                'is_expired' => $isExpired,
                'warning_message' => $warningMessage,
                'rooms_count' => $roomsCount,
                'max_rooms' => $maxRooms,
                'currency' => $sub?->currency ?? 'USD',
                'price_paid' => $sub?->price_paid ?? 0,
            ],
        ]);
    }

    /**
     * List support tickets for current hotel.
     */
    public function supportTickets(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $tickets = \App\Models\SupportTicket::where('hotel_id', $user->hotel_id)
            ->with(['creator', 'latestMessage'])
            ->latest('updated_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'tickets' => $tickets,
            'data' => $tickets,
        ]);
    }

    /**
     * Store new support ticket from desktop/api.
     */
    public function storeSupportTicket(Request $request)
    {
        $user = $this->checkStaffAccess($request);
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'department' => 'required|in:technical,billing,sales,feature_request,general',
            'priority' => 'required|in:low,medium,high,urgent',
            'message' => 'required|string|max:5000',
        ]);

        $ticket = \App\Models\SupportTicket::create([
            'hotel_id' => $user->hotel_id,
            'user_id' => $user->id,
            'subject' => $validated['subject'],
            'department' => $validated['department'],
            'priority' => $validated['priority'],
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        \App\Models\SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
            'is_staff_reply' => false,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "تم فتح تذكرة الدعم الفني رقم {$ticket->ticket_number} بنجاح.",
            'ticket' => $ticket->fresh()->load('messages.sender'),
        ]);
    }

    /**
     * Get ticket thread messages.
     */
    public function showSupportTicket(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        $ticket = \App\Models\SupportTicket::where('hotel_id', $user->hotel_id)
            ->with(['creator', 'messages.sender'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'ticket' => $ticket,
        ]);
    }

    /**
     * Reply to a support ticket from desktop/api.
     */
    public function replySupportTicket(Request $request, $id)
    {
        $user = $this->checkStaffAccess($request);
        $ticket = \App\Models\SupportTicket::where('hotel_id', $user->hotel_id)->findOrFail($id);

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $msg = \App\Models\SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
            'is_staff_reply' => false,
        ]);

        $ticket->update([
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال ردكم بنجاح.',
            'reply' => $msg->load('sender'),
            'ticket' => $ticket->fresh()->load('messages.sender'),
        ]);
    }
}
