<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Offer;
use App\Models\Hotel;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OfferController extends Controller
{
    private function hotelId(): int
    {
        return (int) Auth::user()->hotel_id;
    }

    public function index()
    {
        $offers = Offer::where('hotel_id', $this->hotelId())
            ->latest()
            ->paginate(15);

        return view('admin.offers.index', compact('offers'));
    }

    public function create()
    {
        return view('admin.offers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'discount_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'special_price' => ['nullable', 'numeric', 'min:0'],
            'special_price_usd' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $imagePath = $request->file('image')->store('offers', 'public');
        }

        $offer = Offer::create([
            'hotel_id' => $this->hotelId(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'discount_percentage' => $validated['discount_percentage'] ?? 0,
            'special_price' => $validated['special_price'] ?? null,
            'special_price_usd' => $validated['special_price_usd'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'image' => $imagePath,
            'is_active' => true,
        ]);

        // إرسال إشعار ترويجي لجميع الزبائن المسجلين
        $hotel = Hotel::find($this->hotelId());
        $hotelName = $hotel?->name ?? 'فندق متميز';
        $discountText = (!empty($validated['discount_percentage']) && $validated['discount_percentage'] > 0)
            ? " بخصم {$validated['discount_percentage']}%"
            : '';

        NotificationService::sendToGuests(
            "عرض ترويجي مميز جديد من {$hotelName}! 🎉",
            "أطلق {$hotelName} عرضاً ترويجياً جديداً: '{$validated['title']}'{$discountText}. تصفح العروض الحالية الآن واحجز إقامتك بأفضل الأسعار.",
            'promo_offer',
            'fas fa-tags',
            route('offers.index'),
            $this->hotelId(),
            ['offer_id' => $offer->id]
        );

        return redirect()->route('admin.offers.index')->with('success', 'تم إنشاء العرض الترويجي وإتاحته في قسم العروض الحالية وإشعار النزلاء بنجاح.');
    }

    public function edit(Offer $offer)
    {
        abort_unless($offer->hotel_id === $this->hotelId(), 403);
        return view('admin.offers.edit', compact('offer'));
    }

    public function update(Request $request, Offer $offer)
    {
        abort_unless($offer->hotel_id === $this->hotelId(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'discount_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'special_price' => ['nullable', 'numeric', 'min:0'],
            'special_price_usd' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'image' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            if ($offer->image) {
                Storage::disk('public')->delete($offer->image);
            }
            $offer->image = $request->file('image')->store('offers', 'public');
        }

        $offer->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'discount_percentage' => $validated['discount_percentage'] ?? 0,
            'special_price' => $validated['special_price'] ?? null,
            'special_price_usd' => $validated['special_price_usd'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $offer->is_active,
        ]);

        return redirect()->route('admin.offers.index')->with('success', 'تم تحديث العرض الترويجي بنجاح.');
    }

    public function toggle(Offer $offer)
    {
        abort_unless($offer->hotel_id === $this->hotelId(), 403);
        $offer->is_active = !$offer->is_active;
        $offer->save();

        return back()->with('success', 'تم تحديث حالة العرض بنجاح.');
    }

    public function destroy(Offer $offer)
    {
        abort_unless($offer->hotel_id === $this->hotelId(), 403);
        if ($offer->image) {
            Storage::disk('public')->delete($offer->image);
        }
        $offer->delete();

        return redirect()->route('admin.offers.index')->with('success', 'تم حذف العرض بنجاح.');
    }
}

