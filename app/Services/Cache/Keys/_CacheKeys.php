<?php

namespace App\Services\Cache\Keys;

use LogicException;

/**
 * الأساس اللي كل مجموعة كاش بتورّثه.
 *
 * كل مجموعة ليها ملف واحد في Keys/ فيه اسمها وكل مفاتيحها:
 *
 *     final class SettingsCache extends _CacheKeys
 *     {
 *         public const GROUP   = 'settings';
 *         public const GROUPED = 'grouped';
 *
 *         public static function value(string $key): string
 *         {
 *             return "value.{$key}";
 *         }
 *     }
 *
 * والاستخدام:
 *
 *     $cache->remember(SettingsCache::class, SettingsCache::GROUPED, fn () => ...);
 *     $cache->invalidate(SettingsCache::class);
 */
abstract class _CacheKeys
{
    /** اسم المجموعة — لازم كل كلاس وارث يعرّفها */
    public const GROUP = '';

    /**
     * اسم المجموعة كنص.
     */
    public static function name(): string
    {
        if (static::GROUP === '') {
            throw new LogicException(static::class . ' لازم تعرّف ثابت GROUP.');
        }

        return static::GROUP;
    }

    /**
     * مجموعة فرعية لسجل معيّن — عشان تلغيه لوحده.
     *
     *   $cache->invalidate(RoleCache::for(5));
     */
    public static function for(string|int $id): string
    {
        return static::name() . ".{$id}";
    }
}
