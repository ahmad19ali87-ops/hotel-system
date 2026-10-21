@extends('layouts.app')

@section('title', 'استعادة كلمة المرور')

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-12 col-sm-10 col-md-8 col-lg-5">
        <div class="card border-0 shadow-2xl rounded-3xl overflow-hidden">
            <div class="card-header bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-900 text-white p-5 text-center border-0">
                <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md mx-auto d-flex align-items-center justify-content-center text-3xl mb-3 shadow-inner border border-white/20">
                    🔑
                </div>
                <h4 class="font-black text-2xl mb-1 text-white">استعادة كلمة المرور</h4>
                <p class="text-xs text-blue-100 mb-0">خاص بالزبائن والنزلاء المسجلين لاستعادة الوصول إلى الحساب</p>
            </div>

            <div class="card-body p-4 sm:p-5">
                @if(session('status'))
                    <div class="alert alert-success rounded-2xl text-xs font-semibold mb-4 d-flex align-items-center gap-2">
                        <span>✅</span>
                        <div>{{ session('status') }}</div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger rounded-2xl text-xs font-semibold mb-4 d-flex align-items-center gap-2">
                        <span>⚠️</span>
                        <div>{{ $errors->first() }}</div>
                    </div>
                @endif

                <div class="p-3 rounded-2xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 text-blue-800 dark:text-blue-300 text-xs font-medium mb-4">
                    أدخل بريدك الإلكتروني المسجل لدينا وسنرسل لك رابطاً وكوداً مكوناً من 6 أرقام من <strong>info@ahmadinsights.online</strong> لإعادة تعيين كلمة المرور بكل سهولة وأمان.
                </div>

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="email" class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">البريد الإلكتروني المسجل <span class="text-rose-500">*</span></label>
                        <input id="email" type="email" name="email" class="form-control rounded-xl py-3 px-3 text-sm @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus placeholder="your.email@example.com">
                        @error('email')
                            <div class="invalid-feedback text-xs font-bold">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-2xl font-bold shadow-lg shadow-blue-600/30 text-sm">
                        إرسال رابط وكود الاستعادة 📨
                    </button>
                </form>

                <div class="mt-3 text-center">
                    <a href="{{ route('password.reset') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 text-decoration-none">
                        وصلك كود الاستعادة بالفعل؟ أدخل الكود هنا 🔐
                    </a>
                </div>

                <div class="text-center mt-4 pt-3 border-top border-slate-100 dark:border-slate-800 d-flex justify-content-between align-items-center">
                    <a href="{{ route('login') }}" class="text-xs font-bold text-blue-600 dark:text-blue-400 text-decoration-none">
                        &larr; العودة لتسجيل الدخول
                    </a>
                    <a href="{{ route('register') }}" class="text-xs font-bold text-slate-500 text-decoration-none">
                        ليس لديك حساب؟ تسجيل جديد
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

