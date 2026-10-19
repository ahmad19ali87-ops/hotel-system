<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GuestController extends Controller
{
    public function index(Request $request)
    {
        $hotelId = auth()->user()->hotel_id;

        $query = Guest::where('hotel_id', $hotelId)
            ->withCount('bookings')
            ->withCount(['messages as unread_messages_count' => function ($q) {
                $q->where('is_read', false);
            }]);

        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function ($sub) use ($term) {
                $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('father_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('id_number', 'like', "%{$term}%")
                    ->orWhere('national_id', 'like', "%{$term}%")
                    ->orWhere('nationality', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $guests = $query->latest('id')->paginate(20)->withQueryString();

        return view('admin.guests.index', compact('guests'));
    }

    public function create()
    {
        abort_if(auth()->user()->role === 'hotel_owner', 403, 'صلاحيات مالك الفندق مخصصة للمعاينة والاطلاع فقط.');
        return view('admin.guests.create');
    }

    public function store(Request $request)
    {
        abort_if(auth()->user()->role === 'hotel_owner', 403, 'صلاحيات مالك الفندق مخصصة للمعاينة والاطلاع فقط.');
        $hotelId = auth()->user()->hotel_id;

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'id_type' => ['nullable', 'string', 'max:50'],
            'id_number' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:jpeg,png,jpg,webp,pdf', 'max:8192'],
            'scanner_images' => ['nullable', 'array'],
            'scanner_images.*' => ['nullable', 'string'],
        ]);

        // تركيب الاسم الكامل
        $fullName = trim(($validated['first_name'] ?? '') . ' ' . ($validated['father_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));
        if (empty($fullName)) {
            $fullName = $validated['name'] ?? 'نزيل غير مسمى';
        }

        $idNumber = $validated['id_number'] ?? null;

        // معالجة المرفقات
        $savedAttachments = $this->handleAttachments($request);

        $guest = Guest::create([
            'hotel_id' => $hotelId,
            'name' => $fullName,
            'first_name' => $validated['first_name'] ?? null,
            'father_name' => $validated['father_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'nationality' => $validated['nationality'] ?? 'سوري',
            'birth_date' => $validated['birth_date'] ?? null,
            'id_type' => $validated['id_type'] ?? 'national_id',
            'id_number' => $idNumber,
            'national_id' => $idNumber,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'attachments' => !empty($savedAttachments) ? json_encode($savedAttachments, JSON_UNESCAPED_SLASHES) : null,
        ]);

        return redirect()->route('guests.index')->with('success', 'تم تسجيل النزيل وحفظ بياناته ووثائقه بنجاح.');
    }

    public function edit(Guest $guest)
    {
        abort_if(auth()->user()->role === 'hotel_owner', 403, 'صلاحيات مالك الفندق مخصصة للمعاينة والاطلاع فقط.');
        $this->ensureGuestBelongsToHotel($guest);
        return view('admin.guests.edit', compact('guest'));
    }

    public function update(Request $request, Guest $guest)
    {
        abort_if(auth()->user()->role === 'hotel_owner', 403, 'صلاحيات مالك الفندق مخصصة للمعاينة والاطلاع فقط.');
        $this->ensureGuestBelongsToHotel($guest);

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'id_type' => ['nullable', 'string', 'max:50'],
            'id_number' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:jpeg,png,jpg,webp,pdf', 'max:8192'],
            'deleted_attachments' => ['nullable', 'array'],
            'deleted_attachments.*' => ['nullable', 'string'],
            'scanner_images' => ['nullable', 'array'],
            'scanner_images.*' => ['nullable', 'string'],
        ]);

        $fullName = trim(($validated['first_name'] ?? '') . ' ' . ($validated['father_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));
        if (empty($fullName)) {
            $fullName = $validated['name'] ?? $guest->name;
        }

        $idNumber = $validated['id_number'] ?? $guest->id_number;

        // إدارة المرفقات الحالية
        $existingAttachments = $guest->attachments ? (json_decode($guest->attachments, true) ?: []) : [];

        // حذف المرفقات المحددة
        if ($request->has('deleted_attachments') && is_array($request->deleted_attachments)) {
            foreach ($request->deleted_attachments as $delPath) {
                $idx = array_search($delPath, $existingAttachments);
                if ($idx !== false) {
                    unset($existingAttachments[$idx]);
                    Storage::disk('public')->delete($delPath);
                }
            }
            $existingAttachments = array_values($existingAttachments);
        }

        // إضافة مرفقات جديدة (ملفات أو سكنر)
        $newAttachments = $this->handleAttachments($request);
        $allAttachments = array_merge($existingAttachments, $newAttachments);

        $guest->update([
            'name' => $fullName,
            'first_name' => $validated['first_name'] ?? $guest->first_name,
            'father_name' => $validated['father_name'] ?? $guest->father_name,
            'last_name' => $validated['last_name'] ?? $guest->last_name,
            'nationality' => $validated['nationality'] ?? $guest->nationality,
            'birth_date' => $validated['birth_date'] ?? $guest->birth_date,
            'id_type' => $validated['id_type'] ?? $guest->id_type,
            'id_number' => $idNumber,
            'national_id' => $idNumber,
            'phone' => $validated['phone'] ?? $guest->phone,
            'email' => $validated['email'] ?? $guest->email,
            'address' => $validated['address'] ?? $guest->address,
            'notes' => $validated['notes'] ?? $guest->notes,
            'attachments' => !empty($allAttachments) ? json_encode($allAttachments, JSON_UNESCAPED_SLASHES) : null,
        ]);

        // مزامنة التعديلات مع حساب المستخدم المرتبط (Two-Way Sync)
        $userId = $guest->user_id;
        if (!$userId && $guest->email) {
            $matchedUser = \App\Models\User::where('email', $guest->email)->first();
            if ($matchedUser) {
                $userId = $matchedUser->id;
                $guest->update(['user_id' => $userId]);
            }
        }

        if ($userId && ($linkedUser = \App\Models\User::find($userId))) {
            $linkedUser->update([
                'name' => $fullName,
                'first_name' => $guest->first_name,
                'father_name' => $guest->father_name,
                'last_name' => $guest->last_name,
                'nationality' => $guest->nationality,
                'birth_date' => $guest->birth_date,
                'id_type' => $guest->id_type,
                'id_number' => $guest->id_number,
                'national_id' => $guest->national_id,
                'phone' => $guest->phone ?: $linkedUser->phone,
                'address' => $guest->address ?: $linkedUser->address,
                'attachments' => $guest->attachments,
            ]);
        }

        return redirect()->route('guests.index')->with('success', 'تم تحديث بيانات ووثائق النزيل بنجاح ومزامنتها مع حسابه.');
    }

    public function destroy(Guest $guest)
    {
        abort_if(auth()->user()->role === 'hotel_owner', 403, 'صلاحيات مالك الفندق مخصصة للمعاينة والاطلاع فقط.');
        $this->ensureGuestBelongsToHotel($guest);

        // حذف ملفات الوثائق المرتبطة
        if ($guest->attachments) {
            $attachments = json_decode($guest->attachments, true) ?: [];
            foreach ($attachments as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $guest->delete();
        return redirect()->route('guests.index')->with('success', 'تم حذف النزيل وسجلاته بنجاح.');
    }

    public function history(Guest $guest)
    {
        $this->ensureGuestBelongsToHotel($guest);
        $bookings = $guest->bookings()->with('room')->latest('created_at')->get();

        return view('admin.guests.history', compact('guest', 'bookings'));
    }

    public function printCard(Guest $guest)
    {
        $this->ensureGuestBelongsToHotel($guest);
        $hotel = auth()->user()->hotel;
        return view('admin.guests.print', compact('guest', 'hotel'));
    }

    private function handleAttachments(Request $request): array
    {
        $saved = [];

        // معالجة الملفات المرفوعة
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $saved[] = $file->store('guests/attachments', 'public');
            }
        }

        // معالجة لقطات السكنر / الكاميرا المباشرة (Base64)
        if ($request->has('scanner_images') && is_array($request->scanner_images)) {
            foreach ($request->scanner_images as $base64) {
                if (!empty($base64) && str_starts_with($base64, 'data:image/')) {
                    @list($type, $data) = explode(';', $base64);
                    @list(, $data) = explode(',', $data);
                    if ($data) {
                        $binary = base64_decode($data);
                        $filename = 'guests/attachments/scan_' . Str::random(20) . '.jpg';
                        Storage::disk('public')->put($filename, $binary);
                        $saved[] = $filename;
                    }
                }
            }
        }

        return $saved;
    }

    private function ensureGuestBelongsToHotel(Guest $guest): void
    {
        abort_unless((int) $guest->hotel_id === (int) auth()->user()->hotel_id, 404);
    }
}
