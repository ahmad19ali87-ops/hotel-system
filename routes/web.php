<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\CleaningController;
use App\Http\Controllers\Admin\ComplaintController;
use App\Http\Controllers\Admin\DashboardController as HotelDashboardController;
use App\Http\Controllers\Admin\HotelController as PlatformHotelController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\ReportController as HotelReportController;
use App\Http\Controllers\Admin\RoomTypeController as AdminRoomTypeController;
use App\Http\Controllers\Admin\UserController as HotelUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HotelFrontController;
use App\Http\Controllers\HousekeepingController;
use App\Http\Controllers\PublicHotelController;
use App\Http\Controllers\Admin\InvoiceController as InvoiceController;
use App\Http\Controllers\Admin\OfferController as HotelOfferController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Guest\GuestPortalController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicHotelController::class, 'index'])->name('home');
Route::get('/hotels', [PublicHotelController::class, 'index'])->name('hotels.list');
Route::get('/offers', [GuestPortalController::class, 'offers'])->name('offers.index');

Route::get('/downloads/windows', function () {
    $path = public_path('downloads/hotel_desktop_windows.zip');
    if (!file_exists($path)) {
        abort(404, 'ملف التطبيق غير متوفر حالياً');
    }
    return response()->download($path, 'hotel_desktop_windows.zip');
})->name('downloads.windows');

Route::get('/downloads/windows-exe', function () {
    $path = public_path('downloads/hotel_desktop.exe');
    if (!file_exists($path)) {
        abort(404, 'ملف التطبيق غير متوفر حالياً');
    }
    return response()->download($path, 'hotel_desktop.exe');
})->name('downloads.windows-exe');

Route::get('/downloads/android', function () {
    $path = public_path('downloads/hotel_mobile.apk');
    if (!file_exists($path)) {
        abort(404, 'ملف تطبيق الأندرويد غير متوفر حالياً');
    }
    return response()->download($path, 'hotel_mobile.apk', [
        'Content-Type' => 'application/vnd.android.package-archive',
    ]);
})->name('downloads.android');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->middleware('guest')->name('register');
Route::post('/register', [RegisterController::class, 'register'])->middleware('guest');
Route::get('/captcha/refresh', [RegisterController::class, 'refreshCaptcha'])->name('captcha.refresh');
Route::get('/verify-code', [RegisterController::class, 'showVerificationForm'])->name('verification.notice');
Route::post('/verify-code', [RegisterController::class, 'verifyCode'])->name('verification.verify');
Route::post('/verify-code/resend', [RegisterController::class, 'resendCode'])->name('verification.resend');

// Password Reset Routes (For registered guests & users)
Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->middleware('guest')->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware('guest')->name('password.email');
Route::get('/reset-password/{token?}', [ForgotPasswordController::class, 'showResetForm'])->middleware('guest')->name('password.reset');
Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->middleware('guest')->name('password.update');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/prompt', [GoogleAuthController::class, 'prompt'])->name('auth.google.prompt');
Route::post('/auth/google/one-tap', [GoogleAuthController::class, 'oneTap'])->name('auth.google.one-tap');
Route::post('/auth/google/submit', [GoogleAuthController::class, 'submitRealGoogle'])->name('auth.google.submit');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::get('/locale/{lang}', function (string $lang) {
    if (in_array($lang, ['ar', 'en'])) {
        session(['locale' => $lang]);
    }
    return redirect()->back();
})->name('locale.switch');

