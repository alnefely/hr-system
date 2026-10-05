<?php

namespace App\Http\Middleware;

use App\Support\RootUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * تسجيل دومين النظام من طلبات المتصفح.
 *
 * الأوامر المجدولة والطابور بيستخدموه بدل APP_URL — شوف RootUrl.
 */
class RememberRootUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        RootUrl::remember($request->root());

        return $next($request);
    }
}
