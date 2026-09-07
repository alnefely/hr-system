<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\JobTitle;
use App\Models\Role;
use App\Services\HR\EmployeeService;
use Illuminate\Database\Seeder;

class SampleEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(EmployeeService::class);

        $cairoBranch  = Branch::where('code', 'EG-01')->first() ?? Branch::first();
        $riyadhBranch = Branch::where('code', 'SA-01')->first() ?? Branch::find(2) ?? $cairoBranch;
        $muscatBranch = Branch::where('code', 'OM-01')->first() ?? Branch::find(3) ?? $cairoBranch;

        $generalManagerTitle = JobTitle::where('name', 'like', '%مدير عام%')->first() ?? JobTitle::first();
        $hrManagerTitle      = JobTitle::where('name', 'like', '%موارد بشرية%')->first() ?? JobTitle::find(2) ?? $generalManagerTitle;
        $softwareDevTitle    = JobTitle::where('name', 'like', '%برمجيات%')->first() ?? JobTitle::find(5) ?? $generalManagerTitle;
        $uiuxTitle           = JobTitle::where('name', 'like', '%UI%')->first() ?? JobTitle::find(6) ?? $generalManagerTitle;
        $marketingTitle      = JobTitle::where('name', 'like', '%تسويق%')->first() ?? JobTitle::find(7) ?? $generalManagerTitle;

        $fullRole = Role::first();

        // 1. المدير العام - فرع القاهرة
        $gm = $service->create([
            'name'                            => 'عبد الرحمن محمد النفيلى',
            'national_id'                     => '29001010101234',
            'birth_date'                      => '1990-05-15',
            'gender'                          => 'male',
            'marital_status'                  => 'married',
            'nationality'                     => 'مصري',
            'phone'                           => '+201012345671',
            'phone_country'                   => 'EG',
            'alt_phone'                       => '+201112345671',
            'alt_phone_country'               => 'EG',
            'personal_email'                  => 'elnafily@sbbs.com',
            'emergency_contact_name'          => 'محمد النفيلى',
            'emergency_contact_phone'         => '+201212345671',
            'emergency_contact_phone_country' => 'EG',
            'address'                         => 'القاهرة الجديدة، التجمع الأول، مصر',
            'branch_id'                       => $cairoBranch->id,
            'job_title_id'                    => $generalManagerTitle->id,
            'manager_id'                      => null,
            'hire_date'                       => '2023-01-01',
            'probation_end_date'              => '2023-04-01',
            'contract_type'                   => 'full_time',
            'status'                          => 'active',
            'notes'                           => 'المدير التنفيذي العام لمجموعة الفروع',
            'basic_salary'                    => 45000,
            'bank_name'                       => 'البنك الأهلي المصري (NBE)',
            'bank_account_number'             => 'EG120003000000012345678901',
            'insurance_number'                => 'INS-9001234',
            'annual_leave_entitlement'        => 30,
            'monthly_permission_hours_limit'  => 6,
            'daily_work_hours'                => 8,
            'custom_hourly_rate'              => 250,
            'allowances' => [
                ['name' => 'بدل سكن وتنقلات تنفيذية', 'amount' => 10000],
                ['name' => 'بدل تمثيل ومسؤولية', 'amount' => 5000],
            ],
            'create_account'                  => '1',
            'account_email'                   => 'elnafily.admin@sbbs.com',
            'account_password'                => 'password123',
            'role_id'                         => $fullRole?->id,
            'documents' => [
                ['title' => 'صورة بطاقة الرقم القومي', 'file_path' => '/assets/docs/id_elnafily.pdf'],
                ['title' => 'عقد العمل المعتمد', 'file_path' => '/assets/docs/contract_elnafily.pdf'],
            ],
        ]);

        // 2. مديرة الموارد البشرية - فرع القاهرة
        $hrManager = $service->create([
            'name'                            => 'نهال نبيل عبد الفتاح',
            'national_id'                     => '29304120105678',
            'birth_date'                      => '1993-04-12',
            'gender'                          => 'female',
            'marital_status'                  => 'single',
            'nationality'                     => 'مصرية',
            'phone'                           => '+201023456782',
            'phone_country'                   => 'EG',
            'personal_email'                  => 'nehal.nabil@sbbs.com',
            'emergency_contact_name'          => 'نبيل عبد الفتاح',
            'emergency_contact_phone'         => '+201123456782',
            'emergency_contact_phone_country' => 'EG',
            'address'                         => 'مدينة نصر، القاهرة',
            'branch_id'                       => $cairoBranch->id,
            'job_title_id'                    => $hrManagerTitle->id,
            'manager_id'                      => $gm->id,
            'hire_date'                       => '2023-06-01',
            'probation_end_date'              => '2023-09-01',
            'contract_type'                   => 'full_time',
            'status'                          => 'active',
            'notes'                           => 'إدارة عمليات الموارد البشرية والرواتب والتوظيف',
            'basic_salary'                    => 25000,
            'bank_name'                       => 'بنك CIB مصر',
            'bank_account_number'             => 'EG440010000000098765432101',
            'insurance_number'                => 'INS-9305678',
            'annual_leave_entitlement'        => 21,
            'monthly_permission_hours_limit'  => 4,
            'daily_work_hours'                => 8,
            'custom_hourly_rate'              => 150,
            'allowances' => [
                ['name' => 'بدل انتقال', 'amount' => 3000],
                ['name' => 'بدل اتصالات وإنترنت', 'amount' => 1000],
            ],
            'create_account'                  => '1',
            'account_email'                   => 'hr.nehal@sbbs.com',
            'account_password'                => 'password123',
            'role_id'                         => $fullRole?->id,
            'documents' => [
                ['title' => 'المؤهل الجامعي', 'file_path' => '/assets/docs/degree_nehal.pdf'],
            ],
        ]);

        // 3. كبير مهندسي البرمجيات - فرع القاهرة
        $dev = $service->create([
            'name'                            => 'نور مجدي أحمد عبد الفتاح',
            'national_id'                     => '29608150109988',
            'birth_date'                      => '1996-08-15',
            'gender'                          => 'male',
            'marital_status'                  => 'married',
            'nationality'                     => 'مصري',
            'phone'                           => '+201034567893',
            'phone_country'                   => 'EG',
            'personal_email'                  => 'nour.magdy@sbbs.com',
            'emergency_contact_name'          => 'مجدي أحمد',
            'emergency_contact_phone'         => '+201234567893',
            'emergency_contact_phone_country' => 'EG',
            'address'                         => 'المعادي، القاهرة',
            'branch_id'                       => $cairoBranch->id,
            'job_title_id'                    => $softwareDevTitle->id,
            'manager_id'                      => $gm->id,
            'hire_date'                       => '2024-02-01',
            'probation_end_date'              => '2024-05-01',
            'contract_type'                   => 'full_time',
            'status'                          => 'active',
            'notes'                           => 'مسؤول تطوير وتكامل الأنظمة وتطبيقات الويب والموبايل',
            'basic_salary'                    => 28000,
            'bank_name'                       => 'بنك مصر',
            'bank_account_number'             => 'EG880002000000055443322101',
            'insurance_number'                => 'INS-9609988',
            'annual_leave_entitlement'        => 21,
            'monthly_permission_hours_limit'  => 4,
            'daily_work_hours'                => 8,
            'custom_hourly_rate'              => 175,
            'allowances' => [
                ['name' => 'بدل طبيعة عمل تقني', 'amount' => 4000],
                ['name' => 'بدل انتقال', 'amount' => 2000],
            ],
            'create_account'                  => '1',
            'account_email'                   => 'dev.nour@sbbs.com',
            'account_password'                => 'password123',
            'role_id'                         => $fullRole?->id,
            'documents' => [
                ['title' => 'شهادات الخبرة التقنية', 'file_path' => '/assets/docs/certs_nour.pdf'],
            ],
        ]);

        // 4. مصممة UI/UX - فرع الرياض (السعودية)
        $uiux = $service->create([
            'name'                            => 'باربرا عادل جورج',
            'national_id'                     => '2456789012',
            'birth_date'                      => '1995-11-20',
            'gender'                          => 'female',
            'marital_status'                  => 'single',
            'nationality'                     => 'مصرية (إقامة سعودية)',
            'phone'                           => '+966512345674',
            'phone_country'                   => 'SA',
            'personal_email'                  => 'barbara.adel@sbbs.com',
            'emergency_contact_name'          => 'عادل جورج',
            'emergency_contact_phone'         => '+966598765432',
            'emergency_contact_phone_country' => 'SA',
            'address'                         => 'حي العليا، الرياض، المملكة العربية السعودية',
            'branch_id'                       => $riyadhBranch->id,
            'job_title_id'                    => $uiuxTitle->id,
            'manager_id'                      => $gm->id,
            'hire_date'                       => '2024-05-15',
            'probation_end_date'              => '2024-08-15',
            'contract_type'                   => 'full_time',
            'status'                          => 'active',
            'notes'                           => 'تصميم واجهات المستخدم للأنظمة وتطبيقات الجوال',
            'basic_salary'                    => 9500,
            'bank_name'                       => 'مصرف الراجحي',
            'bank_account_number'             => 'SA4480000432608010123456',
            'insurance_number'                => 'GOSI-88776655',
            'annual_leave_entitlement'        => 30,
            'monthly_permission_hours_limit'  => 4,
            'daily_work_hours'                => 8,
            'custom_hourly_rate'              => 60,
            'allowances' => [
                ['name' => 'بدل سكن شهري', 'amount' => 2500],
                ['name' => 'بدل مواصلات', 'amount' => 1000],
            ],
            'create_account'                  => '0',
            'documents' => [
                ['title' => 'صورة الإقامة النظامية', 'file_path' => '/assets/docs/iqama_barbara.pdf'],
            ],
        ]);

        // 5. مسؤول تسويق رقمي - فرع مسقط (عُمان)
        $marketing = $service->create([
            'name'                            => 'عمر عاصم عبد العزيز محمود',
            'national_id'                     => '987654321',
            'birth_date'                      => '1997-03-10',
            'gender'                          => 'male',
            'marital_status'                  => 'single',
            'nationality'                     => 'عُماني',
            'phone'                           => '+96891234567',
            'phone_country'                   => 'OM',
            'personal_email'                  => 'omer.asem@sbbs.com',
            'emergency_contact_name'          => 'عاصم عبد العزيز',
            'emergency_contact_phone'         => '+96898765432',
            'emergency_contact_phone_country' => 'OM',
            'address'                         => 'شاطئ القرم، مسقط، سلطنة عُمان',
            'branch_id'                       => $muscatBranch->id,
            'job_title_id'                    => $marketingTitle->id,
            'manager_id'                      => $gm->id,
            'hire_date'                       => '2025-01-10',
            'probation_end_date'              => '2025-04-10',
            'contract_type'                   => 'full_time',
            'status'                          => 'active',
            'notes'                           => 'إدارة الحملات الإعلانية وتحليل الأداء والنمو',
            'basic_salary'                    => 850,
            'bank_name'                       => 'بنك مسقط',
            'bank_account_number'             => 'OM3200100000000123456789',
            'insurance_number'                => 'PASI-11223344',
            'annual_leave_entitlement'        => 30,
            'monthly_permission_hours_limit'  => 4,
            'daily_work_hours'                => 8,
            'custom_hourly_rate'              => 5.5,
            'allowances' => [
                ['name' => 'علاوة انتقال وتغذية', 'amount' => 150],
                ['name' => 'علاوة سكن', 'amount' => 200],
            ],
            'create_account'                  => '0',
            'documents' => [
                ['title' => 'البطاقة المدنية', 'file_path' => '/assets/docs/id_omer.pdf'],
            ],
        ]);
    }
}