Route::middleware('hotel.scope')->prefix('/hotel/{hotelSlug}')->group(function () {
    Route::get('/', [HotelFrontController::class, 'index'])->name('hotel.front');
    Route::get('/rooms', [HotelFrontController::class, 'rooms'])->name('hotel.rooms');
    Route::get('/rooms/{id}', [HotelFrontController::class, 'showRoom'])->name('hotel.room.show');
    Route::post('/book', [HotelFrontController::class, 'book'])->middleware('throttle:10,1')->name('hotel.book');
    Route::post('/reviews', [HotelFrontController::class, 'storeReview'])->middleware('throttle:5,60')->name('hotel.reviews.store');
    Route::post('/reviews/{review}/reply', [HotelFrontController::class, 'replyReview'])->middleware(['auth', 'throttle:15,60'])->name('hotel.reviews.reply');
    Route::post('/complaints', [HotelFrontController::class, 'storeComplaint'])->middleware('throttle:5,60')->name('hotel.complaints.store');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->middleware('guest')->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware(['guest', 'throttle:6,1']);
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/invoice/{invoice}/verify', [InvoiceController::class, 'publicShow'])
    ->middleware('signed')
    ->name('invoice.public.legacy');
Route::get('/{hotelSlug}/invoices/{invoice}', [InvoiceController::class, 'publicShowByHotel'])
    ->where('hotelSlug', '[A-Za-z0-9-]+')
    ->whereNumber('invoice')
    ->name('invoice.by-hotel');

Route::middleware(['auth', 'role:super_admin'])->prefix('platform')->name('platform.')->group(function () {
    Route::get('/', fn () => redirect()->route('platform.hotels.index'))->name('dashboard');
    Route::resource('hotels', PlatformHotelController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::post('/hotels/{hotel}/toggle-visibility', [PlatformHotelController::class, 'toggleVisibility'])->name('hotels.toggle-visibility');
    Route::get('/hotels/{hotel}/backup', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('hotels.backup.index');
    Route::get('/hotels/{hotel}/backup/download', [\App\Http\Controllers\Admin\BackupController::class, 'download'])->name('hotels.backup.download');
    Route::post('/hotels/{hotel}/backup/restore', [\App\Http\Controllers\Admin\BackupController::class, 'restore'])->name('hotels.backup.restore');

    Route::resource('destinations', \App\Http\Controllers\Platform\DestinationController::class);
    Route::get('/app-manager', [\App\Http\Controllers\Admin\AppManagerController::class, 'index'])->name('app-manager.index');
    Route::get('/settings', [\App\Http\Controllers\Platform\SiteSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [\App\Http\Controllers\Platform\SiteSettingController::class, 'update'])->name('settings.update');
    Route::resource('admins', \App\Http\Controllers\Platform\PlatformAdminController::class)->except(['show']);

    // Security Monitoring Dashboard
    Route::get('/security', [\App\Http\Controllers\Platform\SecurityDashboardController::class, 'index'])->name('security.index');
    Route::post('/security/block', [\App\Http\Controllers\Platform\SecurityDashboardController::class, 'blockIp'])->name('security.block');
    Route::post('/security/unblock', [\App\Http\Controllers\Platform\SecurityDashboardController::class, 'unblockIp'])->name('security.unblock');
    Route::post('/security/unlock-user/{user}', [\App\Http\Controllers\Platform\SecurityDashboardController::class, 'unlockUser'])->name('security.unlock-user');
    Route::post('/security/threat-engine', [\App\Http\Controllers\Platform\SecurityDashboardController::class, 'updateThreatEngine'])->name('security.threat-engine');
    Route::post('/security/settings', [\App\Http\Controllers\Platform\SecurityDashboardController::class, 'updateSettings'])->name('security.settings');
    Route::post('/security/clear-logs', [\App\Http\Controllers\Platform\SecurityDashboardController::class, 'clearLogs'])->name('security.clear-logs');
});

// لوحة إدارة تطبيق الجوال (الوجهات والمحافظات، الإعلانات وبنرات أشهر الواجهات)
Route::middleware(['auth'])->prefix('admin/app-manager')->name('admin.app-manager.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AppManagerController::class, 'index'])->name('index');
    Route::post('/destinations', [\App\Http\Controllers\Admin\AppManagerController::class, 'storeDestination'])->name('destinations.store');
    Route::put('/destinations/{destination}', [\App\Http\Controllers\Admin\AppManagerController::class, 'updateDestination'])->name('destinations.update');
    Route::delete('/destinations/{destination}', [\App\Http\Controllers\Admin\AppManagerController::class, 'destroyDestination'])->name('destinations.destroy');
    Route::post('/destinations/{destination}/toggle', [\App\Http\Controllers\Admin\AppManagerController::class, 'toggleDestination'])->name('destinations.toggle');

    Route::post('/banners', [\App\Http\Controllers\Admin\AppManagerController::class, 'storeBanner'])->name('banners.store');
    Route::put('/banners/{banner}', [\App\Http\Controllers\Admin\AppManagerController::class, 'updateBanner'])->name('banners.update');
    Route::delete('/banners/{banner}', [\App\Http\Controllers\Admin\AppManagerController::class, 'destroyBanner'])->name('banners.destroy');
    Route::post('/banners/{banner}/toggle', [\App\Http\Controllers\Admin\AppManagerController::class, 'toggleBanner'])->name('banners.toggle');

    Route::post('/broadcast-message', [\App\Http\Controllers\Admin\AppManagerController::class, 'storeBroadcastMessage'])->name('broadcast-message.store');
    Route::post('/broadcast-notification', [\App\Http\Controllers\Admin\AppManagerController::class, 'storeBroadcastNotification'])->name('broadcast-notification.store');
    Route::post('/welcome-notification', [\App\Http\Controllers\Admin\AppManagerController::class, 'updateWelcomeNotification'])->name('welcome-notification.update');
    Route::delete('/broadcasts/{broadcast}', [\App\Http\Controllers\Admin\AppManagerController::class, 'destroyBroadcast'])->name('broadcasts.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    // بوابة النزيل (حجوزاتي، ملفي الشخصي، بريد النزيل)
    Route::get('/my-bookings', [GuestPortalController::class, 'bookings'])->name('guest.bookings');
    Route::post('/my-bookings/{booking}/cancel', [GuestPortalController::class, 'cancelBooking'])->name('guest.bookings.cancel');
    Route::get('/my-profile', [GuestPortalController::class, 'profile'])->name('guest.profile');
    Route::put('/my-profile', [GuestPortalController::class, 'updateProfile'])->name('guest.profile.update');
    Route::get('/my-messages', [GuestPortalController::class, 'messages'])->name('guest.messages');
    Route::post('/my-messages/send', [GuestPortalController::class, 'sendMessage'])->name('guest.messages.send');

    // الإشعارات والتنبيهات
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::match(['get', 'post'], '/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
});

Route::middleware('auth')->get('/dashboard/hotel', function () {
    return match (auth()->user()->role) {
        'super_admin', 'platform_admin' => redirect()->route('platform.dashboard'),
        'hotel_admin', 'hotel_owner', 'admin' => redirect()->route('admin.dashboard'),
        'reception' => redirect()->route('bookings.index'),
        'accountant' => redirect()->route('accounts.index'),
        'maintenance' => redirect()->route('maintenance.index'),
        'cleaner' => redirect()->route('cleaning.index'),
        'guest' => redirect()->route('guest.bookings'),
        default => redirect()->route('home'),
    };
})->name('dashboard.redirect');

Route::middleware(['auth', 'role:hotel_admin'])->prefix('dashboard/hotel')->group(function () {
    Route::get('/dashboard', [HotelDashboardController::class, 'index'])->name('admin.dashboard');
    Route::resource('offers', HotelOfferController::class)->names('admin.offers');
    Route::post('/offers/{offer}/toggle', [HotelOfferController::class, 'toggle'])->name('admin.offers.toggle');
    Route::get('/hotel-profile', [\App\Http\Controllers\Admin\HotelProfileController::class, 'edit'])->name('admin.hotel-profile.edit');
    Route::put('/hotel-profile', [\App\Http\Controllers\Admin\HotelProfileController::class, 'update'])->name('admin.hotel-profile.update');

    // إعدادات الطابعات والسكنر
    Route::get('/settings/printer', [\App\Http\Controllers\Admin\PrinterSettingController::class, 'edit'])->name('admin.printer-settings.edit');
    Route::put('/settings/printer', [\App\Http\Controllers\Admin\PrinterSettingController::class, 'update'])->name('admin.printer-settings.update');

    // النسخ الاحتياطي والاستعادة
    Route::get('/backup', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('admin.backup.index');
    Route::get('/backup/download', [\App\Http\Controllers\Admin\BackupController::class, 'download'])->name('admin.backup.download');
    Route::post('/backup/restore', [\App\Http\Controllers\Admin\BackupController::class, 'restore'])->name('admin.backup.restore');

    Route::resource('rooms', RoomController::class)->except(['show']);
    Route::resource('room-types', AdminRoomTypeController::class)->except(['show']);
    Route::get('/employees', [HotelUserController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [HotelUserController::class, 'create'])->name('employees.create');
    Route::post('/employees', [HotelUserController::class, 'store'])->name('employees.store');
    Route::get('/employees/{user}/edit', [HotelUserController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{user}', [HotelUserController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{user}', [HotelUserController::class, 'destroy'])->name('employees.destroy');
    Route::resource('complaints', ComplaintController::class);
    Route::get('/reports', [HotelReportController::class, 'index'])->name('admin.reports');
    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity.index');
    Route::get('/deletion-logs', [App\Http\Controllers\Admin\DeletionLogController::class, 'index'])->name('deletion-logs.index');

    // الرسائل الداخلية
    Route::get('/messages/inbox', [MessageController::class, 'inbox'])->name('messages.inbox');
    Route::get('/messages/sent', [MessageController::class, 'sent'])->name('messages.sent');
    Route::get('/messages/create', [MessageController::class, 'create'])->name('messages.create');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show');
    Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
});

Route::middleware(['auth', 'role:hotel_admin,reception'])->prefix('dashboard/hotel')->group(function () {
    Route::resource('bookings', AdminBookingController::class)->except(['show']);
    Route::post('/bookings/{booking}/confirm', [AdminBookingController::class, 'confirm'])->name('bookings.confirm');
    Route::get('/bookings/{booking}/checkout', [AdminBookingController::class, 'showCheckout'])->name('bookings.checkout.form');
    Route::post('/bookings/{booking}/checkin', [AdminBookingController::class, 'checkin'])->name('bookings.checkin');
    Route::post('/bookings/{booking}/checkout', [AdminBookingController::class, 'checkout'])->name('bookings.checkout');
    Route::get('/guests/{guest}/history', [App\Http\Controllers\Admin\GuestController::class, 'history'])->name('guests.history');
    Route::get('/guests/{guest}/print', [App\Http\Controllers\Admin\GuestController::class, 'printCard'])->name('guests.print');
    Route::resource('guests', App\Http\Controllers\Admin\GuestController::class)->except(['show']);
    Route::post('/cleaning/request', [HousekeepingController::class, 'requestCleaning'])->name('cleaning.request');
});

Route::middleware(['auth', 'role:hotel_admin,reception,accountant'])->prefix('dashboard/hotel')->group(function () {
    Route::resource('invoices', AdminInvoiceController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/invoices/{invoice}/pdf', [AdminInvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::put('/invoices/{invoice}/pay', [AdminInvoiceController::class, 'markPaid'])->middleware('role:hotel_admin,reception,accountant')->name('invoices.pay');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');

});

Route::middleware(['auth', 'role:hotel_admin,accountant'])->prefix('dashboard/hotel')->group(function () {
    Route::get('/accounts/reports', [AccountController::class, 'reports'])->name('accounts.reports');
    Route::get('/accounts/reports/pdf', [AccountController::class, 'reportsPdf'])->name('accounts.reports.pdf');
    Route::resource('accounts', AccountController::class)->except(['show']);
});

Route::middleware(['auth', 'role:hotel_admin,maintenance'])->prefix('dashboard/hotel')->group(function () {
    Route::resource('maintenance', MaintenanceController::class)->except(['show']);
});

Route::middleware(['auth', 'role:hotel_admin,cleaner'])->prefix('dashboard/hotel')->group(function () {
    Route::get('/cleaning', [HousekeepingController::class, 'index'])->name('cleaning.index');
    Route::post('/cleaning/{room}/complete', [HousekeepingController::class, 'clean'])->name('cleaning.complete');
});

// التعامل مع أي روت أو عملية غير معرفة بالروت وتحويلها لصفحة تحذير مهذبة
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});

