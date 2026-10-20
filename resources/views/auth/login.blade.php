@extends('layouts.app')

@section('content')
<div class="row justify-content-center py-4">
    <div class="col-12 col-sm-10 col-md-8 col-lg-5">
        <div class="card border-0 shadow-2xl rounded-3xl overflow-hidden">
            <div class="card-header bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-900 text-white p-5 text-center border-0">
                <div class="w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-md mx-auto d-flex align-items-center justify-content-center text-2xl mb-2 shadow-inner border border-white/20">
                    🔐
                </div>
                <h4 class="font-black text-2xl mb-1 text-white">تسجيل الدخول</h4>
                <p class="text-xs text-blue-100 mb-0">أهلاً بك مجدداً، سجّل الدخول لإدارة حجوزاتك أو فندقك</p>
            </div>

            <div class="card-body p-4 sm:p-5">
                {{-- Direct Google Sign In Button --}}
                <div class="mb-4">
                    <a href="{{ route('auth.google') }}" class="btn btn-outline-secondary w-100 py-3 rounded-2xl font-bold d-flex align-items-center justify-content-center gap-2 border-slate-300 hover:border-slate-400 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 shadow-sm transition">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                        </svg>
                        <span>تسجيل الدخول المباشر عبر Google</span>
                    </a>

                    <div class="d-flex align-items-center my-3">
                        <hr class="flex-grow-1 border-slate-200 dark:border-slate-700">
                        <span class="px-3 text-xs font-bold text-slate-400">أو عبر البريد وكلمة المرور</span>
                        <hr class="flex-grow-1 border-slate-200 dark:border-slate-700">
                    </div>
                </div>

                <form action="{{ route('login') }}" method="POST">
                    @csrf

                    @if($errors->any())
                        <div class="alert alert-danger rounded-2xl text-xs font-semibold mb-3">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">البريد الإلكتروني <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" class="form-control rounded-xl py-2.5 px-3 text-sm @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus placeholder="your.email@example.com">
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300 mb-0">كلمة المرور <span class="text-rose-500">*</span></label>
                            <a href="{{ route('password.request') }}" class="text-xs font-bold text-blue-600 dark:text-blue-400 text-decoration-none hover:underline">
                                نسيت كلمة المرور؟
                            </a>
                        </div>
                        <input type="password" name="password" class="form-control rounded-xl py-2.5 px-3 text-sm @error('password') is-invalid @enderror" required placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-2xl font-bold shadow-lg shadow-blue-600/30 text-sm">
                        دخول إلى الحساب 🚀
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top border-slate-100 dark:border-slate-800 d-flex justify-content-between align-items-center">
                    <a href="{{ route('register') }}" class="text-xs font-bold text-blue-600 dark:text-blue-400 text-decoration-none">
                        ✨ ليس لديك حساب؟ سجّل الآن
                    </a>
                    <a href="{{ route('verification.notice') }}" class="text-xs text-slate-500 hover:text-blue-600 text-decoration-none">
                        تفعيل الحساب بكود
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
