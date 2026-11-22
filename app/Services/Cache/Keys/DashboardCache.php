<?php

namespace App\Services\Cache\Keys;

/**
 * مفاتيح كاش لوحة التحكم.
 *
 * المجموعة بتتلغي مع أي حفظ/حذف لموديل بيدخل في أرقام اللوحة
 * (بص على Concerns\FlushesDashboardCache).
 */
final class DashboardCache extends _CacheKeys
{
    public const GROUP = 'dashboard';

    /**
     * بيانات اللوحة لنطاق معيّن (الفلتر + الفرع + اللغة + الصلاحيات).
     *
     *   DashboardCache::payload('full', 'abc123')  →  "payload.full.abc123"
     */
    public static function payload(string $kind, string $hash): string
    {
        return "payload.{$kind}.{$hash}";
    }
}
