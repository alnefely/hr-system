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
        // الجلسة بتتذكّر آخر دومين اتسجّل، فالكاش (اللي ممكن يكون جدول في
        // قاعدة البيانات) بيتلمس مرة لكل جلسة بدل مع كل طلب.
        $root = $request->root();

        if (! $request->hasSession() || $request->session()->get(RootUrl::CACHE_KEY) !== $root) {
            RootUrl::remember($root);

            if ($request->hasSession()) {
                $request->session()->put(RootUrl::CACHE_KEY, $root);
            }
        }

        return $next($request);
    }
}
