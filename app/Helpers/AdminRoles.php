<?php

use App\Services\Role\RoleService;

/*
|--------------------------------------------------------------------------
| دوال الصلاحيات
|--------------------------------------------------------------------------
| المنطق كله في App\Services\Role\RoleService.
| الدوال دي اختصارات بس، موجودة عشان الـ Blade يفضل قصير:
|
|     @if (admin_can('role_show'))
|
| مفيش هنا أي منطق — كل واحدة بتنادي الكلاس وخلاص.
*/

if (! function_exists('admin')) {
    /** المشرف الحالي */
    function admin()
    {
        return app(RoleService::class)->admin();
    }
}

if (! function_exists('admin_can')) {
    /** هل المشرف الحالي يملك الصلاحية دي؟ */
    function admin_can(string $permission): bool
    {
        return app(RoleService::class)->can($permission);
    }
}

if (! function_exists('admin_roles')) {
    /** صلاحيات المشرف الحالي */
    function admin_roles(): array
    {
        return app(RoleService::class)->roles();
    }
}

if (! function_exists('all_roles')) {
    /** كل الصلاحيات المعرّفة في المشروع */
    function all_roles(): array
    {
        return app(RoleService::class)->all();
    }
}

if (! function_exists('grouped_roles')) {
    /** الصلاحيات مجمّعة وجاهزة لشاشة الأدوار */
    function grouped_roles(): array
    {
        return app(RoleService::class)->grouped();
    }
}
