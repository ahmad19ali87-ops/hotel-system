<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Send a notification to a specific user.
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
        $userId = $user instanceof User ? $user->id : $user;
        if (!$userId) {
            return null;
        }

        if (!$hotelId && $user instanceof User) {
            $hotelId = $user->hotel_id;
        }

        return AppNotification::create([
            'user_id' => $userId,
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
            self::sendToUser($user, $title, $message, $type, $icon, $link, $hotelId, $data);
            $count++;
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
            $query->where(function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId)
                  ->orWhereIn('role', ['super_admin', 'platform_admin']);
            });
        }

        $users = $query->get();
        return self::sendToUsers($users, $title, $message, $type, $icon, $link, $hotelId, $data);
    }

    /**
     * Send notification to all registered guests (e.g. for promotional offers).
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
        $guests = User::where('role', 'guest')->get();
        return self::sendToUsers($guests, $title, $message, $type, $icon, $link, $hotelId, $data);
    }

    /**
     * Send notification to all hotel staff and managers.
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
        $query = User::where('role', '!=', 'guest');
        if ($hotelId) {
            $query->where(function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId)
                  ->orWhereIn('role', ['super_admin', 'platform_admin']);
            });
        }
        $users = $query->get();

        return self::sendToUsers($users, $title, $message, $type, $icon, $link, $hotelId, $data);
    }

    /**
     * Send notification to everyone belonging to this hotel (staff, admins, and guests).
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
        $query = User::query();
        if ($hotelId) {
            $query->where(function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId)
                  ->orWhereIn('role', ['super_admin', 'platform_admin']);
            });
        }
        $users = $query->get();

        return self::sendToUsers($users, $title, $message, $type, $icon, $link, $hotelId, $data);
    }
}

