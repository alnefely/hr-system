<?php

namespace App\Models\Concerns;

use App\Services\Cache\CacheService;
use App\Services\Cache\Keys\DashboardCache;

/**
 * أي موديل بيدخل في أرقام لوحة التحكم بيلغي كاشها لما يتحفظ أو يتمسح.
 *
 * اللوحة متكاشة ٥ دقايق (DashboardService)، فمن غير ده الفاتورة الجديدة
 * ماكانتش هتظهر غير بعد ما الكاش يخلص.
 */
trait FlushesDashboardCache
{
    public static function bootFlushesDashboardCache(): void
    {
        static::saved(fn () => static::flushDashboardCache());
        static::deleted(fn () => static::flushDashboardCache());
    }

    public static function flushDashboardCache(): void
    {
        app(CacheService::class)->invalidate(DashboardCache::class);
    }
}
