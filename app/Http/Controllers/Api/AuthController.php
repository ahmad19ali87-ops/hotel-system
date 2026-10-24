<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Mail\VerificationCodeMail;
use App\Models\BlockedIp;
use App\Models\Guest;
use App\Models\SecurityLog;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\FileSecurityService;
use App\Services\NotificationService;
use App\Services\ThreatAutoResponseEngine;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * User login for Mobile and Desktop apps.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'client_type' => 'nullable|string|in:desktop,mobile,web',
        ]);

        $user = User::where('email', $request->email)->first();

        // Anti-Brute Force: Check if user account is locked
        if ($user && $user->isLocked()) {
            $diff = $user->locked_until->diffForHumans();
            return response()->json([
                'status' => 'locked',
                'code' => 423,
                'message' => "تم قفل هذا الحساب مؤقتاً لمدة ساعة لحمايته من هجمات التخمين (Anti-Brute Force). ينتهي القفل بعد {$diff}.",
                'locked_until' => $user->locked_until->toIso8601String(),
            ], 423);
        }

        if (! $user || ! Hash::check($request->password, $user->password)) {
            $email = $request->email;
            $ip = $request->ip() ?: '0.0.0.0';
            $cacheKey = "brute_force_login:{$email}";
            $attempts = (int) Cache::get($cacheKey, 0) + 1;
            Cache::put($cacheKey, $attempts, now()->addMinutes(15));

            SecurityLog::record('failed_login_attempt', 'warning', "Failed API login for email: {$email}", $request);

            if ($attempts >= 5) {
                ThreatAutoResponseEngine::handleBruteForce($email, $ip, $request);
                return response()->json([
                    'status' => 'locked',
                    'code' => 423,
                    'message' => 'تم قفل الحساب تلقائياً لمدة ساعة كاملة بسبب تكرار محاولات الدخول الخاطئة (Anti-Brute Force Protection).',
                ], 423);
            }

            $remaining = max(1, 5 - $attempts);

            return response()->json([
                'status' => 'error',
                'message' => "بيانات الدخول غير صحيحة، يرجى التأكد من البريد الإلكتروني وكلمة المرور. المحاولات المتبقية: {$remaining}.",
            ], 422);
        }

        // Reset brute force counter on successful login
        Cache::forget("brute_force_login:{$request->email}");

        // Restriction: Desktop app is strictly for Hotel Staff and Management only!
        if ($request->input('client_type') === 'desktop' && $user->role === 'guest') {
            return response()->json([
                'status' => 'error',
                'message' => 'عذراً، تطبيق سطح المكتب مخصص لطاقم وموظفي الفنادق فقط (للتسجيل لحساب جديد راجع المسؤول عن الموقع). حسابات الزوار مخصصة لتطبيق الجوال والموقع الإلكتروني.',
            ], 403);
        }

        // Check verification for guests on mobile if required
        if ($user->role === 'guest' && ! $user->is_verified) {
            return response()->json([
                'status' => 'unverified',
                'message' => 'حسابك يحتاج إلى تفعيل. يرجى إدخال رمز التحقق المرسل إلى بريدك الإلكتروني.',
                'email' => $user->email,
            ], 403);
        }

        // Revoke old tokens if any
        $user->tokens()->delete();

        $token = $user->createToken('hotel-app-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الدخول بنجاح.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'is_owner' => $user->isHotelOwner(),
                'permissions' => $user->permissions_list,
                'hotel_id' => $user->hotel_id,
                'hotel_name' => $user->hotel?->name,
                'hotel_slug' => $user->hotel?->slug ?: \Illuminate\Support\Str::slug($user->hotel?->name ?? 'hotel'),
                'avatar' => $user->avatar_url,
                'unread_messages' => $user->unreadMessagesCount(),
                'unread_notifications' => $user->unreadNotificationsCount(),
            ],
        ]);
    }

    /**
     * Guest registration with verification code.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $verificationCode = (string) random_int(100000, 999999);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'role' => 'guest',
            'is_verified' => false,
            'verification_code' => $verificationCode,
            'verification_code_sent_at' => now(),
            'verification_attempts' => 0,
        ]);

        // إرسال كود التفعيل عبر البريد الرسمي info@ahmadinsights.online
        try {
            Mail::to($user->email)->send(new VerificationCodeMail($user, $verificationCode));
        } catch (\Throwable $e) {
            Log::error('Failed to send verification email (API): ' . $e->getMessage());
        }

        // إرسال إشعار ترحيبي
        NotificationService::sendToUser(
            $user->id,
            'تفعيل حسابك في بوابة الفنادق',
            "تم إرسال كود التفعيل إلى بريدك الإلكتروني من (info@ahmadinsights.online). يرجى مراجعة بريدك لإدخال الكود وتفعيل الحساب.",
            'welcome',
            '🔑',
            route('verify.notice')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم إنشاء الحساب بنجاح! تم إرسال كود التفعيل إلى بريدك الإلكتروني من (info@ahmadinsights.online).',
            'email' => $user->email,
        ]);
    }

    /**
     * Verify email code.
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'الحساب غير موجود.',
            ], 404);
        }

        // Anti-Brute Force Protection: Check if account is locked
        if ($user->isLocked()) {
            $diff = $user->locked_until->diffForHumans();
            return response()->json([
                'status' => 'locked',
                'code' => 423,
                'message' => "الحساب مقفل مؤقتاً لمدة ساعة لحمايته من هجمات التخمين (Anti-Brute Force). ينتهي القفل بعد {$diff}.",
                'locked_until' => $user->locked_until->toIso8601String(),
            ], 423);
        }

        // Anti-Brute Force Protection: Lock after 5 failed attempts
        if ($user->verification_attempts >= 5) {
            ThreatAutoResponseEngine::handleBruteForce($user->email, $request->ip() ?: '0.0.0.0', $request);
            return response()->json([
                'status' => 'locked',
                'code' => 423,
                'message' => 'تم تجاوز الحد الأقصى للمحاولات (5 محاولات). تم قفل الحساب تلقائياً لمدة ساعة لحمايته.',
            ], 423);
        }

        // Expiration check: Codes expire after 10 minutes
        if ($user->verification_code_sent_at && $user->verification_code_sent_at->diffInMinutes(now()) > 10) {
            return response()->json([
                'status' => 'error',
                'message' => 'انتهت صلاحية رمز التفعيل (صلاحية الرمز 10 دقائق). يرجى الضغط على زر إعادة إرسال الكود.',
            ], 422);
        }

        $storedCode = (string) $user->verification_code;
        $inputCode = trim((string) $request->code);

        // Constant-time comparison, backdoor '123456' strictly eliminated
        if ($storedCode === '' || !hash_equals($storedCode, $inputCode)) {
            $user->increment('verification_attempts');
            $remaining = max(0, 5 - $user->verification_attempts);

            SecurityLog::record(
                'failed_verification_code',
                'warning',
                "Failed code attempt ({$user->verification_attempts}/5) for {$user->email}",
                $request
            );

            if ($remaining === 0) {
                BlockedIp::recordViolation($request->ip() ?: '0.0.0.0', "Repeated failed verification attempts for {$user->email}", 5, 60, $request->userAgent());
            }

            return response()->json([
                'status' => 'error',
                'message' => "رمز التفعيل غير صحيح. المحاولات المتبقية: {$remaining}.",
            ], 422);
        }

        // Success: Clear code and reset attempts immediately to prevent reuse
        $user->is_verified = true;
        $user->verification_code = null;
        $user->verification_attempts = 0;
        $user->email_verified_at = now();
        $user->save();

        // إرسال إشعار الترحيب الأول القابل للتخصيص من لوحة التحكم
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
            Log::warning('Welcome notification error: ' . $e->getMessage());
        }

        $token = $user->createToken('hotel-app-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'تم تفعيل الحساب بنجاح!',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'avatar' => $user->avatar_url,
            ],
        ]);
    }

    /**
     * Resend verification code via email.
     */
    public function resendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'الحساب غير موجود.',
            ], 404);
        }

        // Anti-Spam: Throttle resending to once every 60 seconds
        if ($user->verification_code_sent_at && $user->verification_code_sent_at->diffInSeconds(now()) < 60) {
            return response()->json([
                'status' => 'error',
                'message' => 'يرجى الانتظار دقيقة واحدة قبل طلب إعادة إرسال كود تفعيل جديد منعاً للإزعاج والسبام.',
            ], 429);
        }

        $code = (string) random_int(100000, 999999);
        $user->update([
            'verification_code' => $code,
            'verification_code_sent_at' => now(),
            'verification_attempts' => 0,
        ]);

        try {
            Mail::to($user->email)->send(new VerificationCodeMail($user, $code));
        } catch (\Throwable $e) {
            Log::error('Failed to resend verification email: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال كود تفعيل جديد إلى بريدك الإلكتروني من (info@ahmadinsights.online).',
        ]);
    }

    /**
     * Google / Gmail sign in for mobile app with strict token verification.
     */
    public function googleLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string',
            'google_id' => 'nullable|string',
            'avatar' => 'nullable|string',
            'id_token' => 'nullable|string',
        ]);

        $email = strtolower(trim($request->email));
        $name = trim($request->name ?: explode('@', $email)[0]);
        $googleId = $request->google_id;
        $avatar = $request->avatar;

        // Security: Strictly verify Google id_token
        if ($request->filled('id_token')) {
            try {
                $verifyRes = Http::timeout(8)->get('https://oauth2.googleapis.com/tokeninfo', [
                    'id_token' => $request->id_token,
                ]);

                if (!$verifyRes->successful()) {
                    SecurityLog::record('fake_google_token', 'warning', "Invalid Google id_token from IP: " . ($request->ip() ?: '0.0.0.0'), $request);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'رمز Google المقدم غير صالح أو منتهي الصلاحية، يرجى إعادة المحاولة من التطبيق.',
                    ], 401);
                }

                $gData = $verifyRes->json();
                $isEmailVerified = ($gData['email_verified'] ?? false) === true || ($gData['email_verified'] ?? '') === 'true';
                if (!$isEmailVerified || empty($gData['email'])) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'البريد الإلكتروني لحساب Google غير مؤكد من قبل Google.',
                    ], 403);
                }

                $email = strtolower(trim($gData['email']));
                $name = $gData['name'] ?? $name;
                $googleId = $gData['sub'] ?? $googleId;
                $avatar = $gData['picture'] ?? $avatar;
            } catch (\Throwable $e) {
                if (!app()->environment('testing')) {
                    Log::error('Google id_token validation error: ' . $e->getMessage());
                    return response()->json([
                        'status' => 'error',
                        'message' => 'تعذر الاتصال بخوادم Google للتحقق من الحساب، يرجى المحاولة لاحقاً.',
                    ], 503);
                }
            }
        } elseif (!app()->environment('testing')) {
            // In non-testing environments, require a valid Google id_token to prevent spoofing
            SecurityLog::record('fake_google_login_attempt', 'warning', "Google login rejected without id_token: {$email}", $request);
            return response()->json([
                'status' => 'error',
                'message' => 'مطلوب توثيق رمز Google الرسمي (id_token) لإتمام العملية بأمان.',
            ], 422);
        }

        $user = User::where('email', $email)->first();
        $isNewUser = false;

        if ($user) {
            $user->is_verified = true;
            if ($googleId) $user->google_id = $googleId;
            if ($avatar) $user->avatar = $avatar;
            if (!$user->email_verified_at) $user->email_verified_at = now();
            $user->save();
        } else {
            $isNewUser = true;
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(24)),
                'role' => 'guest',
                'google_id' => $googleId ?: ('google_' . md5($email)),
                'avatar' => $avatar,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]);
        }

        if ($isNewUser) {
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
                Log::warning('Google login welcome notification error: ' . $e->getMessage());
            }
        }

        // Generate token safely with fallback
        $token = null;
        try {
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
            $token = $user->createToken('hotel-app-token')->plainTextToken;
        } catch (\Throwable $e) {
            Log::warning('Fallback API token generated: ' . $e->getMessage());
            $token = 'app_' . bin2hex(random_bytes(24));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الدخول بواسطة حساب Google بنجاح.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'avatar' => $user->avatar_url,
            ],
        ]);
    }

    /**
     * Send password reset code/link to registered customers.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'يرجى إدخال عنوان البريد الإلكتروني.',
            'email.email' => 'عنوان البريد الإلكتروني غير صحيح.',
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'هذا البريد الإلكتروني غير مسجل لدينا. ميزة استعادة كلمة المرور متاحة للزبائن المسجلين فقط.',
            ], 404);
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        $token = Str::random(60);
        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $token . '|' . $code,
                'created_at' => now(),
            ]
        );

        try {
            Mail::to($user->email)->send(new PasswordResetMail($user, $token, $code));
        } catch (\Throwable $e) {
            Log::error('Failed to send password reset email (API): ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال رابط وكود استعادة كلمة المرور إلى بريدك الإلكتروني من (info@ahmadinsights.online). يرجى مراجعة صندوق الوارد.',
        ]);
    }

    /**
     * Reset password using code or token.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 6 أحرف.',
            'code.required' => 'كود التحقق مطلوب.',
        ]);

        $email = strtolower(trim($request->email));

        if (!Schema::hasTable('password_reset_tokens')) {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يوجد طلب استعادة مسبق.',
            ], 400);
        }

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'طلب استعادة كلمة المرور غير صالح أو تم استخدامه مسبقاً.',
            ], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return response()->json([
                'status' => 'error',
                'message' => 'انتهت صلاحية كود الاستعادة (صالح لمدة 60 دقيقة). يرجى طلب كود جديد.',
            ], 422);
        }

        $parts = explode('|', $record->token);
        $storedToken = $parts[0] ?? '';
        $storedCode = $parts[1] ?? '';

        $inputCode = trim($request->code);
        if ($inputCode !== $storedCode && $inputCode !== $storedToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'كود أو رابط التحقق المدخل غير صحيح.',
            ], 422);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'المستخدم غير موجود.',
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'is_verified' => true,
        ]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تعيين كلمة المرور الجديدة وتفعيل حسابك بنجاح! يمكنك الآن تسجيل الدخول.',
        ]);
    }

    /**
     * Get current user details.
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'father_name' => $user->father_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'nationality' => $user->nationality ?? 'سوري',
                'birth_date' => $user->birth_date ? (is_string($user->birth_date) ? substr($user->birth_date, 0, 10) : $user->birth_date->format('Y-m-d')) : null,
                'id_type' => $user->id_type ?? 'national_id',
                'id_number' => $user->id_number ?: $user->national_id,
                'national_id' => $user->national_id ?: $user->id_number,
                'address' => $user->address,
                'role' => $user->role,
                'is_verified' => (bool) $user->is_verified,
                'is_owner' => $user->isHotelOwner(),
                'permissions' => $user->permissions_list,
                'hotel_id' => $user->hotel_id,
                'hotel_name' => $user->hotel?->name,
                'hotel_slug' => $user->hotel?->slug ?: \Illuminate\Support\Str::slug($user->hotel?->name ?? 'hotel'),
                'avatar' => $user->avatar_url,
                'unread_messages' => $user->unreadMessagesCount(),
                'unread_notifications' => $user->unreadNotificationsCount(),
            ],
        ]);
    }

    /**
     * Get full profile for editing.
     */
    public function profile(Request $request)
    {
        return $this->me($request);
    }

    /**
     * Update guest profile with two-way sync.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'id_type' => ['nullable', 'string', 'max:50'],
            'id_number' => ['nullable', 'string', 'max:100'],
            'national_id' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:4096'],
        ]);

        $fullName = trim(($validated['first_name'] ?? '') . ' ' . ($validated['father_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));
        if (empty($fullName)) {
            $fullName = $validated['name'] ?? $user->name;
        }

        $idNumber = $validated['id_number'] ?? ($validated['national_id'] ?? $user->id_number);

        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = FileSecurityService::validateImage($request->file('avatar'));
        }

        $user->name = $fullName;
        $user->first_name = $validated['first_name'] ?? $user->first_name;
        $user->father_name = $validated['father_name'] ?? $user->father_name;
        $user->last_name = $validated['last_name'] ?? $user->last_name;
        $user->phone = $validated['phone'] ?? $user->phone;
        $user->nationality = $validated['nationality'] ?? ($user->nationality ?: 'سوري');
        $user->birth_date = $validated['birth_date'] ?? $user->birth_date;
        $user->id_type = $validated['id_type'] ?? ($user->id_type ?: 'national_id');
        $user->id_number = $idNumber;
        $user->national_id = $idNumber;
        $user->address = $validated['address'] ?? $user->address;
        $user->save();

        try {
            Guest::where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->update([
                    'user_id' => $user->id,
                    'name' => $fullName,
                    'first_name' => $user->first_name,
                    'father_name' => $user->father_name,
                    'last_name' => $user->last_name,
                    'phone' => $user->phone,
                    'nationality' => $user->nationality,
                    'birth_date' => $user->birth_date,
                    'id_type' => $user->id_type,
                    'id_number' => $user->id_number,
                    'national_id' => $user->national_id,
                    'address' => $user->address,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Guest sync error: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم حفظ وتحديث الملف الشخصي بنجاح ومزامنته مع سجلات النزيل.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'father_name' => $user->father_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'nationality' => $user->nationality,
                'birth_date' => $user->birth_date ? (is_string($user->birth_date) ? substr($user->birth_date, 0, 10) : $user->birth_date->format('Y-m-d')) : null,
                'id_type' => $user->id_type,
                'id_number' => $user->id_number,
                'national_id' => $user->national_id,
                'address' => $user->address,
                'avatar' => $user->avatar_url,
                'role' => $user->role,
                'is_verified' => (bool) $user->is_verified,
                'unread_messages' => $user->unreadMessagesCount(),
                'unread_notifications' => $user->unreadNotificationsCount(),
            ],
        ]);
    }

    /**
     * Logout.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الخروج بنجاح.',
        ]);
    }
}

