<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, $permissionName = null): Response
    {

        $admin = Admin();

        if (!$admin) {
            return redirect()->route('admin.login')->with('error', __('auth.login_alert'));
        }

        // السماح للـ ajax
        if ($permissionName === 'ajax') {
            return $next($request);
        }

        // التحقق مما إذا كان هذا الـ Middleware مخصصاً لـ Action مختلف في مسارات الـ Resource
        if ($request->route()) {
            $route = $request->route();
            $routeMiddleware = $route->middleware();
            $actionMethod = $route->getActionMethod();

            // معرفة ما إذا كانت هناك مصفوفة مفاتيح نصية (Associative) لتحديد صلاحيات لكل Action
            $hasAssociative = false;
            foreach ($routeMiddleware as $key => $val) {
                if (is_string($key)) {
                    $hasAssociative = true;
                    break;
                }
            }

            if ($hasAssociative) {
                $matches = function ($middlewareValue) use ($permissionName) {
                    if (!is_string($middlewareValue)) {
                        return false;
                    }
                    $parts = explode(':', $middlewareValue);
                    if (count($parts) < 2) {
                        return false;
                    }
                    $name = trim($parts[0]);
                    $parameter = trim($parts[1]);
                    
                    if ($parameter !== $permissionName) {
                        return false;
                    }
                    
                    return $name === 'AdminPermission' 
                        || $name === \App\Http\Middleware\AdminPermission::class
                        || $name === '\\' . \App\Http\Middleware\AdminPermission::class;
                };

                $hasExactKey = array_key_exists($actionMethod, $routeMiddleware);
                $shouldRun = false;

                if ($hasExactKey) {
                    if ($matches($routeMiddleware[$actionMethod])) {
                        $shouldRun = true;
                    }
                } else {
                    // وراثة الصلاحية للإجراءات الفرعية
                    if ($actionMethod === 'create' && isset($routeMiddleware['store'])) {
                        if ($matches($routeMiddleware['store'])) {
                            $shouldRun = true;
                        }
                    } elseif ($actionMethod === 'edit' && isset($routeMiddleware['update'])) {
                        if ($matches($routeMiddleware['update'])) {
                            $shouldRun = true;
                        }
                    }
                }

                // إذا كان هذا الـ Middleware غير مخصص للـ Action الحالي، نتخطاه
                if (!$shouldRun) {
                    return $next($request);
                }
            }
        }

        $permissions = admin_roles();

        // تأكيد إنها array
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?? [];
        }

        // التحقق من الصلاحية
        if ($permissionName && !in_array($permissionName, $permissions)) {
            // abort(403, __('error.403'));
            return redirect()->route('admin.error_403')->with('error', __('alert.403_message'));
        }

        return $next($request);
    }
}
