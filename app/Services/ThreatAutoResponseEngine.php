<?php

namespace App\Services;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ThreatAutoResponseEngine
{
    /**
     * Check if Threat Auto-Response Engine is globally enabled.
     */
    public static function isEnabled(): bool
    {
        return SiteSetting::get('threat_engine_enabled', '1') !== '0';
    }

    /**
     * Get active status and rule summary for the dashboard.
     */
    public static function getEngineSummary(): array
    {
        $enabled = static::isEnabled();
        $rules = [
            [
                'id' => 'sqli',
                'name' => 'هجمات SQL Injection',
                'action' => 'حظر IP فوري دائم',
                'action_type' => 'instant_ip_ban',
                'enabled' => SiteSetting::get('threat_rule_sqli', '1') !== '0',
                'description' => 'حظر عنوان IP المهاجم فوراً ومنعه من إجراء أي اتصالات أخرى بالخادم.',
                'icon' => '💉',
                'badge' => 'حظر فوري',
                'badge_color' => 'danger',
            ],
            [
                'id' => 'malicious_file',
                'name' => 'رفع ملفات خبيثة',
                'action' => 'حظر IP (24 ساعة) + إنذار عاجل',
                'action_type' => 'file_block_and_alarm',
                'enabled' => SiteSetting::get('threat_rule_file', '1') !== '0',
                'description' => 'حظر عنوان IP فوراً لمدة 24 ساعة وإرسال إشعار أمني عالي الخطورة للإدارة.',
                'icon' => '☣️',
                'badge' => 'حظر + إنذار',
                'badge_color' => 'danger',
            ],
            [
                'id' => 'watched_country',
                'name' => 'هجوم من دولة مراقبة',
                'action' => 'حظر IP لمدة 24 ساعة',
                'action_type' => 'watched_country_ban_24h',
                'enabled' => SiteSetting::get('threat_rule_country', '1') !== '0',
                'description' => 'فرض حظر تلقائي لمدة 24 ساعة على أي IP يشن هجوماً من قائمة الدول المراقبة.',
                'icon' => '🌐',
                'badge' => 'حظر 24h',
                'badge_color' => 'warning',
            ],
            [
                'id' => 'brute_force',
                'name' => 'هجوم التخمين Brute Force',
                'action' => 'قفل الحساب لمدة ساعة',
                'action_type' => 'account_lockout_1h',
                'enabled' => SiteSetting::get('threat_rule_bruteforce', '1') !== '0',
                'description' => 'قفل الحساب المستهدف لمدة ساعة كاملة مع إبطال كافة الجلسات والتوكنات النشطة.',
                'icon' => '🔒',
                'badge' => 'قفل 1h',
                'badge_color' => 'warning',
            ],
            [
                'id' => 'replay_attack',
                'name' => 'هجوم إعادة الإرسال Replay Attack',
                'action' => 'حظر وإنهاء الجلسة والتوكن بالكامل',
                'action_type' => 'session_token_kill',
                'enabled' => SiteSetting::get('threat_rule_replay', '1') !== '0',
                'description' => 'إلغاء وحذف جلسة المستخدم والتوكن المعتمد فوراً وحظره من الاستخدام.',
                'icon' => '⚡',
                'badge' => 'إنهاء الجلسة',
                'badge_color' => 'danger',
            ],
        ];

        return [
            'enabled' => $enabled,
            'rules' => $rules,
            'active_rules_count' => count(array_filter($rules, fn ($r) => $r['enabled'])),
            'total_rules_count' => count($rules),
        ];
    }

    /**
     * 1. Rule: SQL Injection -> Immediate IP Ban.
     * إذا هجوم SQL Injection → حظر IP فورًا
     */
    public static function handleSqlInjection(string $ip, string $details = '', ?Request $request = null): void
    {
        if (!static::isEnabled() || SiteSetting::get('threat_rule_sqli', '1') === '0') {
            return;
        }

        $reason = "محرك الاستجابة التلقائي: تم حظر الـ IP فوراً لاكتشاف محاولة حقن قواعد البيانات (SQL Injection): " . ($details ?: 'Unsafe SQL signature');

        // Immediate IP block: indefinite / permanent (0 minutes)
        BlockedIp::block($ip, $reason, 0, $request?->userAgent());

        // Instant Alert to Super Admin
        static::dispatchInstantAlert(
            "🚨 حظر فوري: هجوم SQL Injection تم إحباطه",
            "قام محرك الاستجابة التلقائي بحظر عنوان الـ IP ({$ip}) فوراً وبشكل دائم بعد رصد محاولة حقن SQL Injection خبيثة.",
            'sql_injection_attempt',
            'critical',
            $ip
        );
    }

