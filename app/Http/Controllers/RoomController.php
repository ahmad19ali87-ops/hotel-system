<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomType;
use App\Services\FileSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::where('hotel_id', $this->hotelId())
            ->with(['roomType', 'type'])
            ->orderBy('room_number')
            ->get();

        return view('admin.rooms.index', compact('rooms'));
    }

    public function create()
    {
        $types = RoomType::where('hotel_id', $this->hotelId())->orderBy('name')->get();

        return view('rooms.create', compact('types'));
    }

    public function store(Request $request)
    {
        $hotelId = $this->hotelId();
        $validated = $request->validate([
            'room_number' => ['required', 'string', 'max:255', Rule::unique('rooms')->where('hotel_id', $hotelId)],
            'room_type_id' => ['required', 'integer', Rule::exists('room_types', 'id')->where('hotel_id', $hotelId)],
            'price' => ['required', 'numeric', 'min:0'],
            'price_usd' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['available', 'reserved', 'maintenance', 'cleaning'])],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $room = new Room([
            'room_number' => $validated['room_number'],
            'room_type_id' => $validated['room_type_id'],
            'price' => $validated['price'],
            'price_usd' => $validated['price_usd'] ?? 0,
            'status' => $validated['status'] ?? 'available',
        ]);
        $room->hotel_id = $hotelId;

        if ($request->hasFile('image')) {
            $room->image = FileSecurityService::validateImage($request->file('image'));
        }

        $room->save();

        return redirect()->route('rooms.index')->with('success', 'تم إضافة الغرفة بنجاح');
    }

    public function edit(Room $room)
    {
        abort_unless((int) $room->hotel_id === $this->hotelId(), 404);
        $types = RoomType::where('hotel_id', $this->hotelId())->orderBy('name')->get();

        return view('rooms.edit', compact('room', 'types'));
    }

    public function update(Request $request, Room $room)
    {
        abort_unless((int) $room->hotel_id === $this->hotelId(), 404);
        $hotelId = $this->hotelId();
        $validated = $request->validate([
            'room_number' => ['required', 'string', 'max:255', Rule::unique('rooms')->where('hotel_id', $hotelId)->ignore($room->id)],
            'room_type_id' => ['required', 'integer', Rule::exists('room_types', 'id')->where('hotel_id', $hotelId)],
            'price' => ['required', 'numeric', 'min:0'],
            'price_usd' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['available', 'booked', 'maintenance', 'cleaning'])],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $room->fill($validated);

        if ($request->boolean('remove_image')) {
            if ($room->image) {
                Storage::disk('public')->delete($room->image);
            }
            $room->image = null;
        }

        if ($request->hasFile('image')) {
            if ($room->image) {
                Storage::disk('public')->delete($room->image);
            }
            $room->image = FileSecurityService::validateImage($request->file('image'));
        }
        $room->save();

        return redirect()->route('rooms.index')->with('success', 'تم تعديل بيانات الغرفة بنجاح');
    }

    public function destroy(Room $room)
    {
        abort_unless((int) $room->hotel_id === $this->hotelId(), 404);

        if ($room->image) {
            Storage::disk('public')->delete($room->image);
        }
        $room->delete();

        return redirect()->route('rooms.index')->with('success', 'تم حذف الغرفة بنجاح');
    }

    private function hotelId(): int
    {
        abort_unless(auth()->user()?->hotel_id, 403);

        return (int) auth()->user()->hotel_id;
    }
}