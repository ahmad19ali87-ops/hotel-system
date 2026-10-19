@extends('layouts.app')

@section('content')
<div class="container py-4">
    {{-- Header Banner --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #003580 0%, #0f172a 60%, #1e3a8a 100%);">
        <div class="card-body p-4 text-white">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center bg-white/10 rounded-3 p-3 border border-white/20 shadow-sm" style="width: 58px; height: 58px;">
                        <span class="fs-2">📱</span>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-1 text-white">لوحة إدارة تطبيق الجوال</h3>
                        <p class="text-white-50 mb-0 small">
                            تخصيص وإدارة محتوى تطبيق الهاتف، صور بطاقات استكشف الوجهات والمحافظات، والبنرات الإعلانية (أشهر الواجهات والكلمات الترويجية)
                        </p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-white/10 text-white px-3 py-2 rounded-pill border border-white/20">
                        📍 {{ $destinations->count() }} وجهة
                    </span>
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
                        📢 {{ $banners->count() }} إعلان وبانر
                    </span>
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 rounded-pill fw-bold">
                        👥 {{ $guestsCount }} مشترك مسجل
                    </span>
                    <span class="badge bg-light text-dark px-3 py-2 rounded-pill fw-bold">
                        ✉️ {{ $broadcastMessages->count() }} تعميم
                    </span>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fw-bold">
                        🔔 {{ $broadcastNotifications->count() }} إشعار
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Session Status Alert --}}
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center gap-2" role="alert">
            <span class="fs-5">✅</span>
            <div class="fw-bold">{{ session('status') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0" role="alert">
            <div class="fw-bold mb-1">يرجى تصحيح الأخطاء التالية:</div>
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Tabs Navigation --}}
    @php
        $activeTab = request('tab', 'destinations');
    @endphp
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <ul class="nav nav-pills nav-fill gap-2" id="appManagerTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold rounded-3 d-flex align-items-center justify-content-center gap-2 {{ $activeTab === 'destinations' ? 'active' : '' }}" 
                            id="destinations-tab" data-bs-toggle="pill" data-bs-target="#destinations-pane" type="button" role="tab"
                            style="{{ $activeTab === 'destinations' ? 'background-color: #003580;' : '' }}">
                        <span class="fs-5">📍</span>
                        <span>قسم استكشف الوجهات ({{ $destinations->count() }})</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold rounded-3 d-flex align-items-center justify-content-center gap-2 {{ $activeTab === 'banners' ? 'active' : '' }}" 
                            id="banners-tab" data-bs-toggle="pill" data-bs-target="#banners-pane" type="button" role="tab"
                            style="{{ $activeTab === 'banners' ? 'background-color: #003580;' : '' }}">
                        <span class="fs-5">📢</span>
                        <span>أشهر الواجهات والإعلانات ({{ $banners->count() }})</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold rounded-3 d-flex align-items-center justify-content-center gap-2 {{ $activeTab === 'broadcast-messages' ? 'active' : '' }}" 
                            id="broadcast-messages-tab" data-bs-toggle="pill" data-bs-target="#broadcast-messages-pane" type="button" role="tab"
                            style="{{ $activeTab === 'broadcast-messages' ? 'background-color: #003580;' : '' }}">
                        <span class="fs-5">✉️</span>
                        <span>رسائل للمشتركين (بدون رد) ({{ $broadcastMessages->count() }})</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold rounded-3 d-flex align-items-center justify-content-center gap-2 {{ $activeTab === 'broadcast-events' ? 'active' : '' }}" 
                            id="broadcast-events-tab" data-bs-toggle="pill" data-bs-target="#broadcast-events-pane" type="button" role="tab"
                            style="{{ $activeTab === 'broadcast-events' ? 'background-color: #003580;' : '' }}">
                        <span class="fs-5">🔔</span>
                        <span>إرسال إشعار بحدث للجميع ({{ $broadcastNotifications->count() }})</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    {{-- Tab Contents --}}
    <div class="tab-content" id="appManagerTabContent">
        
        {{-- =============================================================== --}}
        {{-- TAB 1: DESTINATIONS & PROVINCES --}}
        {{-- =============================================================== --}}
        <div class="tab-pane fade {{ $activeTab === 'destinations' ? 'show active' : '' }}" id="destinations-pane" role="tabpanel">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">بطاقات قسم استكشف أشهر الوجهات والمحافظات</h5>
                    <small class="text-muted">هذه البطاقات والصور تظهر أفقياً في الصفحة الرئيسية لتطبيق الجوال، وعند الضغط عليها يتم فلترة فنادق تلك المحافظة.</small>
                </div>
                <button type="button" class="btn btn-primary px-3 rounded-3 fw-bold d-flex align-items-center gap-2" style="background-color: #003580; border-color: #003580;" data-bs-toggle="modal" data-bs-target="#addDestinationModal">
                    <span>➕</span>
                    <span>إضافة وجهة جديدة للتطبيق</span>
                </button>
            </div>

            <div class="row g-3">
                @forelse($destinations as $d)
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                            {{-- Image Container with Preview --}}
                            <div class="position-relative" style="height: 160px; background-color: #1e293b;">
                                @if($d->image_url)
                                    <img src="{{ $d->image_url }}" alt="{{ $d->title }}" class="w-100 h-100 object-fit-cover">
                                @else
                                    <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white-50">
                                        <span class="fs-1">🖼️</span>
                                        <small class="mt-1">لا توجد صورة مخصصة (تستخدم الصورة التلقائية)</small>
                                    </div>
                                @endif
                                <div class="position-absolute top-0 end-0 p-2 d-flex gap-1">
                                    @if($d->is_active)
                                        <span class="badge bg-success shadow-sm">مفعل بالتطبيق</span>
                                    @else
                                        <span class="badge bg-secondary shadow-sm">معطل</span>
                                    @endif
                                    <span class="badge bg-dark bg-opacity-75 shadow-sm">ترتيب: {{ $d->sort_order }}</span>
                                </div>
                                <div class="position-absolute bottom-0 start-0 end-0 p-3" style="background: linear-gradient(to top, rgba(0,0,0,0.85), transparent);">
                                    <h5 class="text-white fw-bold mb-0 d-flex align-items-center gap-1.5">
                                        <span>{{ $d->title }}</span>
                                    </h5>
                                    @if($d->subtitle)
                                        <p class="text-white-50 small mb-0 text-truncate">{{ $d->subtitle }}</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Card Body --}}
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                                        <span>الفنادق المتوفرة بالمحافظة:</span>
                                        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold">
                                            {{ \App\Models\Hotel::where('province', 'LIKE', '%' . trim(str_replace(['مدينة', 'محافظة'], '', $d->title)) . '%')->count() }} فنادق
                                        </span>
                                    </div>
                                    <div class="small text-muted text-truncate">
                                        <span class="fw-semibold">رابط الفلتر:</span> <code>{{ $d->link ?: 'تلقائي' }}</code>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between pt-2 border-top gap-2">
                                    <form action="{{ route('admin.app-manager.destinations.toggle', $d) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $d->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-pill px-2.5">
                                            {{ $d->is_active ? 'تعطيل الظهور' : 'تفعيل الظهور' }}
                                        </button>
                                    </form>

                                    <div class="d-flex gap-1.5">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" 
                                                data-bs-toggle="modal" data-bs-target="#editDestinationModal{{ $d->id }}">
                                            تعديل والصورة ✏️
                                        </button>
                                        <form action="{{ route('admin.app-manager.destinations.destroy', $d) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الوجهة من التطبيق؟');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="حذف">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Edit Destination Modal --}}
                    <div class="modal fade" id="editDestinationModal{{ $d->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <form action="{{ route('admin.app-manager.destinations.update', $d) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold">تعديل بطاقة الوجهة ({{ $d->title }})</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">اسم الوجهة / المحافظة *</label>
                                            <input type="text" name="title" class="form-control rounded-3" value="{{ old('title', $d->title) }}" required placeholder="مثال: دمشق، اللاذقية، طرطوس">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">وصف قصير / عبارة ترويجية</label>
                                            <input type="text" name="subtitle" class="form-control rounded-3" value="{{ old('subtitle', $d->subtitle) }}" placeholder="مثال: عراقة الشرق والأسواق التاريخية">
                                        </div>

                                        {{-- Image Choice: File or URL --}}
                                        <div class="p-3 bg-light rounded-3 border mb-3">
                                            <label class="form-label fw-bold mb-2">صورة الوجهة في تطبيق الجوال</label>
                                            @if($d->image_url)
                                                <div class="mb-2 d-flex align-items-center gap-2">
                                                    <img src="{{ $d->image_url }}" alt="Preview" class="rounded-2 object-fit-cover" style="width: 70px; height: 50px;">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="delImg{{ $d->id }}">
                                                        <label class="form-check-label text-danger small" for="delImg{{ $d->id }}">
                                                            حذف الصورة الحالية واستخدام التلقائية
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            <div class="mb-2">
                                                <label class="form-label small text-muted">1. رفع صورة من الكمبيوتر:</label>
                                                <input type="file" name="image_file" class="form-control form-control-sm rounded-2" accept="image/*">
                                            </div>
                                            <div>
                                                <label class="form-label small text-muted">2. أو إدخال رابط صورة خارجي مباشر (URL):</label>
                                                <input type="url" name="image_url" class="form-control form-control-sm rounded-2" value="{{ old('image_url', str_starts_with($d->image ?? '', 'http') ? $d->image : '') }}" placeholder="https://images.unsplash.com/...">
                                            </div>
                                        </div>

                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label fw-bold small">ترتيب الظهور</label>
                                                <input type="number" name="sort_order" class="form-control rounded-3" value="{{ old('sort_order', $d->sort_order) }}">
                                            </div>
                                            <div class="col-6 d-flex align-items-end">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="act{{ $d->id }}" {{ $d->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-bold small" for="act{{ $d->id }}">مفعلة وتظهر بالتطبيق</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background-color: #003580; border-color: #003580;">حفظ التعديلات</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                            <span class="fs-1 mb-2">📍</span>
                            <h5 class="fw-bold">لا توجد وجهات مسجلة حالياً</h5>
                            <p class="text-muted">اضغط على زر "إضافة وجهة جديدة" لإضافة وجهات سياحية بصورها وأسمائها إلى تطبيق الجوال.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- =============================================================== --}}
        {{-- TAB 2: PROMOTIONAL ADS & BANNERS (أشهر الواجهات والإعلانات) --}}
        {{-- =============================================================== --}}
        <div class="tab-pane fade {{ $activeTab === 'banners' ? 'show active' : '' }}" id="banners-pane" role="tabpanel">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">قسم أشهر الواجهات والبنرات والكلمات الإعلانية</h5>
                    <small class="text-muted">تظهر هذه البنرات الإعلانية في الصفحة الرئيسية للتطبيق مع الصور الترويجية والشعارات والكلمات التسويقية.</small>
                </div>
                <button type="button" class="btn btn-warning px-3 rounded-3 fw-bold d-flex align-items-center gap-2 text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#addBannerModal">
                    <span>📢</span>
                    <span>إضافة إعلان أو بانر جديد</span>
                </button>
            </div>

            <div class="row g-4">
                @forelse($banners as $b)
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                            {{-- Simulated Phone Banner Mockup --}}
                            <div class="position-relative overflow-hidden" style="height: 190px; background: {{ $b->bg_gradient ?: '#003580' }};">
                                @if($b->image_url)
                                    <img src="{{ $b->image_url }}" alt="{{ $b->title }}" class="w-100 h-100 object-fit-cover position-absolute top-0 start-0">
                                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.3) 60%, rgba(0,0,0,0.1) 100%);"></div>
                                @endif

                                {{-- Badge --}}
                                <div class="position-absolute top-0 end-0 p-3">
                                    <span class="badge bg-warning text-dark px-2.5 py-1.5 rounded-pill fw-bold shadow-sm" style="font-size: 0.75rem;">
                                        ✨ {{ $b->badge }}
                                    </span>
                                </div>

                                {{-- Active Indicator --}}
                                <div class="position-absolute top-0 start-0 p-3">
                                    @if($b->is_active)
                                        <span class="badge bg-success shadow-sm">مباشر على التطبيق</span>
                                    @else
                                        <span class="badge bg-secondary shadow-sm">متوقف</span>
                                    @endif
                                </div>

                                {{-- Text Overlay (الكلمات الإعلانية) --}}
                                <div class="position-absolute bottom-0 start-0 end-0 p-3 text-white">
                                    <h6 class="fw-bold mb-1 text-white" style="font-size: 1.05rem; text-shadow: 0 2px 4px rgba(0,0,0,0.6);">
                                        {{ $b->title }}
                                    </h6>
                                    @if($b->subtitle)
                                        <p class="small text-white-50 mb-0 line-clamp-2" style="font-size: 0.8rem; line-height: 1.35; text-shadow: 0 1px 3px rgba(0,0,0,0.6);">
                                            {{ $b->subtitle }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- Actions and Details --}}
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div class="mb-3 small">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">الربط التفاعلي:</span>
                                        <span class="badge bg-light text-dark border">
                                            @if($b->link_type === 'destination')
                                                📍 فلترة مدينة ({{ $b->link_value }})
                                            @elseif($b->link_type === 'hotel')
                                                🏨 فندق محدد (ID: {{ $b->link_value }})
                                            @elseif($b->link_type === 'url')
                                                🔗 رابط خارجي
                                            @else
                                                عرض فقط بدون رابط
                                            @endif
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">ترتيب الظهور:</span>
                                        <span class="fw-bold">{{ $b->sort_order }}</span>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between pt-2 border-top gap-2">
                                    <form action="{{ route('admin.app-manager.banners.toggle', $b) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $b->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-pill px-2.5">
                                            {{ $b->is_active ? 'إيقاف الإعلان' : 'تفعيل الإعلان' }}
                                        </button>
                                    </form>

                                    <div class="d-flex gap-1.5">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" 
                                                data-bs-toggle="modal" data-bs-target="#editBannerModal{{ $b->id }}">
                                            تعديل والكلمات ✏️
                                        </button>
                                        <form action="{{ route('admin.app-manager.banners.destroy', $b) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الإعلان من التطبيق؟');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="حذف">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Edit Banner Modal --}}
                    <div class="modal fade" id="editBannerModal{{ $b->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <form action="{{ route('admin.app-manager.banners.update', $b) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold">تعديل الإعلان والكلمات الإعلانية</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="row g-3">
                                            <div class="col-md-8">
                                                <label class="form-label fw-bold">الكلمات الإعلانية الرئيسية (العنوان) *</label>
                                                <input type="text" name="title" class="form-control rounded-3" value="{{ old('title', $b->title) }}" required placeholder="مثال: أشهر واجهات الاصطياف في اللاذقية 🏖️">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">الشارة التسويقية</label>
                                                <input type="text" name="badge" class="form-control rounded-3" value="{{ old('badge', $b->badge) }}" placeholder="مثال: أشهر الواجهات، عرض حصري">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-bold">الكلمات الإعلانية التوضيحية (النص والشرح التسويقي)</label>
                                                <textarea name="subtitle" class="form-control rounded-3" rows="3" placeholder="اكتب النص التسويقي الجذاب للنزلاء والزبائن...">{{ old('subtitle', $b->subtitle) }}</textarea>
                                            </div>

                                            {{-- Image Selection --}}
                                            <div class="col-12">
                                                <div class="p-3 bg-light rounded-3 border">
                                                    <label class="form-label fw-bold mb-2">صورة البانر الإعلاني</label>
                                                    @if($b->image_url)
                                                        <div class="mb-2 d-flex align-items-center gap-2">
                                                            <img src="{{ $b->image_url }}" alt="Preview" class="rounded-2 object-fit-cover" style="width: 100px; height: 50px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="delBImg{{ $b->id }}">
                                                                <label class="form-check-label text-danger small" for="delBImg{{ $b->id }}">حذف الصورة</label>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    <div class="row g-2">
                                                        <div class="col-md-6">
                                                            <label class="form-label small text-muted">1. رفع صورة من الجهاز:</label>
                                                            <input type="file" name="image_file" class="form-control form-control-sm rounded-2" accept="image/*">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label small text-muted">2. أو رابط صورة مباشر (URL):</label>
                                                            <input type="url" name="image_url" class="form-control form-control-sm rounded-2" value="{{ old('image_url', str_starts_with($b->image ?? '', 'http') ? $b->image : '') }}" placeholder="https://images.unsplash.com/...">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Link Action --}}
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small">إجراء الضغط على الإعلان</label>
                                                <select name="link_type" class="form-select rounded-3">
                                                    <option value="destination" {{ $b->link_type === 'destination' ? 'selected' : '' }}>فلترة مدينة / وجهة محددة</option>
                                                    <option value="hotel" {{ $b->link_type === 'hotel' ? 'selected' : '' }}>فتح صفحة فندق معين</option>
                                                    <option value="url" {{ $b->link_type === 'url' ? 'selected' : '' }}>رابط خارجي</option>
                                                    <option value="none" {{ $b->link_type === 'none' ? 'selected' : '' }}>بدون إجراء (عرض فقط)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small">قيمة الرابط / اسم المدينة / معرف الفندق</label>
                                                <input type="text" name="link_value" class="form-control rounded-3" value="{{ old('link_value', $b->link_value) }}" placeholder="مثال: اللاذقية أو دمشق">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small">ترتيب الظهور</label>
                                                <input type="number" name="sort_order" class="form-control rounded-3" value="{{ old('sort_order', $b->sort_order) }}">
                                            </div>
                                            <div class="col-md-6 d-flex align-items-end">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="bAct{{ $b->id }}" {{ $b->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-bold small" for="bAct{{ $b->id }}">مفعل ويظهر في تطبيق الجوال</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                                        <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">حفظ التعديلات</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                            <span class="fs-1 mb-2">📢</span>
                            <h5 class="fw-bold">لا توجد إعلانات أو بنرات مسجلة</h5>
                            <p class="text-muted">اضغط على زر "إضافة إعلان أو بانر جديد" لإضافة إعلانات وكلمات ترويجية لقسم أشهر الواجهات في التطبيق.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- =============================================================== --}}
        {{-- TAB 3: BROADCAST MESSAGES (بدون إمكانية الرد) --}}
        {{-- =============================================================== --}}
        <div class="tab-pane fade {{ $activeTab === 'broadcast-messages' ? 'show active' : '' }}" id="broadcast-messages-pane" role="tabpanel">
            <div class="row g-4">
                {{-- Left Column: Compose Broadcast Message Form --}}
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="fs-3">✉️</span>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">إرسال رسالة تعميمية لجميع المشتركين</h5>
                                    <small class="text-muted">تصل لصندوق الرسائل في التطبيق بدون إمكانية الرد</small>
                                </div>
                            </div>
                            <div class="alert alert-warning border-0 rounded-3 p-3 d-flex align-items-start gap-2 mb-0 mt-3" style="background-color: #FEF3C7; color: #92400E;">
                                <span class="fs-5">🔒</span>
                                <div class="small">
                                    <strong>خاصية عدم إمكانية الرد مفعلة:</strong>
                                    ستصل هذه الرسالة إلى جميع المشتركين المسجلين في التطبيق في تبويب الرسائل والمحادثات مع قفل حقل الرد ومنع استقبال أي ردود عليها.
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <form action="{{ route('admin.app-manager.broadcast-message.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">عنوان / موضوع التعميم *</label>
                                    <input type="text" name="subject" class="form-control rounded-3" required 
                                           placeholder="مثال: تعميم رسمي: تحديث شروط ومواعيد تسجيل الوصول" 
                                           value="{{ old('subject') }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">الفئة المستهدفة بالرسالة *</label>
                                    <select name="target" class="form-select rounded-3">
                                        <option value="guests" selected>👥 جميع المشتركين والنزلاء المسجلين ({{ $guestsCount }} مشترك)</option>
                                        <option value="everyone">🌐 جميع مستخدمي النظام والنزلاء ({{ $totalUsersCount }} مستخدم)</option>
                                        <option value="staff">👔 موظفو وإدارات الفنادق فقط</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">نص الرسالة التعميمية بالكامل *</label>
                                    <textarea name="body" class="form-control rounded-3" rows="5" required 
                                              placeholder="اكتب هنا نص الرسالة التي تود توجيهها لجميع المشتركين... نرجو من كافة النزلاء الكرام الالتزام بـ...">{{ old('body') }}</textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold small">إرفاق صورة أو مستند (اختياري)</label>
                                    <input type="file" name="attachment" class="form-control rounded-3" accept="image/*,.pdf">
                                    <div class="form-text small">يمكن إرفاق صورة توضيحية أو تعميم رسمي بصيغة PDF أو صورة.</div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm"
                                        style="background-color: #003580; border-color: #003580;">
                                    <span>📢</span>
                                    <span>إرسال الرسالة للجميع الآن (بدون إمكانية الرد)</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Broadcast History Logs --}}
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">سجل الرسائل التعميمية المرسلة</h5>
                                <small class="text-muted">الرسائل السابقة التي تم إرسالها لجميع المشتركين</small>
                            </div>
                            <span class="badge bg-primary px-3 py-2 rounded-pill" style="background-color: #003580 !important;">
                                {{ $broadcastMessages->count() }} رسالة
                            </span>
                        </div>
                        <div class="card-body p-4 pt-2">
                            @forelse($broadcastMessages as $bMsg)
                                <div class="card border border-light-subtle rounded-3 shadow-none mb-3 overflow-hidden">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="badge bg-warning text-dark rounded-pill fw-bold px-2 py-1">
                                                        📢 تعميم رسمي
                                                    </span>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1 small">
                                                        🔒 الرد معطل
                                                    </span>
                                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1 small">
                                                        {{ $bMsg->recipients_count }} مستلم
                                                    </span>
                                                </div>
                                                <h6 class="fw-bold mt-2 mb-1 text-dark">{{ $bMsg->title }}</h6>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted d-block">{{ $bMsg->created_at->format('Y/m/d H:i') }}</small>
                                                <small class="text-muted">{{ $bMsg->created_at->diffForHumans() }}</small>
                                            </div>
                                        </div>
                                        <p class="text-secondary small mb-2" style="white-space: pre-line;">{{ $bMsg->body }}</p>
                                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-light">
                                            <small class="text-muted">
                                                المرسل: <strong>{{ $bMsg->sender?->name ?? 'إدارة التطبيق' }}</strong>
                                                &bull; الفئة: 
                                                <span class="badge bg-light text-dark border">
                                                    {{ $bMsg->target === 'guests' ? 'المشتركون والنزلاء' : ($bMsg->target === 'everyone' ? 'الجميع' : 'الموظفون') }}
                                                </span>
                                            </small>
                                            <form action="{{ route('admin.app-manager.broadcasts.destroy', $bMsg->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا السجل؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-pill px-2 py-1">
                                                    <span class="small">🗑️ حذف من السجل</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <span class="fs-1 text-muted">📭</span>
                                    <h6 class="fw-bold mt-2">لا توجد رسائل تعميمية سابقة</h6>
                                    <p class="text-muted small">استخدم النموذج المقابل لإرسال أول رسالة تعميمية لكافة المشتركين في تطبيق الجوال.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- =============================================================== --}}
        {{-- TAB 4: BROADCAST EVENT NOTIFICATIONS (إرسال إشعار بحدث للجميع) --}}
        {{-- =============================================================== --}}
        <div class="tab-pane fade {{ $activeTab === 'broadcast-events' ? 'show active' : '' }}" id="broadcast-events-pane" role="tabpanel">
            <div class="row g-4">
                {{-- Left Column: Compose Event Notification Form --}}
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="fs-3">🔔</span>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">إرسال إشعار وتنبيه بحدث معين</h5>
                                    <small class="text-muted">يصل كإشعار فوري وتنبيه على شاشات هواتف المشتركين</small>
                                </div>
                            </div>
                            <div class="alert alert-info border-0 rounded-3 p-3 d-flex align-items-start gap-2 mb-0 mt-3" style="background-color: #E0F2FE; color: #0369A1;">
                                <span class="fs-5">📱</span>
                                <div class="small">
                                    <strong>إشعار فوري داخل وخارج التطبيق:</strong>
                                    سيتلقى المشتركون إشعاراً في هواتفهم (Push Notification) مع نغمة التنبيه وفي قائمة الإشعارات داخل التطبيق مع رابط الحدث.
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <form action="{{ route('admin.app-manager.broadcast-notification.store') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">عنوان الإشعار / اسم الحدث *</label>
                                    <input type="text" name="title" class="form-control rounded-3" required 
                                           placeholder="مثال: 🎉 انطلاق مهرجان الصيف السياحي في اللاذقية" 
                                           value="{{ old('title') }}">
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-md-7">
                                        <label class="form-label fw-bold small">نوع / تصنيف الحدث *</label>
                                        <select name="event_type" class="form-select rounded-3">
                                            <option value="promo" selected>🎟️ حدث ترويجي وعروض خاصة</option>
                                            <option value="tourism_event">🌴 فعالية سياحية ومهرجان</option>
                                            <option value="general_announcement">📢 إعلان وتعميم عام</option>
                                            <option value="seasonal">⭐ مناسبة موسمية وعطلات</option>
                                            <option value="urgent_alert">⚡ تنبيه هام أو صيانة مجدولة</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold small">أيقونة الإشعار</label>
                                        <select name="icon" class="form-select rounded-3">
                                            <option value="🎉" selected>🎉 احتفال / حدث</option>
                                            <option value="📢">📢 بوق إعلان</option>
                                            <option value="🔔">🔔 جرس تنبيه</option>
                                            <option value="🌴">🌴 سياحة وبحر</option>
                                            <option value="⭐">⭐ نجمة مميزة</option>
                                            <option value="🏷️">🏷️ خصم وعرض</option>
                                            <option value="🔥">🔥 عرض حار</option>
                                            <option value="🛎️">🛎️ خدمة فندقية</option>
                                            <option value="⚡">⚡ عاجل</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">الفئة المستهدفة بالإشعار *</label>
                                    <select name="target" class="form-select rounded-3">
                                        <option value="guests" selected>👥 جميع المشتركين والنزلاء ({{ $guestsCount }} مشترك)</option>
                                        <option value="everyone">🌐 جميع مستخدمي النظام والنزلاء ({{ $totalUsersCount }} مستخدم)</option>
                                        <option value="staff">👔 موظفو الفنادق فقط</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">نص وتفاصيل الحدث / الإشعار *</label>
                                    <textarea name="body" class="form-control rounded-3" rows="4" required 
                                              placeholder="يسرنا إعلامكم ببدء فعاليات موسم الاصطياف مع خصومات تصل إلى 30% على كافة الحجوزات عبر التطبيق...">{{ old('body') }}</textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold small">رابط أو إجراء مرتبط بالحدث (اختياري)</label>
                                    <input type="text" name="link" class="form-control rounded-3" 
                                           placeholder="مثال: /offers أو اسم فندق أو رابط خارجي">
                                    <div class="form-text small">عند ضغط المستخدم على الإشعار، يمكن توجيهه إلى صفحة العروض أو فندق محدد.</div>
                                </div>

                                <button type="submit" class="btn btn-warning w-100 py-3 rounded-pill fw-bold text-dark d-flex align-items-center justify-content-center gap-2 shadow-sm">
                                    <span>🔔</span>
                                    <span>إرسال الإشعار والتنبيه لجميع المشتركين الآن</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Event Notifications History Logs --}}
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">سجل الإشعارات والأحداث المرسلة</h5>
                                <small class="text-muted">الإشعارات والتنبيهات التي تم بثها لجميع المستخدمين</small>
                            </div>
                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
                                {{ $broadcastNotifications->count() }} إشعار
                            </span>
                        </div>
                        <div class="card-body p-4 pt-2">
                            @forelse($broadcastNotifications as $bNotif)
                                <div class="card border border-light-subtle rounded-3 shadow-none mb-3 overflow-hidden">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div class="d-flex align-items-start gap-2">
                                                <div class="bg-light rounded-circle p-2 text-center" style="width: 44px; height: 44px; line-height: 28px;">
                                                    <span class="fs-4">{{ $bNotif->icon ?: '🔔' }}</span>
                                                </div>
                                                <div>
                                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                                        <span class="badge bg-primary text-white rounded-pill px-2 py-1 small" style="background-color: #003580 !important;">
                                                            {{ $bNotif->event_type ?: 'حدث عام' }}
                                                        </span>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 small">
                                                            تم البث لـ {{ $bNotif->recipients_count }} مستخدم
                                                        </span>
                                                    </div>
                                                    <h6 class="fw-bold mt-2 mb-1 text-dark">{{ $bNotif->title }}</h6>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted d-block">{{ $bNotif->created_at->format('Y/m/d H:i') }}</small>
                                                <small class="text-muted">{{ $bNotif->created_at->diffForHumans() }}</small>
                                            </div>
                                        </div>
                                        <p class="text-secondary small mb-2 ms-5" style="white-space: pre-line;">{{ $bNotif->body }}</p>
                                        @if($bNotif->link)
                                            <div class="ms-5 mb-2">
                                                <span class="badge bg-light text-primary border">🔗 الرابط: {{ $bNotif->link }}</span>
                                            </div>
                                        @endif
                                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-light">
                                            <small class="text-muted">
                                                المرسل: <strong>{{ $bNotif->sender?->name ?? 'إدارة التطبيق' }}</strong>
                                                &bull; الفئة: 
                                                <span class="badge bg-light text-dark border">
                                                    {{ $bNotif->target === 'guests' ? 'المشتركون والنزلاء' : ($bNotif->target === 'everyone' ? 'الجميع' : 'الموظفون') }}
                                                </span>
                                            </small>
                                            <form action="{{ route('admin.app-manager.broadcasts.destroy', $bNotif->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا السجل؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-pill px-2 py-1">
                                                    <span class="small">🗑️ حذف من السجل</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <span class="fs-1 text-muted">🔕</span>
                                    <h6 class="fw-bold mt-2">لا توجد إشعارات أحداث سابقة</h6>
                                    <p class="text-muted small">استخدم النموذج المقابل لإرسال تنبيه أو إشعار فوري بحدث معين لكافة المشتركين في التطبيق.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- =============================================================== --}}
{{-- MODAL: ADD DESTINATION --}}
{{-- =============================================================== --}}
<div class="modal fade" id="addDestinationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('admin.app-manager.destinations.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">إضافة وجهة جديدة لقسم استكشف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">اسم الوجهة / المحافظة *</label>
                        <input type="text" name="title" class="form-control rounded-3" required placeholder="مثال: دمشق، اللاذقية، طرطوس، حلب">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">وصف قصير / عبارة ترويجية</label>
                        <input type="text" name="subtitle" class="form-control rounded-3" placeholder="مثال: شواطئ البحر والمنتجعات الساحلية">
                    </div>

                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <label class="form-label fw-bold mb-2">صورة الوجهة في تطبيق الجوال</label>
                        <div class="mb-2">
                            <label class="form-label small text-muted">1. رفع صورة من الكمبيوتر:</label>
                            <input type="file" name="image_file" class="form-control form-control-sm rounded-2" accept="image/*">
                        </div>
                        <div>
                            <label class="form-label small text-muted">2. أو إدخال رابط صورة خارجي مباشر (URL):</label>
                            <input type="url" name="image_url" class="form-control form-control-sm rounded-2" placeholder="https://images.unsplash.com/photo-...">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small">ترتيب الظهور</label>
                            <input type="number" name="sort_order" class="form-control rounded-3" value="{{ $destinations->count() + 1 }}">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="actNew" checked>
                                <label class="form-check-label fw-bold small" for="actNew">مفعلة وتظهر بالتطبيق</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background-color: #003580; border-color: #003580;">إضافة الوجهة الآن</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- =============================================================== --}}
{{-- MODAL: ADD BANNER / AD --}}
{{-- =============================================================== --}}
<div class="modal fade" id="addBannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('admin.app-manager.banners.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">إضافة إعلان أو بانر ترويجي لتطبيق الجوال</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">الكلمات الإعلانية الرئيسية (العنوان) *</label>
                            <input type="text" name="title" class="form-control rounded-3" required placeholder="مثال: أشهر واجهات الاصطياف في اللاذقية 🏖️">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">الشارة التسويقية</label>
                            <input type="text" name="badge" class="form-control rounded-3" value="أشهر الواجهات" placeholder="مثال: أشهر الواجهات، عرض حصري">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">الكلمات الإعلانية التوضيحية (النص والشرح التسويقي)</label>
                            <textarea name="subtitle" class="form-control rounded-3" rows="3" placeholder="مثال: احجز إقامتك الآن بأفضل الأسعار واستمتع بإطلالة بحرية وخصومات حصرية..."></textarea>
                        </div>

                        {{-- Image Selection --}}
                        <div class="col-12">
                            <div class="p-3 bg-light rounded-3 border">
                                <label class="form-label fw-bold mb-2">صورة الإعلان في التطبيق</label>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">1. رفع صورة من الجهاز:</label>
                                        <input type="file" name="image_file" class="form-control form-control-sm rounded-2" accept="image/*">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">2. أو رابط صورة مباشر (URL):</label>
                                        <input type="url" name="image_url" class="form-control form-control-sm rounded-2" placeholder="https://images.unsplash.com/photo-...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Link Action --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">إجراء الضغط على الإعلان</label>
                            <select name="link_type" class="form-select rounded-3">
                                <option value="destination">فلترة مدينة / وجهة محددة</option>
                                <option value="hotel">فتح صفحة فندق معين</option>
                                <option value="url">رابط خارجي</option>
                                <option value="none">بدون إجراء (عرض فقط)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">قيمة الرابط / اسم المدينة / معرف الفندق</label>
                            <input type="text" name="link_value" class="form-control rounded-3" placeholder="مثال: اللاذقية أو دمشق">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">ترتيب الظهور</label>
                            <input type="number" name="sort_order" class="form-control rounded-3" value="{{ $banners->count() + 1 }}">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="bActNew" checked>
                                <label class="form-check-label fw-bold small" for="bActNew">مفعل ويظهر في تطبيق الجوال</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">نشر الإعلان بالتطبيق</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

