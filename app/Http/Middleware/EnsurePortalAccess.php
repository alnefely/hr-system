<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * التحقق من أن الموظف لسه مسموح له بالبوابة — مع كل طلب، مش وقت الدخول بس.
 *
 * لو الموارد البشرية أوقفت الحساب (أو الموظف ترك العمل) وهو فاتح البوابة،
 * بيتطلّع فوراً في أول طلب، ويشوف السبب في شاشة الدخول.
 */
class EnsurePortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $employee = auth('employee')->user();

        if (! $employee) {
            return redirect()->route('portal.login');
        }

        if ($reason = $employee->portalBlockReason()) {
            auth('employee')->logout();

            return redirect()->route('portal.login')->with('blocked', $reason);
        }

        // أول دخول (أو بعد إعادة تعيين من الموارد البشرية): لازم يغيّر كلمة المرور
        if ($employee->must_change_password && ! $request->routeIs('portal.password.*', 'portal.logout')) {
            return redirect()->route('portal.password.edit');
        }

        return $next($request);
    }
}
