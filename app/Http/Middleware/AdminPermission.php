<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * التحقق من صلاحية المشرف على المسار.
 *
 *     ->middleware('AdminPermission:sale_show')
 *
 * مسارات الـ resource بتحدد صلاحية كل إجراء بـ middlewareFor():
 *
 *     Route::resource('/sales', SaleController::class)
 *         ->middlewareFor('index', 'AdminPermission:sale_show')
 *         ->middlewareFor(['create', 'store'], 'AdminPermission:sale_create');
 *
 * (قبل كده الـ middleware كان بيفلتر بنفسه مصفوفة مفاتيحها أسماء
 * الإجراءات، وكل الصلاحيات كانت بتتنفّذ على كل طلب.)
 */
class AdminPermission
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $permissionName = null): Response
    {
        if (! admin()) {
            return redirect()->route('admin.login')->with('error', __('auth.login_alert'));
        }

        // تسجيل الدخول كفاية لطلبات AJAX العامة
        if ($permissionName === null || $permissionName === 'ajax') {
            return $next($request);
        }

        if (! admin_can($permissionName)) {
            return redirect()->route('admin.error_403')->with('error', __('alert.403_message'));
        }

        return $next($request);
    }
}
