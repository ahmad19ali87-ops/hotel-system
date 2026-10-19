<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Maintenance;
use App\Models\Room;
use App\Models\Hotel;

class MaintenanceController extends Controller
{
    public function index()
    {
        $items = Maintenance::where('hotel_id', auth()->user()->hotel_id)
                            ->with('room')
                            ->orderBy('opened_at', 'desc')
                            ->get();

        return view('admin.maintenance.index', compact('items'));
    }

    public function create()
    {
        $rooms = Room::where('hotel_id', auth()->user()->hotel_id)->get();
        return view('admin.maintenance.create', compact('rooms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'room_id'     => 'required|exists:rooms,id',
            'issue_type'  => 'required|string|max:255',
            'priority'    => 'required|string|max:255',
            'notes'       => 'nullable|string',
        ]);

        Maintenance::create([
            'hotel_id'   => auth()->user()->hotel_id,
            'room_id'    => $request->room_id,
            'issue_type' => $request->issue_type,
            'priority'   => $request->priority,
            'notes'      => $request->notes,
            'status'     => 'open',
            'opened_at'  => now(),
        ]);

        // تغيير حالة الغرفة إلى صيانة
        $room = Room::find($request->room_id);
        $room->status = 'maintenance';
        $room->save();

        return redirect()->route('maintenance.index')
                         ->with('success', 'تم تسجيل بلاغ الصيانة بنجاح');
    }

    public function edit(Maintenance $maintenance)
    {
        if ($maintenance->hotel_id !== auth()->user()->hotel_id) {
            abort(403);
        }

        $rooms = Room::where('hotel_id', auth()->user()->hotel_id)->get();

        return view('admin.maintenance.edit', compact('maintenance', 'rooms'));
    }

    public function update(Request $request, Maintenance $maintenance)
    {
        if ($maintenance->hotel_id !== auth()->user()->hotel_id) {
            abort(403);
        }

        $request->validate([
            'room_id'     => 'required|exists:rooms,id',
            'issue_type'  => 'required|string|max:255',
            'priority'    => 'required|string|max:255',
            'notes'       => 'nullable|string',
            'status'      => 'required|string|max:255',
            'opened_at'   => 'nullable|date',
            'closed_at'   => 'nullable|date',
        ]);

        $maintenance->update([
            'room_id'    => $request->room_id,
            'issue_type' => $request->issue_type,
            'priority'   => $request->priority,
            'notes'      => $request->notes,
            'status'     => $request->status,
            'opened_at'  => $request->opened_at,
            'closed_at'  => $request->closed_at,
        ]);

        return redirect()->route('maintenance.index')
                         ->with('success', 'تم تحديث بلاغ الصيانة');
    }

    public function destroy(Maintenance $maintenance)
    {
        if ($maintenance->hotel_id !== auth()->user()->hotel_id) {
            abort(403);
        }

        $maintenance->delete();

        return redirect()->route('maintenance.index')
                         ->with('success', 'تم حذف بلاغ الصيانة');
    }
}
