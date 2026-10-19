<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    public function index()
    {
        $types = RoomType::all();
        return view('room_types.index', compact('types'));
    }

    public function create()
    {
        return view('room_types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'nullable',
        ]);

        RoomType::create($request->all());

        return redirect()->route('room-types.index')->with('success', 'تم إضافة النوع بنجاح');
    }

    public function edit(RoomType $roomType)
    {
        return view('room_types.edit', compact('roomType'));
    }

    public function update(Request $request, RoomType $roomType)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'nullable',
        ]);

        $roomType->update($request->all());
$hotel = Hotel::where('slug', $hotelSlug)->firstOrFail();

$rooms = Room::where('hotel_id', $hotel->id)->get();

        return redirect()->route('room-types.index')->with('success', 'تم تعديل النوع بنجاح');
    }

     public function occupancy()  
    {
          $totalRooms = Room::count();
          $bookedRooms = Room::where('status', 'booked')->count();
          $occupancyRate = $totalRooms > 0 ? ($bookedRooms / $totalRooms) * 100 : 0;
    
          return view('room_types.occupancy', compact('totalRooms', 'bookedRooms', 'occupancyRate'));       

$hotel = Hotel::where('slug', $hotelSlug)->firstOrFail();

Room::where('hotel_id', $hotel->id)->get();

     }
     


    public function destroy(RoomType $roomType)
    {
        $roomType->delete();
        return redirect()->route('room-types.index')->with('success', 'تم حذف النوع');
    }
}
