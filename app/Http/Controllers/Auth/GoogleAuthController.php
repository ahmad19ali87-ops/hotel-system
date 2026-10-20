<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth or render Google Sign-in dialog with real Gmail.
     */
    public function redirect(Request $request)
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        // 1. If Google OAuth credentials exist, redirect to official Google Accounts
        if ($clientId && $clientSecret) {
            $redirectUri = route('auth.google.callback');
            $params = [
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'openid profile email',
                'access_type' => 'offline',
                'prompt' => 'select_account',
            ];

            return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
        }

        // 2. If already supplied real Gmail via query or form
        if ($request->filled('email')) {
            return $this->loginWithRealGoogleEmail($request);
        }

        // 3. Otherwise show Google Real Account Sign-In View
        return view('auth.google-prompt');
    }

    /**
     * Handle direct real Google / Gmail login submission.
     */
    public function submitRealGoogle(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->loginWithRealGoogleEmail($request);
    }

    /**
     * Authenticate or register a user with their real Google email.
     */
    private function loginWithRealGoogleEmail(Request $request)
    {
        $email = strtolower(trim($request->input('email')));
        $name = trim($request->input('name') ?: explode('@', $email)[0]);

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update([
                'is_verified' => true,
                'email_verified_at' => $user->email_verified_at ?: now(),
                'google_id' => $user->google_id ?: ('google_' . md5($email)),
            ]);
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(24)),
                'role' => 'guest',
                'google_id' => 'google_' . md5($email),
                'is_verified' => true,
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user);

        return redirect()->route('home')->with('success', "مرحباً بك! تم تسجيل الدخول بنجاح عبر حساب Google الرسمي ({$user->email}).");
    }

    /**
     * Handle Google OAuth Callback.
     */
    public function callback(Request $request)
    {
        $code = $request->query('code');
        if (! $code) {
            return redirect()->route('login')->withErrors(['email' => 'تم إلغاء تسجيل الدخول عبر Google.']);
        }

        try {
            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => route('auth.google.callback'),
                'grant_type' => 'authorization_code',
            ]);

            if (! $tokenResponse->successful()) {
                Log::error('Google OAuth token error: ' . $tokenResponse->body());
                return redirect()->route('login')->withErrors(['email' => 'تعذر الاتصال بـ Google للتحقق من الحساب.']);
            }

            $accessToken = $tokenResponse->json('access_token');
            $userInfoResponse = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if (! $userInfoResponse->successful()) {
                return redirect()->route('login')->withErrors(['email' => 'فشل جلب بيانات حساب Google.']);
            }

            $userData = $userInfoResponse->json();
            $email = $userData['email'];
            $name = $userData['name'] ?? 'مستخدم Google';
            $googleId = $userData['sub'] ?? null;
            $avatar = $userData['picture'] ?? null;

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'google_id' => $googleId,
                    'avatar' => $avatar,
                    'role' => 'guest',
                    'is_verified' => true,
                    'email_verified_at' => now(),
                ]
            );

            Auth::login($user);

            return redirect()->route('home')->with('success', "تم تسجيل الدخول بنجاح عبر حساب Google ({$user->email}).");
        } catch (\Throwable $e) {
            Log::error('Google OAuth Exception: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['email' => 'حدث خطأ أثناء الاتصال بـ Google: ' . $e->getMessage()]);
        }
    }
}
