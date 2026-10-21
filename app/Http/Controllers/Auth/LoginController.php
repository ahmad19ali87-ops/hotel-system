<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ThreatAutoResponseEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Anti-Brute Force: Check if user account is locked
        if ($user && $user->isLocked()) {
            $diff = $user->locked_until->diffForHumans();
            return back()->withInput($request->only('email'))->withErrors([
                'email' => "تم قفل هذا الحساب مؤقتاً لمدة ساعة لحمايته من هجمات التخمين (Anti-Brute Force). ينتهي القفل بعد {$diff}.",
            ]);
        }

        if (Auth::attempt($credentials)) {
            // Reset brute force counter on successful login
            Cache::forget("brute_force_login:{$credentials['email']}");

            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->role === 'guest') {
                if (!$user->is_verified) {
                    session(['pending_verify_user_id' => $user->id]);
                    $code = $user->verification_code ?: str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                    $user->update(['verification_code' => $code]);
                    Auth::logout();

                    return redirect()->route('verification.notice')->with('warning', "يرجى تفعيل حسابك أولاً. كود التفعيل الخاص بك هو: {$code}");
                }
                return redirect()->intended(route('home'));
            }

            if (! in_array($user->role, ['super_admin', 'hotel_admin', 'admin', 'reception', 'accountant', 'maintenance', 'cleaner'], true)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'هذا الحساب غير مفعّل. راجع مدير الفندق.',
                ]);
            }

            return redirect()->intended(route('dashboard.redirect'));
        }

        // Failed login attempt tracking
        $email = $credentials['email'];
        $ip = $request->ip() ?: '0.0.0.0';
        $cacheKey = "brute_force_login:{$email}";
        $attempts = (int) Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $attempts, now()->addMinutes(15));

        if ($attempts >= 5) {
            ThreatAutoResponseEngine::handleBruteForce($email, $ip, $request);
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'تم قفل الحساب تلقائياً لمدة ساعة كاملة بسبب تكرار محاولات الدخول الخاطئة (Anti-Brute Force Protection).',
            ]);
        }

        $remaining = max(1, 5 - $attempts);

        return back()->withInput($request->only('email'))->withErrors([
            'email' => "بيانات الدخول غير صحيحة. المحاولات المتبقية قبل قفل الحساب: {$remaining}.",
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
