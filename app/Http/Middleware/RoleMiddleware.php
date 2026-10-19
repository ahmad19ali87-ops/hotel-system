<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next, ...$roles)
{
    if (!auth()->check()) {
        abort(403, 'يجب تسجيل الدخول');
    }

    $user = auth()->user();

    $allowedRoles = $roles;
    if (in_array('super_admin', $roles, true) && !in_array('platform_admin', $allowedRoles, true)) {
        $allowedRoles[] = 'platform_admin';
    }
    if (in_array('hotel_admin', $roles, true) && !in_array('admin', $allowedRoles, true)) {
        $allowedRoles[] = 'admin';
    }
    if (in_array('admin', $roles, true) && !in_array('hotel_admin', $allowedRoles, true)) {
        $allowedRoles[] = 'hotel_admin';
    }

    // صلاحيات مالك الفندق (hotel_owner):
    // يمتلك حق الاطلاع والمعاينة الكاملة على كافة أقسام الفندق (الداشبورد، الحجوزات، المحاسبة، الفواتير، الغرف، الصيانة، الموظفين، التقارير)
    // بصيغة قراءة فقط (Read-Only) تمنع أي عمليات تعديل أو كتابة باستثناء الرسائل والبروفايل الشخصي والإشعارات.
    if ($user->role === 'hotel_owner') {
        $hotelStaffRoles = ['hotel_admin', 'admin', 'reception', 'accountant', 'maintenance', 'cleaner', 'hotel_owner'];
        $isHotelRoute = count(array_intersect($roles, $hotelStaffRoles)) > 0;

        if ($isHotelRoute) {
            if ($user->hotel_id && ! $user->hotel()->exists()) {
                abort(403, 'الفندق المرتبط بهذا الحساب غير متاح.');
            }

            // السماح بطلبات الاستعراض والقراءة فقط (GET و HEAD)
            if ($request->isMethodSafe()) {
                return $next($request);
            }

            // السماح بإرسال الرسائل الداخلية وتعديل البروفايل الشخصي وقراءة الإشعارات وتبديل اللغة
            if ($request->routeIs(['messages.*', 'notifications.*', 'profile.*', 'locale.*'])) {
                return $next($request);
            }

            abort(403, 'عذراً، صلاحيات مالك الفندق مخصصة للمعاينة والاطلاع فقط بدون إمكانية التعديل أو الحفظ.');
        }
    }

    $effectiveRoles = array_merge([$user->role], $user->permissions_list);
    if (count(array_intersect($effectiveRoles, $allowedRoles)) === 0) {
        abort(403, 'غير مسموح بالدخول هنا');
    }

    if (!in_array($user->role, ['super_admin', 'platform_admin'], true) && $user->hotel_id && ! $user->hotel()->exists()) {
        abort(403, 'الفندق المرتبط بهذا الحساب غير متاح.');
    }

    return $next($request);
}

}
