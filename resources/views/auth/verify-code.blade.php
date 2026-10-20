@extends('layouts.app')

@section('content')
<div class="row justify-content-center py-4">
    <div class="col-12 col-sm-10 col-md-8 col-lg-5">
        <div class="card border-0 shadow-xl rounded-3xl overflow-hidden">
            <div class="card-header bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-900 text-white p-4 text-center border-0">
                <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md mx-auto d-flex align-items-center justify-content-center text-3xl mb-2 shadow-inner border border-white/20">
                    ✉️
                </div>
                <h4 class="font-black text-xl mb-1 text-white">تفعيل الحساب</h4>
                <p class="text-xs text-blue-100 mb-0">أدخل كود التفعيل المكون من 6 أرقام لتفعيل حسابك وإكمال الحجز</p>
            </div>

            <div class="card-body p-4 sm:p-5">
                @if(session('success'))
                    <div class="alert alert-success text-xs font-semibold rounded-2xl mb-4 d-flex align-items-center gap-2">
                        <span>✅</span>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning text-xs font-semibold rounded-2xl mb-4 d-flex align-items-center gap-2">
                        <span>⚠️</span>
                        <div>{{ session('warning') }}</div>
                    </div>
                @endif

                <div class="p-3 rounded-2xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 text-blue-800 dark:text-blue-300 text-xs font-medium text-center mb-4">
                    📨 تم إرسال كود التفعيل المكون من 6 أرقام إلى بريدك الإلكتروني ({{ $user->email ?? 'المسجل' }}) من <strong class="text-blue-900 dark:text-blue-200">info@ahmadinsights.online</strong>. يرجى مراجعة صندوق الوارد والرسائل غير المرغوب فيها وإدخال الكود أدناه.
                </div>

                <form method="POST" action="{{ route('verification.verify') }}">
                    @csrf

                    <div class="mb-4 text-center">
                        <label for="code" class="form-label font-bold text-xs uppercase text-slate-600 dark:text-slate-300 mb-2">
                            أدخل كود التفعيل المكون من 6 أرقام
                        </label>
                        <input id="code" type="text" maxlength="6" inputmode="numeric" 
                               class="form-control form-control-lg text-center tracking-widest font-black text-2xl py-3 rounded-2xl border-2 @error('code') is-invalid @enderror" 
                               name="code" value="{{ old('code') }}" required autofocus placeholder="------">
                        @error('code')
                            <div class="invalid-feedback text-xs font-bold text-start">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-2xl font-bold shadow-lg shadow-blue-600/30 text-sm">
                        تأكيد وتفعيل الحساب 🚀
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top border-slate-100 dark:border-slate-800 d-flex justify-content-between align-items-center">
                    <form method="POST" action="{{ route('verification.resend') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-link btn-sm text-decoration-none text-xs text-blue-600 dark:text-blue-400 font-bold p-0">
                            🔄 إعادة إرسال الكود
                        </button>
                    </form>

                    <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-xs text-slate-500 hover:text-rose-600 text-decoration-none font-semibold">
                        تسجيل الخروج
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

