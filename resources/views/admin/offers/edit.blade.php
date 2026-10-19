@extends('layouts.app')

@section('content')
<div class="row justify-content-center my-4">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-xl rounded-3xl overflow-hidden bg-white dark:bg-slate-800">
            <div class="card-header bg-gradient-to-r from-blue-700 to-indigo-800 text-white p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="font-black text-xl mb-1 text-white">✏️ تعديل العرض الترويجي</h4>
                        <p class="text-xs text-blue-100 mb-0">{{ $offer->title }}</p>
                    </div>
                    <a href="{{ route('admin.offers.index') }}" class="btn btn-sm btn-light rounded-xl font-bold text-xs">
                        عودة للعروض
                    </a>
                </div>
            </div>

            <div class="card-body p-4 sm:p-5">
                <form action="{{ route('admin.offers.update', $offer) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @if($errors->any())
                        <div class="alert alert-danger rounded-2xl text-xs font-bold mb-4">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label font-bold text-xs text-slate-700 dark:text-slate-300">عنوان العرض <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" class="form-control rounded-xl py-2.5 px-3 text-sm" value="{{ old('title', $offer->title) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-bold text-xs text-slate-700 dark:text-slate-300">وصف ومزايا العرض</label>
                        <textarea name="description" rows="3" class="form-control rounded-2xl p-3 text-sm">{{ old('description', $offer->description) }}</textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label font-bold text-xs text-slate-700 dark:text-slate-300">نسبة الخصم (%)</label>
                            <input type="number" step="0.01" name="discount_percentage" class="form-control rounded-xl py-2.5 px-3 text-sm" value="{{ old('discount_percentage', $offer->discount_percentage) }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label font-bold text-xs text-slate-700 dark:text-slate-300">السعر المخفض (ليرة سورية)</label>
                            <input type="number" step="0.01" name="special_price" class="form-control rounded-xl py-2.5 px-3 text-sm" value="{{ old('special_price', $offer->special_price) }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label font-bold text-xs text-slate-700 dark:text-slate-300">السعر المخفض (USD)</label>
                            <input type="number" step="0.01" name="special_price_usd" class="form-control rounded-xl py-2.5 px-3 text-sm" value="{{ old('special_price_usd', $offer->special_price_usd) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-xs text-slate-700 dark:text-slate-300">تاريخ بدء العرض</label>
                            <input type="date" name="start_date" class="form-control rounded-xl py-2 px-3 text-sm" value="{{ old('start_date', $offer->start_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-xs text-slate-700 dark:text-slate-300">تاريخ انتهاء العرض</label>
                            <input type="date" name="end_date" class="form-control rounded-xl py-2 px-3 text-sm" value="{{ old('end_date', $offer->end_date?->format('Y-m-d')) }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-bold text-xs text-slate-700 dark:text-slate-300">تحديث صورة العرض</label>
                        @if($offer->image)
                            <div class="mb-2">
                                <img src="{{ $offer->image_url }}" alt="" class="h-24 rounded-xl object-cover border">
                            </div>
                        @endif
                        <input type="file" name="image" class="form-control rounded-xl py-2 text-sm" accept="image/*">
                    </div>

                    <div class="mb-4 p-3 rounded-2xl bg-slate-50 dark:bg-slate-900 border">
                        <label class="d-flex align-items-center gap-2 cursor-pointer mb-0">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $offer->is_active)) class="rounded h-5 w-5 text-blue-600">
                            <span class="font-bold text-xs text-slate-800 dark:text-slate-200">تفعيل العرض الترويجي والظهور في قسم العروض الحالية</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-2xl font-bold shadow-lg shadow-blue-600/30 text-sm">
                        حفظ التعديلات 💾
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

