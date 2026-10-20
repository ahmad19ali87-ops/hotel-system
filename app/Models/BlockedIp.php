<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class BlockedIp extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_until',
        'violations_count',
        'user_agent',
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
        'violations_count' => 'integer',
    ];

    /**
     * Determine if a given IP address is currently blocked.
     */
    public static function isBlocked(string $ip): bool
    {
        if (!Schema::hasTable('blocked_ips')) {
            return false;
        }

        return static::where('ip_address', $ip)
            ->where(function ($query) {
                $query->whereNull('blocked_until')
                    ->orWhere('blocked_until', '>', now());
            })
            ->exists();
    }

    /**
     * Get block record for IP if active.
     */
    public static function getActiveBlock(string $ip): ?self
    {
        if (!Schema::hasTable('blocked_ips')) {
            return null;
        }

        return static::where('ip_address', $ip)
            ->where(function ($query) {
                $query->whereNull('blocked_until')
                    ->orWhere('blocked_until', '>', now());
            })
            ->first();
    }

    /**
     * Block an IP immediately for a specified duration in minutes (0 = indefinite).
     */
    public static function block(string $ip, string $reason, int $minutes = 60, ?string $userAgent = null): ?self
    {
        if (!Schema::hasTable('blocked_ips')) {
            return null;
        }

        $blockedUntil = $minutes > 0 ? now()->addMinutes($minutes) : null;

        $record = static::firstOrNew(['ip_address' => $ip]);
        $record->reason = $reason;
        $record->blocked_until = $blockedUntil;
        $record->violations_count = ($record->violations_count ?: 0) + 1;
        if ($userAgent) {
            $record->user_agent = substr($userAgent, 0, 500);
        }
        $record->save();

        return $record;
    }

    /**
     * Record a suspicious violation for this IP. Automatically block if threshold reached.
     */
    public static function recordViolation(string $ip, string $reason, int $threshold = 5, int $blockMinutes = 60, ?string $userAgent = null): ?self
    {
        if (!Schema::hasTable('blocked_ips')) {
            return null;
        }

        $record = static::firstOrNew(['ip_address' => $ip]);
        $record->violations_count = ($record->violations_count ?: 0) + 1;
        $record->reason = $reason;
        if ($userAgent) {
            $record->user_agent = substr($userAgent, 0, 500);
        }

        if ($record->violations_count >= $threshold && (!$record->blocked_until || $record->blocked_until <= now())) {
            $record->blocked_until = now()->addMinutes($blockMinutes);
        }

        $record->save();
        return $record;
    }

    /**
     * Unblock an IP address.
     */
    public static function unblock(string $ip): bool
    {
        if (!Schema::hasTable('blocked_ips')) {
            return false;
        }

        return (bool) static::where('ip_address', $ip)->delete();
    }
}

