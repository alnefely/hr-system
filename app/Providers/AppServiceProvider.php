<?php

namespace App\Providers;

use App\Services\Cache\CacheService;
use App\Services\Role\RoleService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CacheService::class);
        $this->app->singleton(RoleService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        Paginator::useBootstrap();
    }


    protected function configureRateLimiting(): void
    {
        RateLimiter::for('WebRateLimiter', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('ApiRateLimiter', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip())
            ->response(fn() => response()->json([
                'message' => 'لقد تجاوزت الحد المسموح به من الطلبات. يرجى المحاولة مرة أخرى لاحقًا.',
            ], 429));
        });
    }
    
}
