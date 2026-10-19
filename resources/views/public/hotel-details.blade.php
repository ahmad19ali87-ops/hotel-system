@extends('layouts.app')

@section('content')
<div class="container my-4" style="max-width: 1000px;">
    @php
        $hotelPhones = $hotel->phones ? (json_decode($hotel->phones, true) ?: []) : [];
        if (!$hotelPhones && $hotel->phone) $hotelPhones = [$hotel->phone];
    @endphp

    <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm mb-3">← العودة إلى قائمة الفنادق</a>

    <div class="card shadow-sm mb-4 overflow-hidden">
        @if(count($images))
            <div class="row g-1">
                @foreach(array_slice($images, 0, 2) as $image)
                    <div class="col-md-6">
                        <img src="{{ $image }}" alt="{{ $hotel->name }}" class="w-100" style="height: 280px; object-fit: cover;">
                    </div>
                @endforeach
            </div>
        @elseif($hotel->logo_url)
            <img src="{{ $hotel->logo_url }}" alt="{{ $hotel->name }}" class="w-100" style="height: 280px; object-fit: cover;">
        @endif

        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h1 class="h2 fw-bold text-primary mb-1">{{ $hotel->name }}</h1>
                    @if($hotel->tagline)
                        <p class="fs-6 fw-bold text-primary-emphasis mb-2">✨ {{ $hotel->tagline }}</p>
                    @endif
                    <p class="text-muted mb-2">📍 {{ $hotel->province }}{{ $hotel->address ? '، ' . $hotel->address : '' }}</p>
                    @php
                        $mapsUrl = $hotel->google_maps_url ?: 'https://www.google.com/maps/search/?api=1&query=' . urlencode($hotel->name . ' ' . ($hotel->address ?? $hotel->province));
                    @endphp
                    <div class="mb-3">
                        <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm fw-bold d-inline-flex align-items-center gap-2 shadow-sm">
                            <span>📍</span>
                            <span>الموقع والاتجاهات على خرائط جوجل (Google Maps)</span>
                            <span class="badge bg-success-subtle text-success">ملاحة فورية ↗</span>
                        </a>
                    </div>
                    <div class="d-flex flex-wrap gap-3 text-muted">
                        @foreach($hotelPhones as $phone)
                            <span>📞 <a href="tel:{{ $phone }}" class="text-decoration-none">{{ $phone }}</a></span>
                        @endforeach
                        @if($hotel->email)
                            <span>✉️ <a href="mailto:{{ $hotel->email }}" class="text-decoration-none">{{ $hotel->email }}</a></span>
                        @endif
                    </div>
                </div>
                <div class="text-md-end">
                    <div class="mb-1">
                        <span class="fs-5">
                            @for($s = 0; $s < (int)$hotel->stars; $s++) ⭐ @endfor
                        </span>
                    </div>
                    <div class="text-warning fw-bold">
                        {{ $averageRating ? number_format($averageRating, 1) . ' / 5 ★' : 'لا توجد تقييمات' }}
                    </div>
                </div>
            </div>

            @if($hotel->description)
                <hr class="my-3">
                <p class="text-secondary leading-relaxed mb-0">{{ $hotel->description }}</p>
            @endif

            @php $amenities = $hotel->amenities_list; @endphp
            @if(count($amenities))
                <div class="mt-4 pt-3 border-top">
                    <h6 class="fw-bold text-secondary mb-2">✨ الميزات والخدمات المتوفرة:</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($amenities as $am)
                            <span class="badge bg-light text-dark border px-3 py-2 fs-6 fw-medium">{{ $am }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-4">
                <a href="{{ route('hotel.rooms', $hotel->slug) }}" class="btn btn-primary btn-lg fw-bold px-4">
                    🏨 استعراض الغرف المتاحة والحجز
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-warning">⭐ قيّم إقامتك</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('hotel.reviews.store', $hotel->slug) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">الاسم (اختياري)</label>
                            <input name="guest_name" value="{{ old('guest_name') }}" maxlength="120" class="form-control" placeholder="اسمك أو اترك فارغاً كزائر">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">التقييم</label>
                            <select name="rating" required class="form-select">
                                <option value="">اختر عدد النجوم...</option>
                                @for($rating = 5; $rating >= 1; $rating--)
                                    <option value="{{ $rating }}" @selected(old('rating') == $rating)>{{ str_repeat('⭐', $rating) }} ({{ $rating }} نجوم)</option>
                                @endfor
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">تعليقك</label>
                            <textarea name="comment" rows="3" maxlength="2000" class="form-control" placeholder="اكتب رأيك وتجربتك في الفندق...">{{ old('comment') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 fw-bold">إرسال التقييم</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-danger">📩 إرسال ملاحظة أو شكوى</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">تصل الشكوى بشكل مباشر وسري إلى إدارة الفندق فقط.</p>
                    <form method="POST" action="{{ route('hotel.complaints.store', $hotel->slug) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">الموضوع</label>
                            <input name="title" value="{{ old('title') }}" required maxlength="160" class="form-control" placeholder="موضوع الشكوى أو الملاحظة">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">تفاصيل الشكوى</label>
                            <textarea name="message" rows="4" required maxlength="4000" class="form-control" placeholder="يرجى توضيح تفاصيل الشكوى...">{{ old('message') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100 fw-bold">إرسال الشكوى لإدارة الفندق</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">💬 آراء وتقييمات الزوار ({{ $reviews->count() }})</h5>
        </div>
        <div class="card-body p-0">
            <ul class="list-group list-group-flush">
                @forelse($reviews as $review)
                    <li class="list-group-item p-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong>{{ $review->guest_name ?: 'زائر' }}</strong>
                            <span class="text-warning">
                                @for($r = 0; $r < $review->rating; $r++) ⭐ @endfor
                            </span>
                        </div>
                        @if($review->comment)
                            <p class="mb-1 text-secondary">{{ $review->comment }}</p>
                        @endif
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">{{ $review->created_at->format('Y-m-d') }}</small>
                            @if(auth()->check() && (in_array(auth()->user()->role, ['super_admin', 'platform_admin']) || ((int)auth()->user()->hotel_id === (int)$hotel->id && in_array(auth()->user()->role, ['hotel_admin', 'admin', 'reception']))))
                                <button class="btn btn-sm btn-outline-primary py-0 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#reply-form-{{ $review->id }}">
                                    {{ $review->hotel_reply ? '✏️ تعديل رد الإدارة' : '↩️ إضافة رد الإدارة' }}
                                </button>
                            @endif
                        </div>

                        @if($review->hotel_reply)
                            <div class="mt-2 p-3 bg-light rounded border-start border-4 border-primary">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="badge bg-primary">رد إدارة الفندق 🏨</span>
                                    @if($review->replied_at)
                                        <small class="text-muted">{{ $review->replied_at->format('Y-m-d H:i') }}</small>
                                    @endif
                                </div>
                                <p class="mb-0 text-dark">{{ $review->hotel_reply }}</p>
                            </div>
                        @endif

                        @if(auth()->check() && (in_array(auth()->user()->role, ['super_admin', 'platform_admin']) || ((int)auth()->user()->hotel_id === (int)$hotel->id && in_array(auth()->user()->role, ['hotel_admin', 'admin', 'reception']))))
                            <div class="collapse mt-2" id="reply-form-{{ $review->id }}">
                                <form method="POST" action="{{ route('hotel.reviews.reply', ['hotelSlug' => $hotel->slug, 'review' => $review->id]) }}" class="card card-body bg-light p-2">
                                    @csrf
                                    <label class="form-label small fw-bold mb-1">رد إدارة الفندق على النزيل:</label>
                                    <textarea name="hotel_reply" rows="2" class="form-control form-control-sm mb-2" required placeholder="اكتب رد وتوضيح إدارة الفندق هنا...">{{ $review->hotel_reply }}</textarea>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-sm btn-primary">حفظ الرد</button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="list-group-item text-center py-4 text-muted">
                        لا توجد تقييمات مسجلة بعد لهذا الفندق. كن أول من يقيّم!
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection