@extends('layouts.app')

@section('content')
<div class="container my-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
        <div>
            <span class="text-xs font-bold text-blue-600 uppercase tracking-wider block mb-1">
                🔔 {{ __('مركز الإشعارات والتنبيهات') }}
            </span>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-slate-100 mb-0">
                {{ __('الإشعارات الواردة') }}
            </h1>
        </div>

        @if(Auth::user()->unreadNotificationsCount() > 0)
            <form action="{{ route('notifications.read-all') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary rounded-xl px-3 py-2 font-bold text-xs d-flex align-items-center gap-1.5 shadow-sm">
                    <span>✓✓</span>
                    <span>{{ __('تحديد الكل كمقروء') }}</span>
                </button>
            </form>
        @endif
    </div>

    @if($notifications->isEmpty())
        <div class="card border-0 shadow-sm rounded-3xl p-5 text-center my-5 bg-white dark:bg-slate-800">
            <div class="text-5xl mb-3">🔔</div>
            <h4 class="font-bold text-slate-800 dark:text-slate-100 mb-2">{{ __('لا توجد إشعارات حتى الآن') }}</h4>
            <p class="text-xs text-slate-500 max-w-md mx-auto mb-0">
                {{ __('ستصلك هنا كافة التنبيهات الخاصة بالحجوزات، التأكيدات، الفواتير، الصيانة، التنظيف، والرسائل فور حدوثها.') }}
            </p>
        </div>
    @else
        <div class="card border-0 shadow-sm rounded-3xl overflow-hidden bg-white dark:bg-slate-800">
            <div class="divide-y divide-slate-100 dark:divide-slate-700/60">
                @foreach($notifications as $notification)
                    <div class="p-4 d-flex align-items-start gap-3 transition-colors {{ !$notification->is_read ? 'bg-blue-50/60 dark:bg-blue-950/20' : 'hover:bg-slate-50/60 dark:hover:bg-slate-700/20' }}">
                        {{-- Icon --}}
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-lg flex-shrink-0 {{ !$notification->is_read ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                            {{ $notification->icon ?? '🔔' }}
                        </div>

                        {{-- Details --}}
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                <h6 class="font-bold text-sm text-slate-900 dark:text-slate-100 mb-0 truncate">
                                    {{ $notification->title }}
                                </h6>
                                <span class="text-[11px] text-slate-400 whitespace-nowrap flex-shrink-0">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-2">
                                {{ $notification->message }}
                            </p>

                            <div class="d-flex align-items-center gap-2">
                                @if($notification->link)
                                    <form action="{{ route('notifications.read', $notification) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary rounded-xl text-xs py-1 px-3 font-bold">
                                            <span>{{ __('عرض التفاصيل') }}</span> →
                                        </button>
                                    </form>
                                @endif

                                @if(!$notification->is_read && !$notification->link)
                                    <form action="{{ route('notifications.read', $notification) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-light rounded-xl text-xs py-1 px-2.5 text-slate-600 hover:text-slate-900">
                                            ✓ {{ __('تحديد كمقروء') }}
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('notifications.destroy', $notification) }}" method="POST" class="m-0" onsubmit="return confirm('هل تريد حذف هذا الإشعار؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-rose-500 hover:text-rose-700 text-xs p-1 text-decoration-none" title="{{ __('حذف الإشعار') }}">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>

                        @if(!$notification->is_read)
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600 flex-shrink-0 mt-1.5" title="{{ __('غير مقروء') }}"></span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection

