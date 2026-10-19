<?php

namespace App\Http\Controllers\Admin;

use App\Models\Hotel;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    public function index()
    {
        $hotels = Hotel::all();
        return view('admin.hotels.index', compact('hotels'));
    }

    public function create()
    {
        return view('admin.hotels.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:hotels',
        ]);

        Hotel::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'logo' => $request->logo,
            'stars' => $request->stars,
            'phone' => $request->phone,
            'address' => $request->address,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.hotels.index')->with('success', 'تم إضافة الفندق بنجاح');
    }
}
