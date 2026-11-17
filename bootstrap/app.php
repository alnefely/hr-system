<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [__DIR__.'/../routes/web.php', __DIR__.'/../routes/portal.php'],
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'AdminPermission' => App\Http\Middleware\AdminPermission::class,
            'portal.access'   => App\Http\Middleware\EnsurePortalAccess::class,
        ]);

        // غير المسجّل يروح لشاشة الدخول المناسبة للمسار اللي طلبه
        $middleware->redirectGuestsTo(fn ($request) => $request->is('portal', 'portal/*')
            ? route('portal.login')
            : route('admin.login'));

        // المسجّل اللي يفتح شاشة الدخول يروح لرئيسيته
        $middleware->redirectUsersTo(fn ($request) => $request->is('portal', 'portal/*')
            ? route('portal.home')
            : route('admin.home'));

        // دومين النظام للأوامر المجدولة والطابور بدل APP_URL
        $middleware->web(append: [
            App\Http\Middleware\RememberRootUrl::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
