@extends('layouts.app')

@section('content')
<div class="container my-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="font-black text-2xl tracking-tight text-slate-900 dark:text-slate-100 mb-1">
                🏷️ العروض والباقات الترويجية للفندق
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mb-0">
                إدارة العروض الخاصة والخصومات التي تظهر للزوار في قسم العروض الحالية وصفحة الفندق
            </p>
        </div>
        @if(auth()->user()->role !== 'hotel_owner')
        <a href="{{ route('admin.offers.create') }}" class="btn btn-primary rounded-2xl font-bold text-xs sm:text-sm px-4 py-2.5 shadow-md shadow-blue-600/20">
            ➕ إضافة عرض جديد
        </a>
        @else
        <span class="badge bg-warning text-dark font-bold px-3 py-2 rounded-xl">👑 مالك الفندق - وضع الاطلاع فقط</span>
        @endif
    </div>

    @if($offers->isEmpty())
        <div class="card border-0 shadow-sm rounded-3xl p-5 text-center my-4 bg-white dark:bg-slate-800">
            <div class="text-5xl mb-3">🎁</div>
            <h4 class="font-bold text-slate-800 dark:text-slate-100 mb-2">لا توجد عروض ترويجية مضافة لفندقك</h4>
            <p class="text-xs text-slate-500 max-w-md mx-auto mb-4">
                أضف عروضاً وخصومات لجذب المزيد من النزلاء والظهور في قسم العروض الحالية على الصفحة الرئيسية.
            </p>
            @if(auth()->user()->role !== 'hotel_owner')
            <div>
                <a href="{{ route('admin.offers.create') }}" class="btn btn-primary rounded-xl px-4 py-2 font-bold text-sm">
                    إضافة أول عرض ترويجي
                </a>
            </div>
            @endif
        </div>
    @else
        <div class="card border-0 shadow-md rounded-3xl overflow-hidden bg-white dark:bg-slate-800">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-900/60 text-xs font-bold uppercase text-slate-500 border-b border-slate-100 dark:border-slate-700">
                        <tr>
                            <th class="ps-4 py-3">العرض</th>
                            <th>نسبة الخصم</th>
                            <th>السعر الخاص (ليرة)</th>
                            <th>السعر الخاص (USD)</th>
                            <th>فترة الصلاحية</th>
                            <th>الحالة</th>
                            <th class="text-end pe-4">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        @foreach($offers as $offer)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <img src="{{ $offer->image_url }}" alt="" class="w-12 h-12 rounded-xl object-cover border border-slate-200 dark:border-slate-700 bg-slate-100">
                                        <div>
                                            <strong class="font-bold text-slate-900 dark:text-slate-100 block">{{ $offer->title }}</strong>
                                            <span class="text-xs text-slate-400 block truncate max-w-xs">{{ $offer->description ?: '—' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($offer->discount_percentage > 0)
                                        <span class="badge bg-rose-100 text-rose-700 font-black text-xs px-2.5 py-1 rounded-xl">
                                            خصم {{ (int)$offer->discount_percentage }}%
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    <strong class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ $offer->special_price ? number_format($offer->special_price, 2) . ' ل.س' : '—' }}
                                    </strong>
                                </td>
                                <td>
                                    <strong class="text-xs font-bold text-blue-600 dark:text-blue-400">
                                        {{ $offer->special_price_usd ? '$' . number_format($offer->special_price_usd, 2) : '—' }}
                                    </strong>
                                </td>
                                <td>
                                    <span class="text-xs text-slate-500">
                                        {{ $offer->start_date ? $offer->start_date->format('Y/m/d') : 'الآن' }} إلى {{ $offer->end_date ? $offer->end_date->format('Y/m/d') : 'دائم' }}
                                    </span>
                                </td>
                                <td>
                                    @if(auth()->user()->role !== 'hotel_owner')
                                    <form action="{{ route('admin.offers.toggle', $offer) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm rounded-xl text-xs font-bold py-1 px-2.5 {{ $offer->is_active ? 'btn-outline-success' : 'btn-outline-secondary' }}">
                                            {{ $offer->is_active ? '✅ نشط' : '⏸️ متوقف' }}
                                        </button>
                                    </form>
                                    @else
                                        <span class="badge bg-slate-100 text-slate-700 border border-slate-200 px-2 py-1 text-xs">
                                            {{ $offer->is_active ? '✅ نشط' : '⏸️ متوقف' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if(auth()->user()->role !== 'hotel_owner')
                                    <div class="d-inline-flex align-items-center gap-1.5">
                                        <a href="{{ route('admin.offers.edit', $offer) }}" class="btn btn-sm btn-outline-primary rounded-xl font-bold text-xs py-1 px-2.5">
                                            ✏️ تعديل
                                        </a>
                                        <form action="{{ route('admin.offers.destroy', $offer) }}" method="POST" class="m-0" onsubmit="return confirm('هل أنت متأكد من حذف هذا العرض الترويجي؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-xl font-bold text-xs py-1 px-2.5">
                                                🗑️ حذف
                                            </button>
                                        </form>
                                    </div>
                                    @else
                                        <span class="badge bg-slate-100 text-slate-500 border border-slate-200 text-xs px-2 py-1">اطلاع فقط</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $offers->links() }}
        </div>
    @endif
</div>
@endsection

