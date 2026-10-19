@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl py-6 px-4">
    <div class="mb-6 flex items-center justify-between border-b border-slate-200 pb-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-blue-600 block mb-1">لوحة إدارة المنصة</span>
            <h1 class="text-2xl font-black text-slate-900">تعديل الوجهة: {{ $destination->title }}</h1>
            <p class="text-xs text-slate-500 mt-1">تخصيص الصورة والعبارات الترويجية والرابط لهذه الوجهة</p>
        </div>
        <a href="{{ route('platform.destinations.index') }}" class="btn btn-secondary btn-sm">العودة للقائمة</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card p-6 shadow-sm border border-slate-200 bg-white rounded-2xl">
        <form method="POST" action="{{ route('platform.destinations.update', $destination) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-slate-700 mb-1">اسم الوجهة / المحافظة <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $destination->title) }}" required class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">رمز تعبيري (أيقونة)</label>
                    <input type="text" name="icon" value="{{ old('icon', $destination->icon) }}" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-center text-lg">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">الجملة الترويجية / الوصف المختصر</label>
                <input type="text" name="subtitle" value="{{ old('subtitle', $destination->subtitle) }}" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">
                    رابط التحويل عند النقر
                    <span class="text-xs font-normal text-slate-500 mr-2">(اتركه فارغاً ليقوم بتصفية الفنادق تلقائياً حسب اسم المحافظة)</span>
                </label>
                <input type="text" name="link" value="{{ old('link', $destination->link) }}" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            {{-- Image Box --}}
            <div class="border rounded-xl p-4 bg-slate-50">
                <label class="block text-sm font-bold text-slate-700 mb-2">صورة الوجهة</label>

                @if($destination->image_url)
                    <div id="current_image_container" class="relative inline-block mb-3">
                        <img src="{{ $destination->image_url }}" alt="{{ $destination->title }}" class="h-36 w-64 rounded-xl object-cover border border-slate-200 shadow-sm">
                        <label class="flex items-center gap-2 mt-2 cursor-pointer text-xs font-bold text-red-600">
                            <input type="checkbox" name="delete_image" value="1" class="rounded text-red-600 focus:ring-red-500">
                            <span>حذف هذه الصورة والاعتماد على الرمز التعبيري</span>
                        </label>
                    </div>
                @endif

                <label class="block text-sm">
                    <span class="text-xs text-slate-600">{{ $destination->image_url ? 'استبدال الصورة الحالية:' : 'رفع صورة للوجهة:' }}</span>
                    <input type="file" name="image" id="destination_image_input" accept="image/png,image/jpeg,image/webp" class="mt-1 w-full rounded-xl border border-slate-300 p-2 bg-white text-sm">
                </label>

                <div id="image_preview_box" class="hidden mt-3">
                    <span class="text-xs text-slate-500 block mb-1">معاينة الصورة الجديدة:</span>
                    <img id="image_preview" src="" alt="preview" class="h-36 w-64 rounded-xl object-cover border border-slate-200 shadow-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">ترتيب الظهور</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $destination->sort_order) }}" class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $destination->is_active)) class="rounded text-blue-600 focus:ring-blue-500 h-5 w-5">
                        <span class="text-sm font-bold text-slate-800">تفعيل ظهور البطاقة في الصفحة الرئيسية</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('platform.destinations.index') }}" class="btn btn-light text-sm font-bold">إلغاء</a>
                <button type="submit" class="btn btn-primary px-5 py-2 text-sm font-bold shadow-sm">
                    💾 حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('destination_image_input')?.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('image_preview').src = e.target.result;
            document.getElementById('image_preview_box').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('image_preview_box').classList.add('hidden');
    }
});
</script>
@endsection

