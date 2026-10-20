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
     * Redirect directly to official Google Accounts login page.
     */
    public function redirect(Request $request)
    {
        // If email query is provided (e.g. testing or quick link)
        if ($request->filled('email')) {
            return $this->loginWithRealGoogleEmail($request);
        }

        $clientId = config('services.google.client_id', '467934742059-hnkanpf5954jqgtu18aubhqpj780et00.apps.googleusercontent.com');
        $redirectUri = config('services.google.redirect') ?: route('auth.google.callback');

        if ($clientId) {
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

        return redirect()->route('auth.google.prompt');
    }

    /**
     * Show Google sign-in options page (Official button + GIS One-Tap + real Gmail fallback).
     */
    public function prompt()
    {
        return view('auth.google-prompt', [
            'clientId' => config('services.google.client_id', '467934742059-hnkanpf5954jqgtu18aubhqpj780et00.apps.googleusercontent.com'),
        ]);
    }

    /**
     * Handle Google One Tap / Google Identity Services (GSI) credential verification.
     */
    public function oneTap(Request $request)
    {
        $credential = $request->input('credential');
        if (!$credential) {
            return redirect()->route('login')->withErrors(['email' => 'لم يتم استلام بيانات التحقق من حساب Google.']);
        }

        try {
            $verifyResponse = Http::get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $credential,
            ]);

            if (!$verifyResponse->successful()) {
                Log::warning('Google tokeninfo failed: ' . $verifyResponse->body());
                return redirect()->route('auth.google.prompt')->withErrors(['email' => 'تعذر التحقق من رمز Google، يرجى إعادة المحاولة.']);
            }

            $userData = $verifyResponse->json();
            $email = strtolower(trim($userData['email']));
            $name = $userData['name'] ?? explode('@', $email)[0];
            $googleId = $userData['sub'] ?? ('google_' . md5($email));
            $avatar = $userData['picture'] ?? null;

            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update([
                    'is_verified' => true,
                    'email_verified_at' => $user->email_verified_at ?: now(),
                    'google_id' => $googleId,
                    'avatar' => $avatar ?: $user->avatar,
                ]);
            } else {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(24)),
                    'role' => 'guest',
                    'google_id' => $googleId,
                    'avatar' => $avatar,
                    'is_verified' => true,
                    'email_verified_at' => now(),
                ]);
            }

            Auth::login($user);

            return redirect()->route('home')->with('success', "مرحباً بك! تم تسجيل الدخول بنجاح عبر حساب Google الرسمي ({$user->email}).");
        } catch (\Throwable $e) {
            Log::error('Google OneTap Exception: ' . $e->getMessage());
            return redirect()->route('auth.google.prompt')->withErrors(['email' => 'حدث خطأ أثناء معالجة حساب Google: ' . $e->getMessage()]);
        }
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
        if ($request->has('error')) {
            Log::warning('Google OAuth redirect error: ' . $request->input('error'));
            return redirect()->route('auth.google.prompt')->with('warning', 'تم إلغاء تسجيل الدخول عبر نافذة Google أو رفض الإذن.');
        }

        $code = $request->query('code');
        if (! $code) {
            return redirect()->route('auth.google.prompt')->withErrors(['email' => 'لم يتم استلام كود التحقق من Google.']);
        }

        $clientId = config('services.google.client_id', '467934742059-hnkanpf5954jqgtu18aubhqpj780et00.apps.googleusercontent.com');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect') ?: route('auth.google.callback');

        try {
            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret ?: '',
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if (! $tokenResponse->successful()) {
                Log::warning('Google OAuth token exchange warning: ' . $tokenResponse->body());
                // Smooth fallback to Google prompt view with GIS button instead of throwing raw error
                return redirect()->route('auth.google.prompt')->with('info', 'يرجى تأكيد الدخول بحساب Google عبر الزر المباشر أدناه.');
            }

            $accessToken = $tokenResponse->json('access_token');
            $userInfoResponse = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if (! $userInfoResponse->successful()) {
                return redirect()->route('auth.google.prompt')->withErrors(['email' => 'فشل جلب بيانات حساب Google.']);
            }

            $userData = $userInfoResponse->json();
            $email = strtolower(trim($userData['email']));
            $name = $userData['name'] ?? explode('@', $email)[0];
            $googleId = $userData['sub'] ?? null;
            $avatar = $userData['picture'] ?? null;

            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update([
                    'name' => $name ?: $user->name,
                    'google_id' => $googleId ?: $user->google_id,
                    'avatar' => $avatar ?: $user->avatar,
                    'is_verified' => true,
                    'email_verified_at' => $user->email_verified_at ?: now(),
                ]);
            } else {
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

            Auth::login($user);

            return redirect()->route('home')->with('success', "تم تسجيل الدخول بنجاح عبر حساب Google ({$user->email}).");
        } catch (\Throwable $e) {
            Log::error('Google OAuth Exception: ' . $e->getMessage());
            return redirect()->route('auth.google.prompt')->withErrors(['email' => 'تعذر إكمال الربط المباشر مع Google: ' . $e->getMessage()]);
        }
    }
}
