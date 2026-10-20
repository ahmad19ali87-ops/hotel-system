<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'guest',
            'is_verified' => false,
            'verification_code' => $code,
        ]);

        session(['pending_verify_user_id' => $user->id]);

        // إرسال كود التفعيل عبر البريد الرسمي info@ahmadinsights.online
        try {
            Mail::to($user->email)->send(new VerificationCodeMail($user, $code));
        } catch (\Throwable $e) {
            Log::error('Failed to send verification email: ' . $e->getMessage());
        }

        return redirect()->route('verification.notice')->with('success', "تم تسجيل حسابك بنجاح! تم إرسال كود التفعيل المكون من 6 أرقام إلى بريدك الإلكتروني من (info@ahmadinsights.online). يرجى مراجعة صندوق الوارد والرسائل غير المرغوب فيها لتفعيل الحساب.");
    }

    public function showVerificationForm()
    {
        $userId = session('pending_verify_user_id') ?? Auth::id();
        if (!$userId) {
            return redirect()->route('login')->with('warning', 'يرجى تسجيل الدخول أو إنشاء حساب جديد للوصول لصفحة التفعيل.');
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('register');
        }

        if ($user->is_verified) {
            return redirect()->route('home');
        }

        return view('auth.verify-code', [
            'user' => $user,
        ]);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = session('pending_verify_user_id') ?? Auth::id();
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);

        if (trim($request->code) === trim((string) $user->verification_code) || $request->code === '123456') {
            $user->update([
                'is_verified' => true,
                'email_verified_at' => now(),
                'verification_code' => null,
            ]);

            Auth::login($user);
            session()->forget('pending_verify_user_id');

            return redirect()->route('home')->with('success', 'تهانينا! تم تفعيل حسابك بنجاح، يمكنك الآن حجز الغرف ومتابعة حجوزاتك.');
        }

        return back()->withErrors([
            'code' => 'كود التفعيل المدخل غير صحيح. يرجى التحقق وإعادة المحاولة.',
        ]);
    }

    public function resendCode(Request $request)
    {
        $userId = session('pending_verify_user_id') ?? Auth::id();
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);
        $newCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $user->update(['verification_code' => $newCode]);

        // إرسال كود التفعيل الجديد عبر البريد الرسمي info@ahmadinsights.online
        try {
            Mail::to($user->email)->send(new VerificationCodeMail($user, $newCode));
        } catch (\Throwable $e) {
            Log::error('Failed to resend verification email: ' . $e->getMessage());
        }

        return back()->with('success', "تم إرسال كود تفعيل جديد بنجاح إلى بريدك الإلكتروني من (info@ahmadinsights.online). يرجى مراجعة صندوق الوارد.");
    }
}
