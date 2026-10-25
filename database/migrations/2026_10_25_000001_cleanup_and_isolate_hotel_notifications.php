<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;
use App\Models\AppNotification;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove all hotel-specific or hotel operational notifications mistakenly routed to super_admin or platform_admin users
        $adminUserIds = User::whereIn('role', ['super_admin', 'platform_admin'])->pluck('id')->toArray();
        if (!empty($adminUserIds)) {
            AppNotification::whereIn('user_id', $adminUserIds)
                ->where(function ($q) {
                    $q->whereNotNull('hotel_id')
                      ->orWhereIn('type', [
                          'booking',
                          'booking_created',
                          'booking_confirmed',
                          'booking_new',
                          'booking_cancelled',
                          'booking_updated',
                          'check_in',
                          'check_out',
                          'cleaning_request',
                          'cleaning_done',
                          'cleaning',
                          'maintenance_request',
                          'maintenance',
                          'invoice_paid',
                          'review_new',
                          'complaint_new',
                          'complaint',
                          'promo_offer',
                          'offer_new',
                      ]);
                })
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cleanup is intentional and permanent
    }
};

