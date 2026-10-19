<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RoomType;
use Illuminate\Validation\Rule;

class RoomTypeController extends Controller
{
    public function index()
    {
        $types = RoomType::where('hotel_id', auth()->user()->hotel_id)->orderBy('name')->get();
        return view('admin.room-types.index', compact('types'));
    }

    public function create()
    {
        return view('admin.room-types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('room_types')->where('hotel_id', auth()->user()->hotel_id)],
            'description' => ['nullable', 'string'],
        ]);

        RoomType::create([
            'name' => $request->name,
            'description' => $request->description,
            'hotel_id' => auth()->user()->hotel_id,
        ]);

        return redirect()->route('room-types.index')->with('success', 'تم إضافة نوع الغرفة بنجاح');
    }

    public function edit(RoomType $roomType)
    {
        abort_unless((int) $roomType->hotel_id === (int) auth()->user()->hotel_id, 404);
        return view('admin.room-types.edit', compact('roomType'));
    }

    public function update(Request $request, RoomType $roomType)
    {
        abort_unless((int) $roomType->hotel_id === (int) auth()->user()->hotel_id, 404);
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('room_types')->where('hotel_id', auth()->user()->hotel_id)->ignore($roomType->id)],
            'description' => ['nullable', 'string'],
        ]);

        $roomType->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return redirect()->route('room-types.index')->with('success', 'تم تعديل نوع الغرفة بنجاح');
    }

    public function destroy(RoomType $roomType)
    {
        abort_unless((int) $roomType->hotel_id === (int) auth()->user()->hotel_id, 404);
        $roomType->delete();
        return redirect()->route('room-types.index')->with('success', 'تم حذف نوع الغرفة');
    }
}
