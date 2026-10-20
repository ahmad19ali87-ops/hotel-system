<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    /**
     * Ensure password_reset_tokens table exists.
     */
    private function ensureTableExists(): void
    {
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    /**
     * Show the password reset request form.
     */
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a reset link to the given registered user.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'يرجى إدخال عنوان البريد الإلكتروني.',
            'email.email' => 'يرجى إدخال عنوان بريد إلكتروني صحيح.',
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        // Restrict to registered customers / users
        if (!$user) {
            return back()->withErrors([
                'email' => 'هذا البريد الإلكتروني غير مسجل لدينا. ميزة استعادة كلمة المرور متاحة للزبائن المسجلين فقط.',
            ]);
        }

        $this->ensureTableExists();

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
            Log::error('Failed to send password reset email: ' . $e->getMessage());
        }

        return back()->with('status', 'تم إرسال رابط وكود استعادة كلمة المرور إلى بريدك الإلكتروني من (info@ahmadinsights.online). يرجى مراجعة صندوق الوارد.');
    }

    /**
     * Display the password reset form for the given token.
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.reset-password')->with([
            'token' => $token ?: $request->query('token'),
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Reset the given user's password.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 6 أحرف.',
        ]);

        $this->ensureTableExists();

        $email = strtolower(trim($request->email));
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record) {
            return back()->withErrors([
                'email' => 'طلب استعادة كلمة المرور غير صالح أو تم استخدامه مسبقاً.',
            ]);
        }

        // Check if token expired (60 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return back()->withErrors([
                'email' => 'انتهت صلاحية رابط الاستعادة (صالحة لمدة 60 دقيقة). يرجى تقديم طلب استعادة جديد.',
            ]);
        }

        $parts = explode('|', $record->token);
        $storedToken = $parts[0] ?? '';
        $storedCode = $parts[1] ?? '';

        $inputToken = trim($request->token);
        if ($inputToken !== $storedToken && $inputToken !== $storedCode) {
            return back()->withErrors([
                'token' => 'كود أو رابط التحقق غير صحيح.',
            ]);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->withErrors([
                'email' => 'المستخدم غير موجود.',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'is_verified' => true,
        ]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        Auth::login($user);

        return redirect()->route('home')->with('success', 'تهانينا! تم تعيين كلمة المرور الجديدة وتفعيل حسابك بنجاح.');
    }
}
