@extends('layouts.app')

@section('title', 'تسجيل الدخول عبر حساب Google')

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-12 col-sm-10 col-md-8 col-lg-5">
        <div class="card border-0 shadow-2xl rounded-3xl overflow-hidden">
            <div class="card-header bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-900 text-white p-5 text-center border-0">
                <div class="w-16 h-16 rounded-2xl bg-white shadow-md mx-auto d-flex align-items-center justify-content-center p-3 mb-3 border border-white/20">
                    <svg class="w-10 h-10" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                </div>
                <h4 class="font-black text-2xl mb-1 text-white">تسجيل الدخول بحساب Google</h4>
                <p class="text-xs text-blue-100 mb-0">اختر حسابك الرسمي للمتابعة وتأكيد الهوية بأمان</p>
            </div>

            <div class="card-body p-4 sm:p-5">
                @if(session('info'))
                    <div class="alert alert-info rounded-2xl text-xs font-semibold mb-3">
                        ℹ️ {{ session('info') }}
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning rounded-2xl text-xs font-semibold mb-3">
                        ⚠️ {{ session('warning') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger rounded-2xl text-xs font-semibold mb-3">
                        {{ $errors->first() }}
                    </div>
                @endif

                {{-- Official Google Identity Services One Tap / Button --}}
                <script src="https://accounts.google.com/gsi/client" async defer></script>
                <div id="g_id_onload"
                     data-client_id="{{ $clientId ?? config('services.google.client_id', '467934742059-hnkanpf5954jqgtu18aubhqpj780et00.apps.googleusercontent.com') }}"
                     data-login_uri="{{ route('auth.google.one-tap') }}"
                     data-auto_prompt="false">
                </div>

                <div class="d-flex justify-content-center mb-3">
                    <div class="g_id_signin"
                         data-type="standard"
                         data-size="large"
                         data-theme="outline"
                         data-text="sign_in_with"
                         data-shape="rectangular"
                         data-logo_alignment="center"
                         data-width="320">
                    </div>
                </div>

                <div class="mb-3">
                    <a href="{{ route('auth.google') }}" class="btn btn-outline-primary w-100 py-3 rounded-2xl font-bold d-flex align-items-center justify-content-center gap-2 text-sm shadow-sm">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                        </svg>
                        <span>التوجيه المباشر إلى صفحة حسابات Google</span>
                    </a>
                </div>

                <div class="d-flex align-items-center my-4">
                    <hr class="flex-grow-1 border-slate-200 dark:border-slate-700">
                    <span class="px-3 text-xs font-bold text-slate-400">أو تأكيد بريدك على Gmail يدوياً</span>
                    <hr class="flex-grow-1 border-slate-200 dark:border-slate-700">
                </div>

                {{-- Direct Gmail Entry Form --}}
                <form method="POST" action="{{ route('auth.google.submit') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">عنوان بريد Gmail الحقيقي <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" required autofocus
                            placeholder="yourname@gmail.com"
                            value="{{ old('email') }}"
                            class="form-control rounded-xl py-2.5 px-3 text-sm text-start" dir="ltr">
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-bold text-xs text-slate-600 dark:text-slate-300">الاسم الكامل (كما في حساب جوجل)</label>
                        <input type="text" name="name"
                            placeholder="الاسم الكامل"
                            value="{{ old('name') }}"
                            class="form-control rounded-xl py-2.5 px-3 text-sm">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-2xl font-bold shadow-lg shadow-blue-600/30 text-sm">
                        متابعة وتأكيد الدخول عبر Gmail 🚀
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top border-slate-100 dark:border-slate-800">
                    <a href="{{ route('login') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800 text-decoration-none">
                        &larr; العودة لصفحة تسجيل الدخول المعتادة
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
