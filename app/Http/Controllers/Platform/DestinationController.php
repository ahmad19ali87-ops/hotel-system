<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DestinationController extends Controller
{
    private function checkPermission(): void
    {
        abort_unless(auth()->user()?->hasPermission('appearance'), 403, 'غير مصرح لك بإدارة الوجهات والمظهر.');
    }

    public function index()
    {
        $this->checkPermission();
        $destinations = Destination::orderBy('sort_order')->latest('id')->get();
        return view('platform.destinations.index', compact('destinations'));
    }

    public function create()
    {
        $this->checkPermission();
        return view('platform.destinations.create');
    }

    public function store(Request $request)
    {
        $this->checkPermission();
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('destinations', 'public');
        }

        Destination::create([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'link' => $validated['link'] ?? null,
            'icon' => $validated['icon'] ?? '🏛️',
            'image' => $imagePath,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('platform.destinations.index')->with('status', 'تمت إضافة بطاقة الوجهة السياحية بنجاح.');
    }

    public function edit(Destination $destination)
    {
        $this->checkPermission();
        return view('platform.destinations.edit', compact('destination'));
    }

    public function update(Request $request, Destination $destination)
    {
        $this->checkPermission();
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $imagePath = $destination->image;
        if ($request->has('delete_image') && $imagePath) {
            Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }

        if ($request->hasFile('image')) {
            if ($destination->image) {
                Storage::disk('public')->delete($destination->image);
            }
            $imagePath = $request->file('image')->store('destinations', 'public');
        }

        $destination->update([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'link' => $validated['link'] ?? null,
            'icon' => $validated['icon'] ?? '🏛️',
            'image' => $imagePath,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('platform.destinations.index')->with('status', 'تم تحديث بطاقة الوجهة السياحية بنجاح.');
    }

    public function destroy(Destination $destination)
    {
        $this->checkPermission();
        if ($destination->image) {
            Storage::disk('public')->delete($destination->image);
        }
        $destination->delete();

        return redirect()->route('platform.destinations.index')->with('status', 'تم حذف الوجهة بنجاح.');
    }
}

