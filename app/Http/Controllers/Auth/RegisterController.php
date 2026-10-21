<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\BlockedIp;
use App\Models\SecurityLog;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CaptchaService;
use App\Services\NotificationService;
use App\Services\ThreatAutoResponseEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        $captcha = CaptchaService::generate();
        return view('auth.register', compact('captcha'));
    }

    /**
     * Refresh math captcha via AJAX.
     */
    public function refreshCaptcha()
    {
        $captcha = CaptchaService::generate();
        return response()->json([
            'status' => 'success',
            'question' => $captcha['question'],
            'hint' => $captcha['hint'],
        ]);
    }

    public function register(Request $request)
    {
        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];

        // Require Captcha for web registration (unless running automated test suites without captcha input)
        if (!app()->environment('testing') || $request->has('captcha_answer')) {
            $rules['captcha_answer'] = ['required', 'string'];
        }

        $validated = $request->validate($rules, [
            'captcha_answer.required' => 'يرجى حل مسألة التحقق البشري (Captcha) للتأكد من أنك لست روبوت.',
        ]);

        if ($request->has('captcha_answer') && !CaptchaService::verify($request->captcha_answer)) {
            SecurityLog::record('failed_captcha', 'warning', "Failed captcha attempt during registration: {$request->email}", $request);
            return back()->withInput($request->except('password', 'password_confirmation', 'captcha_answer'))->withErrors([
                'captcha_answer' => 'إجابة مسألة التحقق (Captcha) غير صحيحة، يرجى المحاولة مرة أخرى.',
            ]);
        }

        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'guest',
            'is_verified' => false,
            'verification_code' => $code,
            'verification_code_sent_at' => now(),
            'verification_attempts' => 0,
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

        // Anti-Brute Force Protection: Check if account is locked
        if ($user->isLocked()) {
            $diff = $user->locked_until->diffForHumans();
            return back()->withErrors([
                'code' => "تم قفل الحساب مؤقتاً لمدة ساعة لحمايته من هجمات التخمين (Anti-Brute Force). ينتهي القفل بعد {$diff}.",
            ]);
        }

        // Anti-Brute Force Protection: Lock after 5 failed attempts
        if ($user->verification_attempts >= 5) {
            ThreatAutoResponseEngine::handleBruteForce($user->email, $request->ip() ?: '0.0.0.0', $request);
            return back()->withErrors([
                'code' => 'تم تجاوز الحد الأقصى للمحاولات (5 محاولات). تم قفل الحساب تلقائياً لمدة ساعة لحمايته.',
            ]);
        }

        // Expiration check: Codes expire after 10 minutes
        if ($user->verification_code_sent_at && $user->verification_code_sent_at->diffInMinutes(now()) > 10) {
            return back()->withErrors([
                'code' => 'انتهت صلاحية رمز التفعيل (صلاحية الرمز 10 دقائق). يرجى الضغط على زر إعادة إرسال الكود أدناه.',
            ]);
        }

        $storedCode = (string) $user->verification_code;
        $inputCode = trim((string) $request->code);

        // Constant-time check: No '123456' backdoor
        if ($storedCode !== '' && hash_equals($storedCode, $inputCode)) {
            $user->update([
                'is_verified' => true,
                'email_verified_at' => now(),
                'verification_code' => null,
                'verification_attempts' => 0,
            ]);

            try {
                $welcomeEnabled = SiteSetting::get('app_welcome_enabled', '1') !== '0';
                if ($welcomeEnabled) {
                    $welcomeTitle = SiteSetting::get('app_welcome_title', 'أهلاً بك في بوابة حجز الفنادق الذكية 🎉');
                    $welcomeBody = SiteSetting::get('app_welcome_message', 'يسعدنا انضمامك إلينا! استمتع بأفضل العروض الحصرية وإقامة مميزة في أرقى الفنادق.');
                    NotificationService::sendToUser(
                        $user->id,
                        $welcomeTitle,
                        $welcomeBody,
                        'welcome',
                        '🎉',
                        route('home')
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('Welcome notification error (web register): ' . $e->getMessage());
            }

            Auth::login($user);
            session()->forget('pending_verify_user_id');

            return redirect()->route('home')->with('success', 'تهانينا! تم تفعيل حسابك بنجاح، يمكنك الآن حجز الغرف ومتابعة حجوزاتك.');
        }

        $user->increment('verification_attempts');
        $remaining = max(0, 5 - $user->verification_attempts);

        SecurityLog::record(
            'failed_web_verification_code',
            'warning',
            "Failed web code attempt ({$user->verification_attempts}/5) for {$user->email}",
            $request
        );

        if ($remaining === 0) {
            ThreatAutoResponseEngine::handleBruteForce($user->email, $request->ip() ?: '0.0.0.0', $request);
            return back()->withErrors([
                'code' => 'تم تجاوز الحد الأقصى للمحاولات (5 محاولات). تم قفل الحساب تلقائياً لمدة ساعة لحمايته.',
            ]);
        }

        return back()->withErrors([
            'code' => "كود التفعيل المدخل غير صحيح. المحاولات المتبقية: {$remaining}.",
        ]);
    }

    public function resendCode(Request $request)
    {
        $userId = session('pending_verify_user_id') ?? Auth::id();
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);

        // Anti-Spam: Throttle resending to once every 60 seconds
        if ($user->verification_code_sent_at && $user->verification_code_sent_at->diffInSeconds(now()) < 60) {
            return back()->with('warning', 'يرجى الانتظار دقيقة واحدة قبل طلب إعادة إرسال كود تفعيل جديد منعاً للسبام.');
        }

        $newCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $user->update([
            'verification_code' => $newCode,
            'verification_code_sent_at' => now(),
            'verification_attempts' => 0,
        ]);

        // إرسال كود التفعيل الجديد عبر البريد الرسمي info@ahmadinsights.online
        try {
            Mail::to($user->email)->send(new VerificationCodeMail($user, $newCode));
        } catch (\Throwable $e) {
            Log::error('Failed to resend verification email: ' . $e->getMessage());
        }

        return back()->with('success', "تم إرسال كود تفعيل جديد بنجاح إلى بريدك الإلكتروني من (info@ahmadinsights.online). يرجى مراجعة صندوق الوارد.");
    }
}
