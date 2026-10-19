@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl py-6 px-4">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-blue-600 block mb-1">لوحة إدارة المنصة</span>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900">تخصيص الوجهات السياحية والمظهر الخارجي</h1>
            <p class="text-sm text-slate-500 mt-1">تعديل صور المحافظات، العبارات الترويجية، وروابط التوجيه في الصفحة الرئيسية للموقع</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('home') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                👁️ معاينة الموقع
            </a>
            <a href="{{ route('platform.destinations.create') }}" class="btn btn-primary btn-sm shadow-sm font-bold">
                ➕ إضافة وجهة / محافظة جديدة
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @forelse($destinations as $dest)
            <div class="card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm hover:shadow-md transition">
                <div class="relative h-44 bg-slate-900">
                    @if($dest->image_url)
                        <img src="{{ $dest->image_url }}" alt="{{ $dest->title }}" class="h-full w-full object-cover opacity-85">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-5xl bg-gradient-to-br from-slate-800 to-slate-900">
                            {{ $dest->icon ?: '🏛️' }}
                        </div>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                    <div class="absolute top-2.5 right-2.5">
                        <span class="badge {{ $dest->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $dest->is_active ? 'نشط' : 'معطل' }}
                        </span>
                    </div>
                    <div class="absolute bottom-2.5 right-3 left-3 text-white">
                        <h3 class="font-extrabold text-base flex items-center gap-1.5">
                            @if($dest->icon && $dest->image_url) <span>{{ $dest->icon }}</span> @endif
                            <span>{{ $dest->title }}</span>
                        </h3>
                        @if($dest->subtitle)
                            <p class="text-xs text-slate-200 line-clamp-1 mt-0.5">{{ $dest->subtitle }}</p>
                        @endif
                    </div>
                </div>

                <div class="p-4 flex flex-col justify-between flex-1">
                    <div class="text-xs text-slate-500 mb-3 space-y-1">
                        <div><strong class="text-slate-700">الرابط:</strong> <span class="text-blue-600 break-all">{{ $dest->link ?: 'تلقائي (فلتر المحافظة)' }}</span></div>
                        <div><strong class="text-slate-700">الترتيب:</strong> {{ $dest->sort_order }}</div>
                    </div>

                    <div class="flex items-center justify-between gap-2 border-t border-slate-100 pt-3">
                        <a href="{{ route('platform.destinations.edit', $dest) }}" class="btn btn-outline-primary btn-sm flex-1 text-xs font-bold">
                            ✏️ تعديل
                        </a>
                        <form action="{{ route('platform.destinations.destroy', $dest) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الوجهة السياحية؟');" class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm text-xs font-bold">
                                🗑️ حذف
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
                <span class="text-4xl block mb-2">🏛️</span>
                <h3 class="text-base font-bold text-slate-800">لا توجد وجهات سياحية مضافة حالياً</h3>
                <p class="text-xs text-slate-500 mt-1">أضف محافظات ووجهات مميزة لتظهر في واجهة الموقع الخارجية أمام الزوار.</p>
                <a href="{{ route('platform.destinations.create') }}" class="btn btn-primary btn-sm mt-4 font-bold">
                    ➕ إضافة وجهة الآن
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection

