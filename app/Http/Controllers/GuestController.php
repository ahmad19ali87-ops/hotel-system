<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Hotel;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function index($hotelSlug = null)
    {
        if ($hotelSlug) {
            $hotel = Hotel::where('slug', $hotelSlug)->firstOrFail();
            $guests = Guest::where('hotel_id', $hotel->id)->get();

            return view('guests.index', compact('guests', 'hotel'));
        }

        $guests = Guest::all();

        return view('guests.index', compact('guests'));
    }

    public function store(Request $request, $hotelSlug = null)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'nullable|email',
        ]);

        $data = $request->all();

        if ($hotelSlug) {
            $hotel = Hotel::where('slug', $hotelSlug)->firstOrFail();
            $data['hotel_id'] = $hotel->id;

            Guest::create($data);

            return redirect()->route('guests.index', $hotelSlug);
        }

        Guest::create($data);

        return redirect()->route('guests.index')->with('success', 'تم إضافة العميل بنجاح');
    }

    public function create()
    {
        return view('guests.create');
    }

    public function edit(Guest $guest)
    {
        return view('guests.edit', compact('guest'));
    }

    public function update(Request $request, Guest $guest)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'nullable|email',
        ]);

        $guest->update($request->all());

        return redirect()->route('guests.index')->with('success', 'تم تعديل بيانات العميل');
    }
 public function occupancy()
    {
        $rooms = Room::all();

        $total = $rooms->count();
        $available = $rooms->where('status', 'available')->count();
        $booked = $rooms->where('status', 'booked')->count();
        $cleaning = $rooms->where('status', 'cleaning')->count();
        $maintenance = $rooms->where('status', 'maintenance')->count();     
    $hotel = Hotel::where('slug', $hotelSlug)->firstOrFail();
    Room::where('hotel_id', $hotel->id)->get();
 }

    public function destroy(Guest $guest)
    {
        $guest->delete();

        return redirect()->route('guests.index')->with('success', 'تم حذف العميل');
    }
}
