<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SecurityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'user_id',
        'event_type',
        'severity',
        'method',
        'url',
        'user_agent',
        'payload',
        'message',
    ];

    /**
     * Record a security event or attack attempt.
     */
    public static function record(
        string $eventType,
        string $severity = 'warning',
        string $message = '',
        ?Request $request = null,
        ?int $userId = null,
        ?array $extraPayload = null
    ): ?self {
        $ip = $request ? $request->ip() : request()->ip();
        $url = $request ? $request->fullUrl() : (request() ? request()->fullUrl() : null);
        $method = $request ? $request->method() : (request() ? request()->method() : null);
        $userAgent = $request ? $request->userAgent() : (request() ? request()->userAgent() : null);
        $activeUserId = $userId ?: (auth()->check() ? auth()->id() : null);

        // Sanitize sensitive fields from payload
        $payloadData = $extraPayload ?? ($request ? $request->except(['password', 'password_confirmation', 'current_password', 'credit_card', 'cvv']) : []);
        $sanitizedPayload = !empty($payloadData) ? json_encode($payloadData, JSON_UNESCAPED_UNICODE) : null;

        if ($severity === 'critical') {
            Log::critical("[SECURITY ATTACK] {$eventType} from IP {$ip}: {$message}");
        } else {
            Log::warning("[SECURITY EVENT] {$eventType} from IP {$ip}: {$message}");
        }

        if (!Schema::hasTable('security_logs')) {
            return null;
        }

        return static::create([
            'ip_address' => $ip ?: '0.0.0.0',
            'user_id' => $activeUserId,
            'event_type' => $eventType,
            'severity' => in_array($severity, ['info', 'warning', 'critical']) ? $severity : 'warning',
            'method' => $method,
            'url' => $url ? substr($url, 0, 500) : null,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            'payload' => $sanitizedPayload,
            'message' => $message,
        ]);
    }
}

