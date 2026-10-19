<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Guest;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index()
    {
        $complaints = Complaint::where('hotel_id', auth()->user()->hotel_id)
                               ->with('guest', 'booking', 'room', 'handler')
                               ->orderBy('created_at', 'desc')
                               ->get();

        return view('admin.complaints.index', compact('complaints'));
    }

    public function create()
    {
        $hotel_id = auth()->user()->hotel_id;

        $guests = Guest::where('hotel_id', $hotel_id)->get();
        $bookings = Booking::where('hotel_id', $hotel_id)->get();
        $rooms = Room::where('hotel_id', $hotel_id)->get();

        return view('admin.complaints.create', compact('guests', 'bookings', 'rooms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'   => 'required',
            'message' => 'required',
        ]);

        Complaint::create([
            'hotel_id'  => auth()->user()->hotel_id,
            'guest_id'  => $request->guest_id,
            'booking_id'=> $request->booking_id,
            'room_id'   => $request->room_id,
            'title'     => $request->title,
            'message'   => $request->message,
            'status'    => 'new',
        ]);

        return redirect()->route('complaints.index')->with('success', 'تم تسجيل الشكوى بنجاح');
    }

    public function show(Complaint $complaint)
    {
        $this->ensureComplaintBelongsToHotel($complaint);
        $complaint->load(['guest', 'booking', 'room', 'handler']);

        return view('admin.complaints.show', compact('complaint'));
    }

    public function edit(Complaint $complaint)
    {
        $this->ensureComplaintBelongsToHotel($complaint);
        return view('admin.complaints.edit', compact('complaint'));
    }

    public function update(Request $request, Complaint $complaint)
    {
        $this->ensureComplaintBelongsToHotel($complaint);
        $request->validate([
            'status'   => 'required|in:new,in_progress,closed',
            'response' => 'nullable',
        ]);

        $complaint->update([
            'status'     => $request->status,
            'response'   => $request->response,
            'handled_by' => auth()->id(),
        ]);

        return redirect()->route('complaints.index')->with('success', 'تم تحديث حالة الشكوى');
    }

    public function destroy(Complaint $complaint)
    {
        $this->ensureComplaintBelongsToHotel($complaint);
        $complaint->delete();
        return redirect()->route('complaints.index')->with('success', 'تم حذف الشكوى');
    }

    private function ensureComplaintBelongsToHotel(Complaint $complaint): void
    {
        abort_unless((int) $complaint->hotel_id === (int) auth()->user()->hotel_id, 404);
    }
}
