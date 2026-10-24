<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Send a notification to a specific user with strict role & tenant boundaries.
     */
    public static function sendToUser(
        User|int $user,
        string $title,
        string $message,
        string $type = 'general',
        string $icon = '🔔',
        ?string $link = null,
        ?int $hotelId = null,
        ?array $data = null
    ): ?AppNotification {
        $userObj = $user instanceof User ? $user : User::find($user);
        if (!$userObj) {
            return null;
        }

        if (!$hotelId && $userObj->hotel_id) {
            $hotelId = $userObj->hotel_id;
        }

        // Rule 1: Super admin & Platform admin must NEVER receive hotel-specific operational notifications
        if (in_array($userObj->role, ['super_admin', 'platform_admin'], true)) {
            $platformAllowedTypes = [
                'security_alert',
                'crm_lead',
                'subscription_request',
                'support_ticket',
                'broadcast_message',
                'system',
                'platform_announcement',
            ];

            // If the notification belongs to a specific hotel or is an operational hotel type, reject for super admin
            if ($hotelId !== null || !in_array($type, $platformAllowedTypes, true)) {
                return null;
            }
        }

        // Rule 2: Hotel staff must only receive notifications for their own hotel or platform broadcasts
        if ($userObj->hotel_id && !in_array($userObj->role, ['super_admin', 'platform_admin', 'guest'], true)) {
            if ($hotelId !== null && (int) $hotelId !== (int) $userObj->hotel_id) {
                return null;
            }
        }

        // Rule 3: Customer / Guest isolation:
        // Guests must NOT receive staff-internal operational notifications
        if ($userObj->role === 'guest') {
            $staffInternalTypes = [
                'cleaning_request',
                'cleaning_done',
                'maintenance_request',
                'crm_lead',
                'security_alert',
                'system_metric',
            ];
            if (in_array($type, $staffInternalTypes, true)) {
                return null;
            }
        }

        return AppNotification::create([
            'user_id' => $userObj->id,
            'hotel_id' => $hotelId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'icon' => $icon,
            'link' => $link,
            'data' => $data,
            'is_read' => false,
        ]);
    }

    /**
     * Send notifications to multiple users.
     */
    public static function sendToUsers(
        iterable $users,
        string $title,
        string $message,
        string $type = 'general',
        string $icon = '🔔',
        ?string $link = null,
        ?int $hotelId = null,
        ?array $data = null
    ): int {
        $count = 0;
        foreach ($users as $user) {
            $created = self::sendToUser($user, $title, $message, $type, $icon, $link, $hotelId, $data);
            if ($created) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Send notification to staff by role and hotel.
     * E.g. role 'reception', 'hotel_admin', 'cleaner', 'maintenance'.
     */
    public static function sendToRole(
        string|array $roles,
        ?int $hotelId,
        string $title,
        string $message,
        string $type = 'general',
        string $icon = '🔔',
        ?string $link = null,
        ?array $data = null
    ): int {
        $roles = (array) $roles;

        $query = User::whereIn('role', $roles);
        if ($hotelId) {
            // Strictly isolated to this hotel's staff; NEVER route to super_admin or platform_admin
            $query->where('hotel_id', $hotelId)
                  ->whereNotIn('role', ['super_admin', 'platform_admin']);
        }

        $users = $query->get();
        return self::sendToUsers($users, $title, $message, $type, $icon, $link, $hotelId, $data);
    }

    /**
     * Send notification to registered guests.
     * If $hotelId is provided, restricts strictly to guests associated with that hotel.
     */
    public static function sendToGuests(
        string $title,
        string $message,
        string $type = 'offer_new',
        string $icon = '🎁',
        ?string $link = null,
        ?int $hotelId = null,
        ?array $data = null
    ): int {
        $query = User::where('role', 'guest');

        if ($hotelId) {
            // Strictly guests of this specific hotel
            $query->where(function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId)
                  ->orWhereHas('guestProfiles', function ($gq) use ($hotelId) {
                      $gq->where('hotel_id', $hotelId);
                  })
                  ->orWhereHas('guestProfiles.bookings', function ($bq) use ($hotelId) {
                      $bq->where('hotel_id', $hotelId);
                  });
            });
        }

        $guests = $query->get();
        return self::sendToUsers($guests, $title, $message, $type, $icon, $link, $hotelId, $data);
    }

    /**
     * Send notification to hotel staff and managers of a specific hotel.
     * Never reaches super_admin, platform_admin, or guests.
     */
    public static function sendToHotelStaff(
        ?int $hotelId,
        string $title,
        string $message,
        string $type = 'general',
        string $icon = '🔔',
        ?string $link = null,
        ?array $data = null
    ): int {
        if (!$hotelId) {
            return 0;
        }

        $users = User::where('hotel_id', $hotelId)
            ->where('role', '!=', 'guest')
            ->whereNotIn('role', ['super_admin', 'platform_admin'])
            ->get();

        return self::sendToUsers($users, $title, $message, $type, $icon, $link, $hotelId, $data);
    }

    /**
     * Send notification to everyone belonging to this specific hotel (staff and guests of this hotel).
     * Never reaches super_admin or platform_admin.
     */
    public static function sendToHotelEveryone(
        ?int $hotelId,
        string $title,
        string $message,
        string $type = 'general',
        string $icon = '📢',
        ?string $link = null,
        ?array $data = null
    ): int {
        if (!$hotelId) {
            return 0;
        }

        $users = User::where(function ($q) use ($hotelId) {
            $q->where('hotel_id', $hotelId)
              ->orWhereHas('guestProfiles', function ($gq) use ($hotelId) {
                  $gq->where('hotel_id', $hotelId);
              })
              ->orWhereHas('guestProfiles.bookings', function ($bq) use ($hotelId) {
                  $bq->where('hotel_id', $hotelId);
              });
        })->whereNotIn('role', ['super_admin', 'platform_admin'])->get();

        return self::sendToUsers($users, $title, $message, $type, $icon, $link, $hotelId, $data);
    }
}
