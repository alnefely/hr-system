<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\JobTitle;
use App\Models\Role;
use Illuminate\Database\Seeder;

class HrSeeder extends Seeder
{
    public function run(): void
    {
        // 1. الفروع الافتراضية
        $branches = [
            [
                'name'      => 'فرع القاهرة',
                'code'      => 'EG-01',
                'country'   => 'Egypt',
                'city'      => 'القاهرة',
                'address'   => 'مدينة نصر، القاهرة',
                'currency'  => 'EGP',
                'timezone'  => 'Africa/Cairo',
                'is_active' => true,
            ],
            [
                'name'      => 'فرع الرياض',
                'code'      => 'SA-01',
                'country'   => 'Saudi Arabia',
                'city'      => 'الرياض',
                'address'   => 'طريق الملك فهد، الرياض',
                'currency'  => 'SAR',
                'timezone'  => 'Asia/Riyadh',
                'is_active' => true,
            ],
            [
                'name'      => 'فرع مسقط',
                'code'      => 'OM-01',
                'country'   => 'Oman',
                'city'      => 'مسقط',
                'address'   => 'الخوير، مسقط',
                'currency'  => 'OMR',
                'timezone'  => 'Asia/Muscat',
                'is_active' => true,
            ],
        ];

        foreach ($branches as $branch) {
            Branch::firstOrCreate(['code' => $branch['code']], $branch);
        }

        // 2. المسميات الوظيفية الافتراضية
        $jobTitles = [
            ['name' => 'مدير عام', 'description' => 'الإشراف العام على الشركة والقطاعات التشغيلية'],
            ['name' => 'مدير موارد بشرية', 'description' => 'إدارة شؤون الموظفين والتوظيف والتطوير المؤسسي'],
            ['name' => 'مدير مالي', 'description' => 'إدارة الحسابات، الميزانيات، والتدفقات المالية'],
            ['name' => 'مدير مشروع', 'description' => 'تخطيط ومتابعة تسليم المشاريع وفق الجداول الزمنية'],
            ['name' => 'مهندس برمجيات', 'description' => 'تطوير وصيانة الأنظمة والحلول التقنية'],
            ['name' => 'مصمم واجهات وتجربة مستخدم UI/UX', 'description' => 'تصميم الواجهات وتجارب المستخدم الرقمية'],
            ['name' => 'أخصائي تسويق رقمي', 'description' => 'إدارة الحملات التسويقية والنمو الرقمي'],
        ];

        foreach ($jobTitles as $job) {
            JobTitle::firstOrCreate(['name' => $job['name']], $job);
        }

        // 3. تحديث الدور الرئيسي بكافة الصلاحيات الجديدة
        $fullRole = Role::find(1);
        if ($fullRole) {
            $fullRole->roles = all_roles();
            $fullRole->save();
        }

        Role::ClearCache();
    }
}
