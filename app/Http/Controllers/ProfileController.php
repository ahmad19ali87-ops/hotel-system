<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'remove_avatar' => ['nullable', 'boolean'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.current_password' => 'كلمة المرور الحالية غير صحيحة.',
            'password.confirmed' => 'تأكيد كلمة المرور الجديدة غير متطابق.',
            'password.min' => 'يجب ألا تقل كلمة المرور الجديدة عن 8 أحرف.',
        ]);

        $user->name = $validated['name'];
        $user->phone = $validated['phone'] ?? null;
        $user->job_title = $validated['job_title'] ?? null;
        $user->bio = $validated['bio'] ?? null;

        // إزالة الصورة الشخصية
        if ($request->boolean('remove_avatar') && $user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
        }

        // رفع وتحديث الصورة الشخصية
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        // تغيير كلمة المرور إن تم إدخالها
        if ($request->filled('password')) {
            $user->password = $validated['password'];
        }

        // التأكيد الصارم: لا يمكن تغيير الصلاحيات أو الفندق أبداً
        $user->save();

        return redirect()->route('profile.edit')->with('success', 'تم حفظ وتحديث ملفك الشخصي بنجاح.');
    }
}

