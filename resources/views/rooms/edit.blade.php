@extends('layouts.app')

@section('content')

<div class="bg-white shadow-md rounded-lg p-6 max-w-xl mx-auto">

    <h2 class="text-2xl font-bold text-gray-700 mb-6">تعديل بيانات الغرفة</h2>

    <!-- عرض الأخطاء -->
    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            <ul class="list-disc pr-5">
                @foreach ($errors->all() as $error)
                    <li class="text-sm">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('rooms.update', $room->id) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')

        <!-- رقم الغرفة -->
        <div>
            <label class="block mb-1 font-semibold text-gray-700">رقم الغرفة:</label>
            <input type="text" name="room_number" value="{{ $room->room_number }}"
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- نوع الغرفة -->
        <div>
            <label class="block mb-1 font-semibold text-gray-700">نوع الغرفة:</label>
            <select name="room_type_id"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                @foreach($types as $type)
                    <option value="{{ $type->id }}" {{ $room->room_type_id == $type->id ? 'selected' : '' }}>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- السعر بالعملتين (منفصلان وغير مرتبطان بسعر الصرف) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block mb-1 font-semibold text-gray-700">السعر الليلي (بالليرة السورية ل.س):</label>
                <div class="relative">
                    <input type="number" step="0.01" name="price" value="{{ old('price', $room->price) }}" required
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
                    <span class="absolute left-3 top-2.5 text-xs text-gray-500">ل.س</span>
                </div>
            </div>

            <div>
                <label class="block mb-1 font-semibold text-gray-700">السعر الليلي (بالدولار الأمريكي $):</label>
                <div class="relative">
                    <input type="number" step="0.01" name="price_usd" value="{{ old('price_usd', $room->price_usd ?? 0) }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 p-2 border">
                    <span class="absolute left-3 top-2.5 text-xs text-gray-500">$ USD</span>
                </div>
                <small class="text-xs text-gray-500">سعر منفصل مخصص للمحاسبة بالدولار</small>
            </div>
        </div>

        <!-- الحالة -->
        <div>
            <label class="block mb-1 font-semibold text-gray-700">الحالة:</label>
            <select name="status"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="available" {{ $room->status == 'available' ? 'selected' : '' }}>متاحة</option>
                <option value="reserved" {{ $room->status == 'reserved' ? 'selected' : '' }}>محجوزة</option>
                <option value="maintenance" {{ $room->status == 'maintenance' ? 'selected' : '' }}>صيانة</option>
            </select>
        </div>

        <!-- إدارة صورة الغرفة -->
        <div class="border rounded-lg p-4 bg-gray-50">
            <label class="block mb-2 font-semibold text-gray-700">صورة الغرفة:</label>

            <input type="hidden" name="remove_image" id="remove_image_input" value="0">

            @if($room->image_url)
                <div id="current_image_container" class="relative inline-block mb-3">
                    <img id="current_room_img" src="{{ $room->image_url }}"
                         class="w-48 h-36 object-cover rounded-lg shadow border border-gray-200">

                    <!-- أيقونة حذف الصورة الصغيرة فوق الصورة -->
                    <button type="button" id="btn_delete_room_image"
                            class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg transition-transform hover:scale-110"
                            title="حذف هذه الصورة">
                        <span class="text-xs">🗑️</span>
                    </button>
                </div>

                <div id="image_delete_badge" class="hidden mb-3 bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg text-sm flex items-center justify-between">
                    <span>⚠️ تم تحديد الصورة للحذف، سيتم إزالتها عند حفظ التعديلات.</span>
                    <button type="button" id="btn_undo_delete_image" class="text-blue-600 font-bold hover:underline text-xs">
                        تراجع
                    </button>
                </div>
            @else
                <p id="no_img_text" class="text-gray-500 text-sm mb-3">لا توجد صورة حالية للغرفة.</p>
            @endif

            <!-- رفع صورة جديدة أو استبدال -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">رفع صورة جديدة أو استبدال الحالية:</label>
                <input type="file" name="image" id="room_image_file" accept="image/*"
                       class="w-full border-gray-300 rounded-lg shadow-sm bg-white p-2">
            </div>

            <!-- معاينة الصورة الجديدة المختارة -->
            <div id="new_image_preview_box" class="hidden mt-3">
                <span class="text-xs text-green-700 font-semibold block mb-1">معاينة الصورة الجديدة المختارة:</span>
                <img id="new_image_preview" src="" class="w-48 h-36 object-cover rounded-lg shadow border border-green-300">
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnDelete = document.getElementById('btn_delete_room_image');
            const btnUndo = document.getElementById('btn_undo_delete_image');
            const removeInput = document.getElementById('remove_image_input');
            const imgContainer = document.getElementById('current_image_container');
            const badge = document.getElementById('image_delete_badge');
            const fileInput = document.getElementById('room_image_file');
            const previewBox = document.getElementById('new_image_preview_box');
            const previewImg = document.getElementById('new_image_preview');

            if (btnDelete) {
                btnDelete.addEventListener('click', function() {
                    removeInput.value = '1';
                    imgContainer.classList.add('opacity-40');
                    btnDelete.classList.add('hidden');
                    if (badge) badge.classList.remove('hidden');
                });
            }

            if (btnUndo) {
                btnUndo.addEventListener('click', function() {
                    removeInput.value = '0';
                    imgContainer.classList.remove('opacity-40');
                    if (btnDelete) btnDelete.classList.remove('hidden');
                    if (badge) badge.classList.add('hidden');
                });
            }

            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(evt) {
                            previewImg.src = evt.target.result;
                            previewBox.classList.remove('hidden');
                            removeInput.value = '0';
                            if (badge) badge.classList.add('hidden');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        previewBox.classList.add('hidden');
                    }
                });
            }
        });
        </script>

        <!-- زر الحفظ -->
        <div class="text-center">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-6 rounded-lg shadow">
                حفظ التعديلات
            </button>
        </div>

    </form>

</div>

@endsection
