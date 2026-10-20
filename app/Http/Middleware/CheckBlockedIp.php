<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBlockedIp
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        if ($ip && BlockedIp::isBlocked($ip)) {
            $block = BlockedIp::getActiveBlock($ip);
            $reason = $block ? $block->reason : 'نشاط أمني غير مصرح به';
            $blockedUntil = $block && $block->blocked_until ? $block->blocked_until->toDateTimeString() : 'إشعار آخر';

            // Log attempt from blocked IP
            SecurityLog::record(
                'blocked_ip_access_attempt',
                'warning',
                "Blocked IP {$ip} attempted to access {$request->fullUrl()}",
                $request
            );

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'code' => 403,
                    'message' => "تم حظر عنوان IP الخاص بك ({$ip}) لأسباب أمنية: {$reason}. الحظر مستمر حتى: {$blockedUntil}.",
                ], 403);
            }

            return response()->view('errors.403', [
                'message' => "تم حظر هذا الجهاز ({$ip}) لأسباب أمنية متعلقة بحماية النظام: {$reason}. الحظر سارٍ حتى: {$blockedUntil}.",
            ], 403);
        }

        return $next($request);
    }
}

