<?php

namespace App\Services\Cache\Keys;


/**
 * كل مفاتيح كاش الأدوار والصلاحيات.
 */
final class RoleCache extends _CacheKeys
{
    public const GROUP = 'roles';

    // ========================================================
    //  مفاتيح ديناميكية
    // ========================================================

    /**
     * صلاحيات دور واحد.
     *
     *   RoleCache::role(5)  →  "role.5"
     *
     * متكاشة لكل دور على حدة — مش كل الأدوار في مفتاح واحد —
     * لأن دي بتتقرا في كل صفحة أكتر من ١٢ مرة (السايدبار والنافبار).
     */
    public static function role(int|string $roleId): string
    {
        return "role.{$roleId}";
    }
}
