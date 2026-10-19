<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        if (Auth::attempt($credentials)) {
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

        return back()->withErrors([
            'email' => 'بيانات الدخول غير صحيحة.',
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
