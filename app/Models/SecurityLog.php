<?php

namespace App\Models;

use App\Services\GeoIpService;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SecurityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'country_code',
        'country_name',
        'user_id',
        'event_type',
        'severity',
        'action_taken',
        'method',
        'url',
        'user_agent',
        'payload',
        'message',
    ];

    /**
     * Record a security event or attack attempt and evaluate instant alert triggers.
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
        $ip = $ip ?: '0.0.0.0';
        $url = $request ? $request->fullUrl() : (request() ? request()->fullUrl() : null);
        $method = $request ? $request->method() : (request() ? request()->method() : null);
        $userAgent = $request ? $request->userAgent() : (request() ? request()->userAgent() : null);
        $activeUserId = $userId ?: (auth()->check() ? auth()->id() : null);

        // Sanitize sensitive fields from payload
        $payloadData = $extraPayload ?? ($request ? $request->except(['password', 'password_confirmation', 'current_password', 'credit_card', 'cvv']) : []);
        $sanitizedPayload = !empty($payloadData) ? json_encode($payloadData, JSON_UNESCAPED_UNICODE) : null;

        // Lookup country
        $geo = GeoIpService::lookup($ip);
        $countryCode = $geo['code'] ?? 'UNKNOWN';
        $countryName = $geo['name'] ?? 'غير محدد';

        if ($severity === 'critical') {
            Log::critical("[SECURITY ATTACK] {$eventType} from IP {$ip} ({$countryName}): {$message}");
        } else {
            Log::warning("[SECURITY EVENT] {$eventType} from IP {$ip} ({$countryName}): {$message}");
        }

        if (!Schema::hasTable('security_logs')) {
            return null;
        }

        $actionTaken = $extraPayload['action_taken'] ?? null;

        $logData = [
            'ip_address' => $ip,
            'country_code' => $countryCode,
            'country_name' => $countryName,
            'user_id' => $activeUserId,
            'event_type' => $eventType,
            'severity' => in_array($severity, ['info', 'warning', 'critical']) ? $severity : 'warning',
            'method' => $method,
            'url' => $url ? substr($url, 0, 500) : null,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            'payload' => $sanitizedPayload,
            'message' => $message,
        ];

        if (Schema::hasColumn('security_logs', 'action_taken')) {
            $logData['action_taken'] = $actionTaken;
        }

        $log = static::create($logData);

        // Evaluate Instant Alerts
        static::evaluateInstantAlerts($eventType, $severity, $ip, $countryCode, $countryName, $message);

        // Auto-enforce watched country policy if critical attack detected
        if ($severity === 'critical' && !in_array($eventType, ['blocked_ip_access_attempt', 'watched_country_attack'], true)) {
            \App\Services\ThreatAutoResponseEngine::inspectAndEnforceWatchedCountry($ip, $eventType, $request);
        }

        return $log;
    }

    /**
     * Check if security event triggers an instant notification for Super Admin.
     */
    protected static function evaluateInstantAlerts(
        string $eventType,
        string $severity,
        string $ip,
        string $countryCode,
        string $countryName,
        string $message
    ): void {
        try {
            $alertReasons = [];

            // 1. Critical Attack Trigger
            if ($severity === 'critical') {
                $alertReasons[] = "هجوم عالي الخطورة ({$eventType})";
            }

            // 2. Watchlist Country Trigger
            $watchlistStr = SiteSetting::get('security_alert_countries', 'IL,IR,RU,CN,US');
            $watchCountries = array_filter(array_map('trim', explode(',', strtoupper($watchlistStr))));
            if (!empty($countryCode) && in_array(strtoupper($countryCode), $watchCountries, true)) {
                $alertReasons[] = "محاولة اختراق قادمة من دولة ضمن قائمة المراقبة ({$countryName} - {$countryCode})";
            }

            // 3. Repeated Violations Threshold Trigger
            $threshold = (int) SiteSetting::get('security_alert_threshold', 5);
            $recentCount = static::where('ip_address', $ip)
                ->where('created_at', '>=', now()->subHour())
                ->count();

            if ($recentCount >= $threshold) {
                $alertReasons[] = "تجاوز الحد الأمني للتكرار ({$recentCount} محاولات خلال ساعة واحدة)";
            }

            // If any trigger matched, send alert (throttled by IP per 5 minutes)
            if (!empty($alertReasons)) {
                $throttleKey = "security_alert_sent:{$ip}:" . md5(implode('|', $alertReasons));
                if (Cache::add($throttleKey, true, now()->addMinutes(5))) {
                    $title = "🚨 إنذار أمني فوري: " . implode(' + ', $alertReasons);
                    $body = "تم رصد نشاط مريب من الـ IP ({$ip}) التابع لـ ({$countryName}). تفاصيل الحدث: {$message}";

                    NotificationService::sendToRole(
                        'super_admin',
                        null,
                        $title,
                        $body,
                        'security_alert',
                        '🛡️',
                        url('/platform/security')
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Error dispatching security alert: " . $e->getMessage());
        }
    }
}
