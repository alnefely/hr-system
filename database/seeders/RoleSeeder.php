<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create([
            'name'        => 'صلاحية كاملة',
            'description' => 'صلاحية كاملة على كل أقسام لوحة التحكم بدون أي قيود',
            'main'        => true,
            'roles' => all_roles(),
            'created_at'=> now(),
            'updated_at'=> now(),
        ]);

        Role::create([
            'name'        => 'صلاحية احتياطية',
            'description' => 'صلاحية محدودة للعرض فقط — بتتنقل ليها حسابات المشرفين عند حذف صلاحيتهم',
            'main'        => false,
            'roles' => [
                'dashboard_show',
                'admin_profile_show',
            ],
            'created_at'=> now(),
            'updated_at'=> now(),
        ]);

    }
}
