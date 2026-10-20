<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Hotel;
use App\Services\FileSecurityService;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::with('type')->where('hotel_id', auth()->user()->hotel_id)->get();
        return view('admin.rooms.index', compact('rooms'));
    }

   public function create()
{
    $types = RoomType::where('hotel_id', auth()->user()->hotel_id)
                     ->orderBy('name')
                     ->get();

    return view('admin.rooms.create', compact('types'));
}


    public function store(Request $request)
    {
        $request->validate([
            'room_number' => 'required|unique:rooms',
            'room_type_id' => 'required',
            'price' => 'required|numeric',
            'beds' => 'required|numeric',
            'description' => 'nullable',
            'images.*' => 'image|mimes:jpg,jpeg,png'
        ]);

        // رفع الصور
        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->images as $img) {

                $images[] = FileSecurityService::validateImage($img);

            }
        }

        Room::create([
            'hotel_id' => auth()->user()->hotel_id,
            'room_number' => $request->room_number,
            'room_type_id' => $request->room_type_id,
            'price' => $request->price,
            'beds' => $request->beds,
            'description' => $request->description,
            'images' => json_encode($images)
        ]);

        return redirect()->route('rooms.index')->with('success', 'تم إضافة الغرفة بنجاح');
    }

    public function edit(Room $room)
    {
        $types = RoomType::all();
        return view('admin.rooms.edit', compact('room', 'types'));
    }

    public function update(Request $request, Room $room)
    {
        $request->validate([
            'room_number' => 'required',
            'room_type_id' => 'required',
            'price' => 'required|numeric',
            'beds' => 'required|numeric',
            'description' => 'nullable',
            'images.*' => 'image|mimes:jpg,jpeg,png'
        ]);

        // رفع صور جديدة
        $images = json_decode($room->images, true);

        if ($request->hasFile('images')) {
            foreach ($request->images as $img) {

                $images[] = FileSecurityService::validateImage($img);

            }
        }

        $room->update([
            'room_number' => $request->room_number,
            'room_type_id' => $request->room_type_id,
            'price' => $request->price,
            'beds' => $request->beds,
            'description' => $request->description,
            'images' => json_encode($images)
        ]);

        return redirect()->route('rooms.index')->with('success', 'تم تعديل الغرفة بنجاح');
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return redirect()->route('rooms.index')->with('success', 'تم حذف الغرفة');
    }
}