    /**
     * 2. Rule: Malicious File Upload -> IP Block + Instant Alert.
     * إذا هجوم رفع ملف ضار → حظر IP + إرسال إنذار
     */
    public static function handleMaliciousFileUpload(string $ip, string $filename, string $reason = '', ?Request $request = null): void
    {
        if (!static::isEnabled() || SiteSetting::get('threat_rule_file', '1') === '0') {
            return;
        }

        $blockReason = "محرك الاستجابة التلقائي: حظر فوري لمحاولة رفع ملف خبيث ({$filename}): " . ($reason ?: 'Blocked executable script/extension');

        // Immediate IP block for 24 hours (1440 mins)
        BlockedIp::block($ip, $blockReason, 1440, $request?->userAgent());

        // Instant high-priority alarm notification
        static::dispatchInstantAlert(
            "🚨 إنذار أمني عاجل: محاولة رفع ملف خبيث وتم حظر المهاجم",
            "تم رصد محاولة رفع ملف خطير ({$filename}) من الـ IP ({$ip}). قام محرك الاستجابة بحظر الـ IP فوراً لمدة 24 ساعة. السبب: {$reason}",
            'malicious_file_upload',
            'critical',
            $ip
        );
    }

    /**
     * 3. Rule: Attack from a Watched Country -> 24-hour IP Ban.
     * إذا هجوم من دولة مراقبة → حظر IP لمدة 24 ساعة
     */
    public static function handleWatchedCountryAttack(string $ip, string $countryCode, string $countryName, string $attackType = 'مريب', ?Request $request = null): void
    {
        if (!static::isEnabled() || SiteSetting::get('threat_rule_country', '1') === '0') {
            return;
        }

        // Check if IP already has an active permanent or long block to avoid overwriting with a shorter duration
        $existingBlock = BlockedIp::getActiveBlock($ip);
        if ($existingBlock && ($existingBlock->blocked_until === null || $existingBlock->blocked_until > now()->addHours(23))) {
            return;
        }

        $reason = "محرك الاستجابة التلقائي: حظر تلقائي لمدة 24 ساعة لهجوم قادم من دولة مراقبة ({$countryName} - {$countryCode}). نوع الهجوم: {$attackType}";

        // Block for 24 hours (1440 minutes)
        BlockedIp::block($ip, $reason, 1440, $request?->userAgent());

        // Instant Alert to Super Admin
        static::dispatchInstantAlert(
            "🌐 حظر تلقائي (24 ساعة): هجوم من دولة مراقبة ({$countryName})",
            "قام محرك الاستجابة التلقائي بحظر الـ IP ({$ip}) التابع لدولة مراقبة ({$countryName} [{$countryCode}]) لمدة 24 ساعة بعد رصد نشاط ({$attackType}).",
            'watched_country_attack',
            'critical',
            $ip
        );
    }

    /**
     * 4. Rule: Brute Force -> 1-Hour Account Lockout.
     * إذا هجوم brute force → قفل الحساب لمدة ساعة
     */
    public static function handleBruteForce(string $email, string $ip, ?Request $request = null): ?User
    {
        if (!static::isEnabled() || SiteSetting::get('threat_rule_bruteforce', '1') === '0') {
            return null;
        }

        $user = User::where('email', $email)->first();
        if ($user) {
            // Lock account for 1 hour (60 minutes) and terminate active sessions/tokens
            $user->lockFor(60, "محاولات دخول متكررة غير مصرح بها (Brute Force)");

            // Log security event
            SecurityLog::record(
                'brute_force_account_locked',
                'critical',
                "الحساب ({$email}) تم قفله تلقائياً لمدة ساعة بواسطة محرك الاستجابة التلقائي بسبب هجوم Brute Force من IP {$ip}",
                $request,
                $user->id,
                ['action_taken' => 'account_locked_1h', 'locked_until' => $user->locked_until?->toIso8601String()]
            );

            // Instant Alert to Super Admin
            static::dispatchInstantAlert(
                "🔒 إغلاق حساب أمني: هجوم Brute Force على ({$email})",
                "تم قفل حساب المستخدم ({$email}) تلقائياً لمدة ساعة كاملة وإلغاء جميع جلساته النشطة بعد رصد محاولات تخمين متكررة من الـ IP ({$ip}).",
                'brute_force_lockout',
                'critical',
                $ip
            );
        }

        // Also record violation on the IP (3 violations = 2h block)
        BlockedIp::recordViolation($ip, "Brute force attack on user account: {$email}", 3, 120, $request?->userAgent());

        return $user;
    }

