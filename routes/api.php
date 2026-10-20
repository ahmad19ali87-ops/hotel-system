<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\HotelController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\StaffController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (Mobile App Guests & Browsing)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-code', [AuthController::class, 'verifyCode']);
    Route::post('/resend-code', [AuthController::class, 'resendCode']);
    Route::post('/google-login', [AuthController::class, 'googleLogin']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::get('/hotels', [HotelController::class, 'index']);
Route::get('/hotels/{id}', [HotelController::class, 'show']);
Route::get('/hotels/{id}/reviews', [HotelController::class, 'reviews']);
Route::post('/hotels/{id}/reviews', [HotelController::class, 'storeReview']);
Route::post('/hotels/{id}/reviews/{reviewId}/reply', [HotelController::class, 'replyToReview']);
Route::get('/hotels/{id}/contact-user', [HotelController::class, 'contactUser']);
Route::get('/destinations', [HotelController::class, 'destinations']);
Route::get('/app-banners', [HotelController::class, 'appBanners']);
Route::get('/banners', [HotelController::class, 'appBanners']);
Route::get('/offers', [HotelController::class, 'offers']);

// Guest Complaints & Suggestions (Open for guests, optional sanctum auth handled inside)
Route::post('/complaints', [ComplaintController::class, 'store']);
Route::get('/my-complaints', [ComplaintController::class, 'myComplaints']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Mobile App & Desktop App)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Current user info & logout
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Bookings (Guest)
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/my-bookings', [BookingController::class, 'myBookings']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

    // Unified Chat System (Guest & Staff)
    Route::get('/chat/conversations', [ChatController::class, 'conversations']);
    Route::get('/chat/contacts', [ChatController::class, 'contacts']);
    Route::get('/chat/messages', [ChatController::class, 'messages']);
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);

    // In-app Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markSingleRead']);
    Route::post('/notifications/broadcast', [NotificationController::class, 'sendBroadcast']);

    // Hotel Staff & Management Operations (Desktop App & Staff Portal)
    Route::prefix('staff')->group(function () {
        Route::get('/dashboard', [StaffController::class, 'dashboard']);
        
        // Rooms & Types
        Route::get('/rooms', [StaffController::class, 'rooms']);
        Route::post('/rooms', [StaffController::class, 'storeRoom']);
        Route::put('/rooms/{id}', [StaffController::class, 'updateRoom']);
        Route::delete('/rooms/{id}', [StaffController::class, 'deleteRoom']);
        Route::get('/room-types', [StaffController::class, 'roomTypes']);
        Route::post('/room-types', [StaffController::class, 'storeRoomType']);
        Route::put('/room-types/{id}', [StaffController::class, 'updateRoomType']);
        Route::delete('/room-types/{id}', [StaffController::class, 'deleteRoomType']);

        // Bookings & Front Desk (Check-in / Check-out)
        Route::get('/bookings', [StaffController::class, 'bookings']);
        Route::post('/bookings', [StaffController::class, 'storeBooking']);
        Route::post('/bookings/{id}/check-in', [StaffController::class, 'checkInBooking']);
        Route::post('/bookings/{id}/check-out', [StaffController::class, 'checkOutBooking']);
        Route::post('/bookings/{id}/cancel', [StaffController::class, 'cancelBooking']);
        Route::put('/bookings/{id}/status', [StaffController::class, 'updateBookingStatus']);

        // Guests
        Route::get('/guests', [StaffController::class, 'guests']);
        Route::post('/guests', [StaffController::class, 'storeGuest']);
        Route::put('/guests/{id}', [StaffController::class, 'updateGuest']);
        Route::delete('/guests/{id}', [StaffController::class, 'deleteGuest']);

        // Accounts & Cash Book
        Route::get('/accounts', [StaffController::class, 'accounts']);
        Route::post('/accounts', [StaffController::class, 'storeAccount']);

        // Invoices & Billing
        Route::get('/invoices', [StaffController::class, 'invoices']);
        Route::post('/invoices', [StaffController::class, 'storeInvoice']);
        Route::post('/invoices/{id}/pay', [StaffController::class, 'payInvoice']);
        Route::delete('/invoices/{id}', [StaffController::class, 'deleteInvoice']);

        // Staff & Extra Permissions
        Route::get('/employees', [StaffController::class, 'employees']);
        Route::post('/employees', [StaffController::class, 'storeEmployee']);
        Route::put('/employees/{id}/permissions', [StaffController::class, 'updateEmployeePermissions']);

        // Housekeeping
        Route::get('/cleaning', [StaffController::class, 'cleaningRooms']);
        Route::post('/cleaning/{id}/complete', [StaffController::class, 'completeCleaning']);
        Route::post('/cleaning/{id}/request', [StaffController::class, 'requestCleaning']);

        // Maintenance
        Route::get('/maintenance', [StaffController::class, 'maintenanceList']);
        Route::post('/maintenance', [StaffController::class, 'storeMaintenance']);
        Route::put('/maintenance/{id}/status', [StaffController::class, 'updateMaintenanceStatus']);

        // Deletion Logs & Audit
        Route::get('/deletion-logs', [StaffController::class, 'deletionLogs']);

        // Activity Logs
        Route::get('/activity-logs', [StaffController::class, 'activityLogs']);

        // Complaints & Suggestions
        Route::get('/complaints', [StaffController::class, 'complaints']);
        Route::post('/complaints', [StaffController::class, 'storeComplaint']);
        Route::put('/complaints/{id}', [StaffController::class, 'updateComplaint']);

        // Comprehensive Reports & Accounting
        Route::get('/reports', [StaffController::class, 'reports']);
        Route::get('/reports/accounting', [StaffController::class, 'accountingReport']);

        // Printer & Scanner Settings
        Route::get('/printer-settings', [StaffController::class, 'printerSettings']);
        Route::post('/printer-settings', [StaffController::class, 'updatePrinterSettings']);

        // Barcode & QR Scanner Lookup
        Route::get('/scanner/lookup', [StaffController::class, 'scannerLookup']);

        // Staff Notifications Center
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markSingleRead']);
        Route::post('/notifications/broadcast', [NotificationController::class, 'sendBroadcast']);
    });
});
