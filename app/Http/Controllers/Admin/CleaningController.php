<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cleaning;
use App\Models\Room;

class CleaningController extends Controller
{
    public function index()
    {
        $items = Cleaning::where('hotel_id', auth()->user()->hotel_id)
                         ->with('room')
                         ->get();

        return view('admin.cleaning.index', compact('items'));
    }

    public function create()
    {
        $rooms = Room::where('hotel_id', auth()->user()->hotel_id)->get();
        return view('admin.cleaning.create', compact('rooms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required',
            'requested_at' => 'nullable|date',
            'notes' => 'nullable'
        ]);

        Cleaning::create([
            'hotel_id' => auth()->user()->hotel_id,
            'room_id' => $request->room_id,
            'status' => 'pending',
            'requested_at' => $request->requested_at ?? now()->toDateString(),
            'notes' => $request->notes
        ]);

        return redirect()->route('cleaning.index')->with('success', 'تم تسجيل طلب التنظيف');
    }

    public function edit(Cleaning $cleaning)
    {
        $rooms = Room::where('hotel_id', auth()->user()->hotel_id)->get();
        return view('admin.cleaning.edit', compact('cleaning', 'rooms'));
    }

    public function update(Request $request, Cleaning $cleaning)
    {
        $request->validate([
            'room_id' => 'required',
            'status' => 'required',
            'requested_at' => 'nullable|date',
            'notes' => 'nullable'
        ]);

        $cleaning->update([
            'room_id' => $request->room_id,
            'status' => $request->status,
            'requested_at' => $request->requested_at,
            'notes' => $request->notes
        ]);

        return redirect()->route('cleaning.index')->with('success', 'تم تحديث طلب التنظيف');
    }

    public function destroy(Cleaning $cleaning)
    {
        $cleaning->delete();
        return redirect()->route('cleaning.index')->with('success', 'تم حذف طلب التنظيف');
    }
}
