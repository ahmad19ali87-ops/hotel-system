<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
        $middleware->alias([
            'hotel_admin' => App\Http\Middleware\HotelAdmin::class,
            'check.hotel' => App\Http\Middleware\CheckHotelAccess::class,
            'role' => App\Http\Middleware\RoleMiddleware::class,
            'hotel.scope' => App\Http\Middleware\EnsureHotelScope::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            $code = $e->getStatusCode();
            $msg = $e->getMessage() ?: 'العملية غير متاحة أو أن المسار غير معرف، يرجى مراجعة مشغل البرنامج.';

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'code' => $code,
                    'message' => $msg,
                ], $code);
            }

            if (view()->exists("errors.{$code}")) {
                return response()->view("errors.{$code}", ['exception' => $e, 'message' => $msg], $code);
            }

            return response()->view('errors.layout', [
                'title' => 'تنبيه: تعذر إكمال العملية',
                'code' => $code,
                'icon' => '⚠️',
                'message' => $msg . ' (يرجى مراجعة مشغل البرنامج في حال وجود استفسار).',
            ], $code);
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'غير مصرح لك بتنفيذ هذا الأمر، يرجى مراجعة مشغل البرنامج.',
                ], 403);
            }
            return response()->view('errors.403', ['message' => 'غير مصرح لك بتنفيذ هذا الأمر، يرجى مراجعة مشغل البرنامج.'], 403);
        });

        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'حدث خطأ في النظام، يرجى مراجعة مشغل البرنامج.',
                ], 500);
            }

            return response()->view('errors.500', [
                'message' => 'حدث خطأ غير متوقع أثناء تنفيذ العملية، يرجى مراجعة مشغل البرنامج.',
            ], 500);
        });
    })
    ->create();
