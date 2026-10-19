@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-2xl py-8 px-4">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">تعديل العملية المالية</h1>
            <p class="text-sm text-slate-500 mt-1">تعديل تفاصيل قيد الإيراد أو المصروف</p>
        </div>
        <a href="{{ route('accounts.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
            ← عودة للحسابات
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form action="{{ route('accounts.update', $account->id) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">نوع العملية <span class="text-rose-500">*</span></label>
                    <select name="type" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        <option value="income" @selected(old('type', $account->type) === 'income')>📥 إيراد (وارد)</option>
                        <option value="expense" @selected(old('type', $account->type) === 'expense')>📤 مصروف (صادر)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">العملة <span class="text-rose-500">*</span></label>
                    <select name="currency" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        <option value="SYP" @selected(old('currency', $account->currency ?? 'SYP') === 'SYP')>ليرة سورية (ل.س)</option>
                        <option value="USD" @selected(old('currency', $account->currency) === 'USD')>دولار أمريكي ($ USD)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">البيان / عنوان العملية <span class="text-rose-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $account->title) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">المبلغ <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" name="amount" value="{{ old('amount', $account->amount) }}" required min="0" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">تاريخ العملية <span class="text-rose-500">*</span></label>
                    <input type="date" name="transaction_date" value="{{ old('transaction_date', $account->transaction_date) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">ملاحظات إضافية</label>
                <textarea name="notes" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">{{ old('notes', $account->notes) }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3">
                <a href="{{ route('accounts.index') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">إلغاء</a>
                <button type="submit" class="rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white shadow hover:bg-slate-800">تحديث العملية</button>
            </div>
        </form>
    </div>
</div>
@endsection
