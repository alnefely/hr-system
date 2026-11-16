<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Services\Role\RolePresets;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * الأدوار الأساسية + الأدوار الجاهزة حسب التخصص.
     *
     * الترتيب مهم: الصلاحية الكاملة رقم 1 والاحتياطية رقم 2 — الكود
     * بيعتمد على الرقم 2 لنقل المشرفين لما دورهم يتحذف.
     *
     * firstOrCreate بالاسم: إعادة التشغيل مابتكررش الأدوار ولا بتلغي
     * أي تعديل اتعمل عليها من شاشة الأدوار.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'صلاحية كاملة'], [
            'description' => 'صلاحية كاملة على كل أقسام لوحة التحكم بدون أي قيود',
            'main'        => true,
            'roles'       => all_roles(),
        ]);

        Role::firstOrCreate(['name' => 'صلاحية احتياطية'], [
            'description' => 'صلاحية محدودة للعرض فقط — بتتنقل ليها حسابات المشرفين عند حذف صلاحيتهم',
            'main'        => false,
            'roles'       => [
                'dashboard_show',
                'admin_profile_show',
            ],
        ]);

        foreach (RolePresets::all() as $preset) {
            Role::firstOrCreate(['name' => $preset['name']], [
                'description' => $preset['description'],
                'main'        => false,
                'roles'       => $preset['permissions'],
            ]);
        }
    }
}
