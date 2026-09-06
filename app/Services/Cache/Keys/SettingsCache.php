<?php

namespace App\Services\Cache\Keys;


/**
 * كل مفاتيح كاش الإعدادات.
 *
 * أي حاجة بتتكاش من موديل Settings لازم يبقى ليها مفتاح هنا.
 */
final class SettingsCache extends _CacheKeys
{
    public const GROUP = 'settings';

    // ========================================================
    //  مفاتيح ثابتة
    // ========================================================

    /** كل الإعدادات مجمّعة حسب الـ group (من غير الـ sections) */
    public const GROUPED = 'grouped';

    /** أسماء كل الـ groups الموجودة */
    public const ALL_GROUPS = 'all_groups';

    // ========================================================
    //  مفاتيح ديناميكية
    // ========================================================

    /**
     * قيمة إعداد واحد.
     *
     *   SettingsCache::value('site_name')  →  "value.site_name"
     */
    public static function value(string $key): string
    {
        return "{$key}";
    }

    /**
     * إعدادات group واحد.
     *
     *   SettingsCache::group('mail')  →  "group.mail"
     */
    public static function group(string $group): string
    {
        return "group.{$group}";
    }
}