    /**
     * 5. Rule: Replay Attack -> Full Session & Token Termination.
     * إذا هجوم Replay → حظر الجلسة بالكامل
     */
    public static function handleReplayAttack(Request $request, string $reason = 'Replay attack detected'): void
    {
        if (!static::isEnabled() || SiteSetting::get('threat_rule_replay', '1') === '0') {
            return;
        }

        $ip = $request->ip() ?: '0.0.0.0';
        $user = $request->user();

        // If user not yet resolved on request (e.g. middleware ran before auth:sanctum), resolve via Bearer token
        $bearer = $request->bearerToken();
        if (!$user && $bearer && class_exists(\Laravel\Sanctum\PersonalAccessToken::class)) {
            $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($bearer);
            if ($tokenModel && $tokenModel->tokenable instanceof User) {
                $user = $tokenModel->tokenable;
            }
        }

        // 1. Terminate API token if present
        if ($user) {
            try {
                $currentToken = $user->currentAccessToken();
                if ($currentToken && method_exists($currentToken, 'delete')) {
                    $currentToken->delete();
                }
                // Revoke all tokens for high risk replay
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            } catch (\Throwable $e) {
                Log::warning("Error revoking tokens during replay response: " . $e->getMessage());
            }
        }

        // 2. Terminate Web session if present
        if ($request->hasSession()) {
            try {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            } catch (\Throwable $e) {
                Log::warning("Error invalidating web session during replay response: " . $e->getMessage());
            }
        }

        // 3. Blacklist bearer token in cache for 24 hours
        $bearer = $request->bearerToken();
        if ($bearer) {
            $tokenHash = hash('sha256', $bearer);
            Cache::put('blacklisted_bearer:' . $tokenHash, true, now()->addDay());
        }

        // 4. Record violation on IP (2 replay attempts = 24h IP block)
        BlockedIp::recordViolation($ip, "Replay attack detected: {$reason}", 2, 1440, $request->userAgent());

        // 5. Log critical security event
        SecurityLog::record(
            'replay_attack_session_terminated',
            'critical',
            "محرك الاستجابة التلقائي: تم إنهاء وحظر الجلسة والتوكن بالكامل لاكتشاف هجوم Replay Attack ({$reason})",
            $request,
            $user?->id,
            ['action_taken' => 'session_terminated', 'reason' => $reason]
        );

        // 6. Send Alert
        static::dispatchInstantAlert(
            "⚡ إنهاء جلسة فوري: تم إحباط هجوم Replay Attack",
            "تم حظر وإنهاء الجلسة والتوكن بالكامل للمستخدم أو العميل من الـ IP ({$ip}) لاكتشاف إعادة إرسال طلب غير مصرح به: {$reason}",
            'replay_attack_session_terminated',
            'critical',
            $ip
        );
    }

    /**
     * Check if an IP originates from a watched country and apply 24-hour block if needed.
     */
    public static function inspectAndEnforceWatchedCountry(string $ip, string $eventType, ?Request $request = null): bool
    {
        if (!static::isEnabled() || SiteSetting::get('threat_rule_country', '1') === '0') {
            return false;
        }

        $watchlistStr = SiteSetting::get('security_alert_countries', 'IL,IR,RU,CN,US');
        $watchCountries = array_filter(array_map('trim', explode(',', strtoupper($watchlistStr))));
        if (empty($watchCountries)) {
            return false;
        }

        $geo = GeoIpService::lookup($ip);
        $code = strtoupper($geo['code'] ?? 'UNKNOWN');

        if (in_array($code, $watchCountries, true)) {
            static::handleWatchedCountryAttack($ip, $code, $geo['name'] ?? 'غير محدد', $eventType, $request);
            return true;
        }

        return false;
    }

    /**
     * Dispatch instant alert to super admin with throttling to avoid log flooding.
     */
    protected static function dispatchInstantAlert(
        string $title,
        string $message,
        string $threatType,
        string $severity,
        string $ip
    ): void {
        try {
            $throttleKey = "threat_alert:{$threatType}:{$ip}";
            if (Cache::add($throttleKey, true, now()->addMinutes(5))) {
                NotificationService::sendToRole(
                    'super_admin',
                    null,
                    $title,
                    $message,
                    'security_alert',
                    '🚨',
                    url('/platform/security')
                );
            }
        } catch (\Throwable $e) {
            Log::warning("Error dispatching threat engine alert: " . $e->getMessage());
        }
    }
}
