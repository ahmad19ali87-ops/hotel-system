<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\SecurityLog;
use App\Models\SiteSetting;
use App\Services\GeoIpService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityDashboardController extends Controller
{
    /**
     * Display the Security Monitoring Dashboard.
     */
    public function index(Request $request)
    {
        $since24h = Carbon::now()->subDay();

        // 1. KPI Counters (Last 24 Hours)
        $totalAttacks24h = SecurityLog::where('created_at', '>=', $since24h)->count();
        $criticalAttacks24h = SecurityLog::where('created_at', '>=', $since24h)->where('severity', 'critical')->count();

        $failedLogins24h = SecurityLog::where('created_at', '>=', $since24h)
            ->whereIn('event_type', ['failed_login_attempt', 'failed_verification_code', 'failed_web_verification_code', 'brute_force_verify_locked'])
            ->count();

        $maliciousFiles24h = SecurityLog::where('created_at', '>=', $since24h)
            ->where('event_type', 'malicious_file_upload')
            ->count();

        $sqlInjections24h = SecurityLog::where('created_at', '>=', $since24h)
            ->where('event_type', 'sql_injection_attempt')
            ->count();

        $replayAttacks24h = SecurityLog::where('created_at', '>=', $since24h)
            ->where(function ($q) {
                $q->where('event_type', 'like', '%replay%')
                  ->orWhere('event_type', 'replay_attack_duplicate_payload');
            })
            ->count();

        $fakeGoogleLogins24h = SecurityLog::where('created_at', '>=', $since24h)
            ->whereIn('event_type', ['fake_google_token', 'fake_google_login_attempt', 'invalid_google_token', 'google_token_aud_mismatch'])
            ->count();

        $activeBlockedIpsCount = BlockedIp::where(function ($q) {
            $q->whereNull('blocked_until')->orWhere('blocked_until', '>', now());
        })->count();

        // 2. Attacks by Country (Last 24 hours)
        $attacksByCountry = SecurityLog::where('created_at', '>=', $since24h)
            ->select('country_code', 'country_name', DB::raw('count(*) as total'))
            ->groupBy('country_code', 'country_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $row->flag = GeoIpService::countryCodeToFlag($row->country_code ?? 'UNKNOWN');
                return $row;
            });

        // 3. Attacks by IP (Top Offending IPs)
        $attacksByIp = SecurityLog::where('created_at', '>=', $since24h)
            ->select('ip_address', 'country_name', 'country_code', DB::raw('count(*) as total'), DB::raw('max(created_at) as last_seen'))
            ->groupBy('ip_address', 'country_name', 'country_code')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $row->is_blocked = BlockedIp::isBlocked($row->ip_address);
                $row->flag = GeoIpService::countryCodeToFlag($row->country_code ?? 'UNKNOWN');
                return $row;
            });

        // 4. Attacks by Attack Type
        $attacksByType = SecurityLog::where('created_at', '>=', $since24h)
            ->select('event_type', DB::raw('count(*) as total'))
            ->groupBy('event_type')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                $labels = [
                    'sql_injection_attempt' => 'حقن قواعد البيانات (SQL Injection)',
                    'malicious_file_upload' => 'رفع ملفات أو برمجيات خبيثة (Malicious Files)',
                    'replay_attack_duplicate_payload' => 'إعادة إرسال مكررة (Replay Attack)',
                    'replay_attack_nonce_reused' => 'إعادة استخدام الرمز وحيد الاستخدام (Nonce Reuse)',
                    'replay_attack_expired_timestamp' => 'فارق زمني غير متطابق (Expired Timestamp)',
                    'fake_google_token' => 'رمز Google مزيف أو ملعوب به',
                    'fake_google_login_attempt' => 'تسجيل Google بدون توثيق رسمي',
                    'failed_login_attempt' => 'محاولات دخول بكلمة مرور خاطئة',
                    'failed_verification_code' => 'تخمين كود تفعيل خاطئ (API)',
                    'failed_web_verification_code' => 'تخمين كود تفعيل خاطئ (Web)',
                    'brute_force_verify_locked' => 'قفل القوة الغاشمة للتفعيل',
                    'blocked_ip_access_attempt' => 'محاولة وصول من IP محظور',
                    'failed_captcha' => 'فشل اختبار التحقق البشري (Captcha)',
                ];
                $row->label = $labels[$row->event_type] ?? $row->event_type;
                return $row;
            });

        // 5. Recent Security Incidents (Paginated or filtered)
        $query = SecurityLog::query()->latest();

        if ($request->filled('type')) {
            $query->where('event_type', $request->type);
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                  ->orWhere('country_name', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        // 6. Currently Blocked IPs
        $blockedIps = BlockedIp::where(function ($q) {
            $q->whereNull('blocked_until')->orWhere('blocked_until', '>', now());
        })->latest()->get();

        // 7. Security Settings
        $alertCountries = SiteSetting::get('security_alert_countries', 'IL,IR,RU,CN,US');
        $alertThreshold = (int) SiteSetting::get('security_alert_threshold', 5);

        return view('platform.security.index', compact(
            'totalAttacks24h',
            'criticalAttacks24h',
            'failedLogins24h',
            'maliciousFiles24h',
            'sqlInjections24h',
            'replayAttacks24h',
            'fakeGoogleLogins24h',
            'activeBlockedIpsCount',
            'attacksByCountry',
            'attacksByIp',
            'attacksByType',
            'logs',
            'blockedIps',
            'alertCountries',
            'alertThreshold'
        ));
    }

    /**
     * Manually block an IP.
     */
    public function blockIp(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|string|max:45',
            'reason' => 'nullable|string|max:255',
            'duration' => 'required|in:1,24,168,720,permanent',
        ]);

        $minutes = match ($request->duration) {
            '1' => 60,
            '24' => 1440,
            '168' => 10080,
            '720' => 43200,
            'permanent' => 0,
        };

        $reason = $request->reason ?: 'حظر يدوي بواسطة مدير المنصة';
        BlockedIp::block($request->ip_address, $reason, $minutes, request()->userAgent());

        return back()->with('success', "تم حظر عنوان IP ({$request->ip_address}) بنجاح.");
    }

    /**
     * Unblock an IP.
     */
    public function unblockIp(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|string|max:45',
        ]);

        BlockedIp::unblock($request->ip_address);

        return back()->with('success', "تم إلغاء حظر عنوان IP ({$request->ip_address}) بنجاح.");
    }

    /**
     * Update security alert settings.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'security_alert_countries' => 'nullable|string|max:255',
            'security_alert_threshold' => 'required|integer|min:1|max:100',
        ]);

        SiteSetting::set('security_alert_countries', strtoupper(trim($request->input('security_alert_countries', ''))), 'security');
        SiteSetting::set('security_alert_threshold', (string) $request->input('security_alert_threshold', '5'), 'security');

        return back()->with('success', 'تم حفظ إعدادات التنبيهات الأمنية وقائمة المراقبة بنجاح.');
    }

    /**
     * Clear old security logs.
     */
    public function clearLogs(Request $request)
    {
        $days = (int) $request->input('days', 30);
        $deleted = SecurityLog::where('created_at', '<', now()->subDays($days))->delete();

        return back()->with('success', "تم تنظيف وأرشفة {$deleted} سجلاً قديماً بنجاح.");
    }
}
