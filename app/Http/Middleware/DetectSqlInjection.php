<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use App\Services\ThreatAutoResponseEngine;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectSqlInjection
{
    /**
     * Common SQL injection patterns.
     */
    protected array $patterns = [
        '/\bunion\s+(all\s+)?select\b/i',
        '/\bselect\b.+\bfrom\b.+\binformation_schema\b/i',
        '/;\s*(drop|truncate|alter)\s+(table|database)\b/i',
        '/\bexec\s*\(\s*xp_/i',
        '/\bsleep\s*\(\s*\d+\s*\)/i',
        '/\bbenchmark\s*\(\s*\d+\s*,/i',
        '/\bwaitfor\s+delay\b/i',
        '/(\'|"|\`)\s*or\s+1\s*=\s*1/i',
        '/(\'|"|\`)\s*or\s+(\'|")1(\'|")\s*=\s*(\'|")1(\'|")/i',
        '/\bselect\b.+\bfrom\b.+\bwhere\b.+(0x[0-9a-f]+|concat\()/i',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Don't scan password fields
        $inputs = $request->except(['password', 'password_confirmation', 'current_password']);

        if ($this->hasSqlInjection($inputs) || $this->hasSqlInjection($request->query())) {
            $ip = $request->ip() ?: '0.0.0.0';

            // Rule 1: SQL Injection -> Immediate IP Ban via Threat Auto-Response Engine
            ThreatAutoResponseEngine::handleSqlInjection($ip, "Pattern detected on {$request->fullUrl()}", $request);

            SecurityLog::record(
                'sql_injection_attempt',
                'critical',
                "Malicious SQL injection pattern detected on {$request->fullUrl()}",
                $request,
                null,
                ['action_taken' => 'ip_blocked_immediately']
            );

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'code' => 403,
                    'message' => 'تم رفض الطلب: تم اكتشاف محتوى غير آمن أو غير مصرح به (SQL Injection Protection). تم حظر الـ IP تلقائياً.',
                ], 403);
            }

            return response()->view('errors.403', [
                'message' => 'تم رفض الطلب لاكتشاف محتوى غير آمن ومحاولات حقن غير مصرح بها. تم حظر هذا الجهاز فوراً.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Recursively scan data for SQL injection patterns.
     */
    protected function hasSqlInjection(mixed $data): bool
    {
        if (is_array($data)) {
            foreach ($data as $value) {
                if ($this->hasSqlInjection($value)) {
                    return true;
                }
            }
            return false;
        }

        if (is_string($data)) {
            foreach ($this->patterns as $pattern) {
                if (preg_match($pattern, $data)) {
                    return true;
                }
            }
        }

        return false;
    }
}

