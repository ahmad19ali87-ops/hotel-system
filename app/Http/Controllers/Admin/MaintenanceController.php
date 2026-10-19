<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use App\Models\Room;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    public function index()
    {
        $items = Maintenance::where('hotel_id', $this->hotelId())
            ->with('room')
            ->orderByDesc('opened_at')
            ->get();

        return view('admin.maintenance.index', compact('items'));
    }

    public function create()
    {
        $rooms = Room::where('hotel_id', $this->hotelId())
            ->orderBy('room_number')
            ->get();

        return view('admin.maintenance.create', compact('rooms'));
    }

    public function store(Request $request)
    {
        $hotelId = $this->hotelId();
        $validated = $request->validate([
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')->where('hotel_id', $hotelId)],
            'issue_type' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $maintenance = Maintenance::create([
            ...$validated,
            'hotel_id' => $hotelId,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $room = Room::where('hotel_id', $hotelId)
            ->whereKey($validated['room_id'])
            ->first();

        if ($room) {
            $room->update(['status' => 'maintenance']);
        }

        // إشعار موجه لمسؤولي الصيانة
        NotificationService::sendToRole(
            ['maintenance'],
            $hotelId,
            'طلب صيانة جديد للغرفة ' . ($room?->room_number ?? '—'),
            "تم تسجيل بلاغ صيانة: {$validated['issue_type']} (درجة الأولوية: {$validated['priority']}).",
            'maintenance_request',
            'fas fa-tools',
            route('maintenance.index'),
            ['room_id' => $validated['room_id'], 'priority' => $validated['priority'], 'maintenance_id' => $maintenance->id]
        );

        return redirect()->route('maintenance.index')->with('status', 'تم تسجيل أمر الصيانة وإرسال إشعار لفريق الصيانة.');
    }

    public function edit(Maintenance $maintenance)
    {
        $this->ensureBelongsToHotel($maintenance);
        $rooms = Room::where('hotel_id', $this->hotelId())->orderBy('room_number')->get();

        return view('admin.maintenance.edit', compact('maintenance', 'rooms'));
    }

    public function update(Request $request, Maintenance $maintenance)
    {
        $this->ensureBelongsToHotel($maintenance);
        $hotelId = $this->hotelId();
        $validated = $request->validate([
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')->where('hotel_id', $hotelId)],
            'issue_type' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'status' => ['required', Rule::in(['open', 'in_progress', 'closed'])],
        ]);

        $maintenance->update([
            ...$validated,
            'closed_at' => $validated['status'] === 'closed' ? ($maintenance->closed_at ?? now()) : null,
        ]);

        Room::where('hotel_id', $hotelId)
            ->whereKey($validated['room_id'])
            ->update(['status' => $validated['status'] === 'closed' ? 'available' : 'maintenance']);

        return redirect()->route('maintenance.index')->with('status', 'تم تحديث أمر الصيانة.');
    }

    public function destroy(Maintenance $maintenance)
    {
        $this->ensureBelongsToHotel($maintenance);

        if ($maintenance->status !== 'closed') {
            Room::where('hotel_id', $maintenance->hotel_id)
                ->whereKey($maintenance->room_id)
                ->where('status', 'maintenance')
                ->update(['status' => 'available']);
        }

        $maintenance->delete();

        return redirect()->route('maintenance.index')->with('status', 'تم حذف أمر الصيانة.');
    }

    private function hotelId(): int
    {
        abort_unless(auth()->user()?->hotel_id, 403);

        return (int) auth()->user()->hotel_id;
    }

    private function ensureBelongsToHotel(Maintenance $maintenance): void
    {
        abort_unless((int) $maintenance->hotel_id === $this->hotelId(), 404);
    }
}