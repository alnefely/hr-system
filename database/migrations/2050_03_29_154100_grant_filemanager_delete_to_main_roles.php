<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * صلاحية filemanager_delete الجديدة (حذف/تسمية/نقل في مدير الملفات).
 *
 * قبلها كانت "عرض مدير الملفات" بتسمح بكل ده. الأدوار الرئيسية بتاخدها
 * تلقائياً عشان المدير العام مايفقدش حاجة؛ باقي الأدوار بتتمنح من شاشة
 * الأدوار حسب الحاجة.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->where('main', true)->get(['id', 'roles'])->each(function ($role) {
            $permissions = json_decode($role->roles ?? '[]', true) ?: [];

            if (! in_array('filemanager_delete', $permissions, true)) {
                $permissions[] = 'filemanager_delete';

                DB::table('roles')->where('id', $role->id)->update(['roles' => json_encode($permissions)]);
            }
        });

        // الكاش بيتلغي مع أحداث الموديل، والتعديل المباشر مابيطلقهاش
        \App\Models\Role::ClearCache();
    }

    public function down(): void
    {
    }
};
