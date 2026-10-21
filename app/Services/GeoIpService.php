<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoIpService
{
    /**
     * Common Arabic country names mapping.
     */
    protected static array $arabicCountries = [
        'SY' => 'الجمهورية العربية السورية',
        'LB' => 'لبنان',
        'JO' => 'الأردن',
        'IQ' => 'العراق',
        'SA' => 'المملكة العربية السعودية',
        'AE' => 'الإمارات العربية المتحدة',
        'EG' => 'مصر',
        'KW' => 'الكويت',
        'QA' => 'قطر',
        'BH' => 'البحرين',
        'OM' => 'سلطنة عمان',
        'TR' => 'تركيا',
        'RU' => 'روسيا',
        'US' => 'الولايات المتحدة',
        'DE' => 'ألمانيا',
        'FR' => 'فرنسا',
        'GB' => 'المملكة المتحدة',
        'CN' => 'الصين',
        'IR' => 'إيران',
        'IN' => 'الهند',
        'LOCAL' => 'محلي (شبكة داخلية)',
        'UNKNOWN' => 'غير محدد',
    ];

    /**
     * Resolve IP address to country info with local caching.
     */
    public static function lookup(?string $ip): array
    {
        if (empty($ip)) {
            return ['code' => 'UNKNOWN', 'name' => 'غير محدد', 'flag' => '🌐'];
        }

        // Loopback / Private IP checks
        if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.') || str_starts_with($ip, '172.16.')) {
            return [
                'code' => 'LOCAL',
                'name' => 'محلي (شبكة داخلية / الخادم)',
                'flag' => '🏠',
            ];
        }

        return Cache::remember("geoip_lookup:{$ip}", now()->addDays(14), function () use ($ip) {
            try {
                // Query ip-api.com (free, high-speed, no token required)
                $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}?fields=status,country,countryCode");

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['status'] ?? '') === 'success' && !empty($data['countryCode'])) {
                        $code = strtoupper(trim($data['countryCode']));
                        $name = static::$arabicCountries[$code] ?? ($data['country'] ?? $code);
                        return [
                            'code' => $code,
                            'name' => $name,
                            'flag' => static::countryCodeToFlag($code),
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Silently fallback without breaking
            }

            return [
                'code' => 'UNKNOWN',
                'name' => 'غير محدد',
                'flag' => '🌐',
            ];
        });
    }

    /**
     * Convert 2-letter ISO code to unicode flag emoji.
     */
    public static function countryCodeToFlag(string $code): string
    {
        if ($code === 'LOCAL') return '🏠';
        if (strlen($code) !== 2) return '🌐';

        $code = strtoupper($code);
        $firstChar = ord($code[0]) - 65 + 0x1F1E6;
        $secondChar = ord($code[1]) - 65 + 0x1F1E6;

        return mb_chr($firstChar, 'UTF-8') . mb_chr($secondChar, 'UTF-8');
    }
}

