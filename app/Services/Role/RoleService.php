<?php

namespace App\Services\Role;

use App\Models\Admin;
use App\Models\Role as RoleModel;
use App\Services\Cache\CacheService;
use App\Services\Cache\Keys\RoleCache;

/**
 * كل حاجة ليها علاقة بصلاحيات المشرفين.
 *
 * المصدر الوحيد لتعريف الصلاحيات — أي صلاحية جديدة بتتضاف في map().
 *
 * في الـ Blade استخدم الاختصارات (admin_can, permissions...)،
 * وفي الـ PHP استخدم الكلاس مباشرة.
 */
class RoleService
{
    /**
     * ذاكرة داخل نفس الـ Request.
     *
     * admin_can() بتتنادى أكتر من ١٢ مرة في الصفحة الواحدة،
     * ومن غير ده كنا هنضرب الكاش في كل مرة.
     */
    protected ?array $roles = null;

    public function __construct(
        protected CacheService $cache
    ) {}

    // ========================================================
    //  تعريف الصلاحيات
    // ========================================================

    /**
     * كل مجموعات الصلاحيات.
     *
     * المفتاح  = اسم المجموعة (يستخدم في الـ HTML كـ data-group)
     * label    = مفتاح الترجمة
     * items    = الصلاحيات جوّاها
     */
    public function map(): array
    {
        return [
            'dashboard' => [
                'label' => 'dashboard.title',
                'items' => ['dashboard_show'],
            ],
            'filemanager' => [
                'label' => 'lfm.title',
                'items' => ['filemanager_show'],
            ],
            'admin' => [
                'label' => 'admin.title',
                'items' => [
                    'admin_show',
                    'admin_create',
                    'admin_update',
                    'admin_delete',
                ],
            ],
            'profile' => [
                'label' => 'admin.profile',
                'items' => [
                    'admin_profile_show',
                    'admin_profile_update',
                ],
            ],
            'role' => [
                'label' => 'role.title',
                'items' => [
                    'role_show',
                    'role_create',
                    'role_update',
                    'role_delete',
                ],
            ],
            'setting' => [
                'label' => 'setting.title',
                'items' => [
                    'setting_show',
                    'setting_update',
                    'setting_clear_cache',
                ],
            ],
        ];
    }

    /**
     * كل الصلاحيات في ليستة واحدة مسطّحة.
     *
     *   ['dashboard_show', 'admin_show', ...]
     */
    public function all(): array
    {
        return array_values(array_unique(
            array_merge(...array_column($this->map(), 'items'))
        ));
    }

    /**
     * الصلاحيات جاهزة للعرض في شاشة الأدوار.
     *
     *   [
     *     'admin' => [
     *         'label'       => 'المشرفين',
     *         'permissions' => [['value' => 'admin_show', 'label' => 'عرض'], ...],
     *     ],
     *   ]
     */
    public function grouped(): array
    {
        $grouped = [];

        foreach ($this->map() as $key => $group) {
            $grouped[$key] = [
                'label'       => __($group['label']),
                'permissions' => array_map(fn (string $permission) => [
                    'value' => $permission,
                    'label' => $this->label($permission),
                ], $group['items']),
            ];
        }

        return $grouped;
    }

    /**
     * اسم الإجراء المعروض جنب الصلاحية (عرض / إضافة / تعديل / حذف).
     */
    protected function label(string $permission): string
    {
        foreach (['update', 'delete', 'create', 'show'] as $action) {
            if (str_contains($permission, $action)) {
                return __("role.action.{$action}");
            }
        }

        return __("role.action.{$permission}");
    }

    // ========================================================
    //  المشرف الحالي
    // ========================================================

    /**
     * المشرف الحالي.
     */
    public function admin(): ?Admin
    {
        return auth('admin')->user();
    }

    /**
     * صلاحيات المشرف الحالي.
     *
     * متكاشة لكل دور على حدة (مش كل الأدوار في مفتاح واحد).
     */
    public function roles(): array
    {
        if ($this->roles !== null) {
            return $this->roles;
        }

        $roleId = $this->admin()?->role_id;

        if ($roleId === null) {
            return $this->roles = [];
        }

        return $this->roles = $this->cache->remember(
            RoleCache::class,
            RoleCache::role($roleId),
            fn () => RoleModel::find($roleId)?->roles ?? []
        );
    }

    /**
     * هل المشرف الحالي يملك الصلاحية دي؟
     */
    public function can(string $permission): bool
    {
        return in_array($permission, $this->roles(), true);
    }
}
