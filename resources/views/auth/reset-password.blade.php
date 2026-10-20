@extends('layouts.app')

@section('title', 'تعيين كلمة المرور الجديدة')

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-12 col-sm-10 col-md-8 col-lg-5">
        <div class="card border-0 shadow-2xl rounded-3xl overflow-hidden">
            <div class="card-header bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-900 text-white p-5 text-center border-0">
                <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md mx-auto d-flex align-items-center justify-content-center text-3xl mb-3 shadow-inner border border-white/20">
                    🔐
                </div>
                <h4 class="font-black text-2xl mb-1 text-white">تعيين كلمة المرور الجديدة</h4>
                <p class="text-xs text-blue-100 mb-0">أدخل كلمة المرور الجديدة لتأكيد التغيير واستعادة حسابك</p>
            </div>

            <div class="card-body p-4 sm:p-5">
                @if($errors->any())
                    <div class="alert alert-danger rounded-2xl text-xs font-semibold mb-4 d-flex align-items-center gap-2">
                        <span>⚠️</span>
                        <div>{{ $errors->first() }}</div>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf

                    @if(!empty($token))
                        <input type="hidden" name="token" value="{{ $token }}">
                    @else
                        <div class="mb-3">
                            <label for="token" class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">كود أو رمز الاستعادة (المكون من 6 أرقام) <span class="text-rose-500">*</span></label>
                            <input id="token" type="text" name="token" class="form-control rounded-xl py-2.5 px-3 text-sm @error('token') is-invalid @enderror" value="{{ old('token') }}" required placeholder="123456">
                            @error('token')
                                <div class="invalid-feedback text-xs font-bold">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="email" class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">البريد الإلكتروني المسجل <span class="text-rose-500">*</span></label>
                        <input id="email" type="email" name="email" class="form-control rounded-xl py-2.5 px-3 text-sm @error('email') is-invalid @enderror" value="{{ old('email', $email ?? '') }}" required placeholder="your.email@example.com">
                        @error('email')
                            <div class="invalid-feedback text-xs font-bold">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">كلمة المرور الجديدة <span class="text-rose-500">*</span></label>
                        <input id="password" type="password" name="password" class="form-control rounded-xl py-2.5 px-3 text-sm @error('password') is-invalid @enderror" required placeholder="••••••••">
                        @error('password')
                            <div class="invalid-feedback text-xs font-bold">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">تأكيد كلمة المرور الجديدة <span class="text-rose-500">*</span></label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="form-control rounded-xl py-2.5 px-3 text-sm" required placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-2xl font-bold shadow-lg shadow-blue-600/30 text-sm">
                        حفظ كلمة المرور والدخول للحساب 🚀
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top border-slate-100 dark:border-slate-800">
                    <a href="{{ route('login') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800 text-decoration-none">
                        &larr; العودة لصفحة تسجيل الدخول
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

