<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HotelSupportController extends Controller
{
    private function hotelId(): int
    {
        return (int) Auth::user()->hotel_id;
    }

    private function ensureTicketBelongsToHotel(SupportTicket $ticket): void
    {
        if ((int)$ticket->hotel_id !== $this->hotelId()) {
            abort(403, 'غير مصرح لك باستعراض هذه التذكرة.');
        }
    }

    /**
     * List all support tickets for current hotel.
     */
    public function index()
    {
        $tickets = SupportTicket::where('hotel_id', $this->hotelId())
            ->with(['creator', 'latestMessage'])
            ->latest('updated_at')
            ->paginate(15);

        return view('admin.support.index', compact('tickets'));
    }

    /**
     * Show create ticket form.
     */
    public function create()
    {
        return view('admin.support.create');
    }

    /**
     * Store new support ticket.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'department' => 'required|in:technical,billing,sales,feature_request,general',
            'priority' => 'required|in:low,medium,high,urgent',
            'message' => 'required|string|max:5000',
        ]);

        $ticket = SupportTicket::create([
            'hotel_id' => $this->hotelId(),
            'user_id' => auth()->id(),
            'subject' => $validated['subject'],
            'department' => $validated['department'],
            'priority' => $validated['priority'],
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'is_staff_reply' => false,
        ]);

        return redirect()->route('admin.support.show', $ticket)->with('success', "تم فتح تذكرة الدعم الفني رقم {$ticket->ticket_number} بنجاح. سنقوم بالرد عليك في أقرب وقت.");
    }

    /**
     * Show ticket conversation thread.
     */
    public function show(SupportTicket $ticket)
    {
        $this->ensureTicketBelongsToHotel($ticket);
        $ticket->load(['creator', 'messages.sender']);

        return view('admin.support.show', compact('ticket'));
    }

    /**
     * Reply to a ticket.
     */
    public function reply(Request $request, SupportTicket $ticket)
    {
        $this->ensureTicketBelongsToHotel($ticket);

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'is_staff_reply' => false,
        ]);

        $ticket->update([
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        return back()->with('success', 'تم إرسال ردك بنجاح.');
    }
}

