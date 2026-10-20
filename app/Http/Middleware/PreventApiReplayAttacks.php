<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PreventApiReplayAttacks
{
    /**
     * Handle an incoming request for API replay and duplicate attack prevention.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to state-changing methods on API endpoints
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // 1. Verify Timestamp Header if provided (anti-replay window)
        $timestamp = $request->header('X-Timestamp');
        if ($timestamp) {
            $currentTime = time();
            $requestTime = (int) $timestamp;

            // Allow maximum 5 minutes (300 seconds) clock drift
            if (abs($currentTime - $requestTime) > 300) {
                SecurityLog::record(
                    'replay_attack_expired_timestamp',
                    'warning',
                    "Request rejected due to expired or drifted timestamp. Current: {$currentTime}, Request: {$requestTime}",
                    $request
                );

                return response()->json([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'تم رفض الطلب: توقيت الطلب منتهي الصلاحية أو غير متطابق زمنياً (Clock skew / Expired request).',
                ], 400);
            }
        }

        // 2. Nonce / Idempotency Key validation
        $nonce = $request->header('X-Nonce') ?: $request->header('X-Idempotency-Key');
        if ($nonce) {
            $nonceCacheKey = 'api_nonce:' . md5($nonce . '|' . ($request->user() ? $request->user()->id : $request->ip()));
            if (!Cache::add($nonceCacheKey, true, now()->addMinutes(15))) {
                SecurityLog::record(
                    'replay_attack_nonce_reused',
                    'warning',
                    "Replay attack detected: Nonce reused ({$nonce})",
                    $request
                );

                return response()->json([
                    'status' => 'error',
                    'code' => 409,
                    'message' => 'تم رفض الطلب: تم استخدام رمز التحقق أو العملية مسبقاً (Nonce Reused / Replay Attack).',
                ], 409);
            }
        } else {
            // 3. Duplicate payload lock (anti-replay spam window of 3 seconds)
            $identity = $request->user() ? ('user_' . $request->user()->id) : ('ip_' . $request->ip());
            $payloadString = json_encode($request->except(['_token', 'timestamp', 'created_at', 'id_token', 'avatar']), JSON_UNESCAPED_UNICODE);
            $fingerprint = hash('sha256', $identity . '|' . $request->method() . '|' . $request->path() . '|' . $payloadString);
            $payloadCacheKey = 'api_fp:' . $fingerprint;

            if (!Cache::add($payloadCacheKey, true, now()->addSeconds(3))) {
                SecurityLog::record(
                    'replay_attack_duplicate_payload',
                    'warning',
                    "Immediate duplicate payload submission blocked on {$request->path()}",
                    $request
                );

                return response()->json([
                    'status' => 'error',
                    'code' => 429,
                    'message' => 'يرجى الانتظار، تم إرسال نفس الطلب مسبقاً خلال أجزاء من الثانية منعاً للتكرار (Replay Protection).',
                ], 429);
            }
        }

        return $next($request);
    }
}

