<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeDocument;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveBalance;
use App\Models\EmployeePenalty;
use App\Models\EmployeePermission;
use App\Models\EmployeeSalary;
use App\Models\EmployeeSkill;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\JobTitle;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PettyCashBalance;
use App\Models\Role;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use App\Models\VendorPayment;
use App\Models\Customer;
use App\Models\SalesChannel;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ComprehensiveDummySeeder extends Seeder
{
    public function run(): void
    {
        $admin = Admin::first() ?? Admin::create([
            'name'     => 'مدير النظام',
            'email'    => 'admin@sbbs.com',
            'password' => Hash::make('password'),
            'role_id'  => 1,
            'status'   => 'active',
        ]);

        $role = Role::first();

        // 1. Branches
        $branchesData = [
            [
                'name'      => 'الفرع الرئيسي - القاهرة الجديدة',
                'code'      => 'EG-01',
                'country'   => 'Egypt',
                'city'      => 'القاهرة الجديدة',
                'currency'  => 'EGP',
                'timezone'  => 'Africa/Cairo',
                'phone'     => '+201000000001',
                'email'     => 'cairo@sbbs.com',
                'address'   => 'شارع التسعين الشمالي، التجمع الخامس، القاهرة، مصر',
                'is_active' => true,
            ],
            [
                'name'      => 'فرع الإسكندرية - سموحة',
                'code'      => 'EG-02',
                'country'   => 'Egypt',
                'city'      => 'الإسكندرية',
                'currency'  => 'EGP',
                'timezone'  => 'Africa/Cairo',
                'phone'     => '+201200000002',
                'email'     => 'alex@sbbs.com',
                'address'   => 'ميدان فيكتور عمانويل، سموحة، الإسكندرية، مصر',
                'is_active' => true,
            ],
            [
                'name'      => 'فرع المملكة العربية السعودية - الرياض',
                'code'      => 'SA-01',
                'country'   => 'Saudi Arabia',
                'city'      => 'الرياض',
                'currency'  => 'SAR',
                'timezone'  => 'Asia/Riyadh',
                'phone'     => '+966500000001',
                'email'     => 'riyadh@sbbs.com',
                'address'   => 'طريق الملك فهد، حي العليا، الرياض، المملكة العربية السعودية',
                'is_active' => true,
            ],
            [
                'name'      => 'فرع سلطنة عُمان - مسقط',
                'code'      => 'OM-01',
                'country'   => 'Oman',
                'city'      => 'مسقط',
                'currency'  => 'OMR',
                'timezone'  => 'Asia/Muscat',
                'phone'     => '+96890000001',
                'email'     => 'muscat@sbbs.com',
                'address'   => 'حي الشاطئ، القرم، مسقط، سلطنة عُمان',
                'is_active' => true,
            ],
        ];

        $branches = [];
        foreach ($branchesData as $b) {
            $branches[$b['code']] = Branch::updateOrCreate(['code' => $b['code']], $b);
        }

        // 2. Job Titles
        $titlesData = [
            ['code' => 'CEO-01',  'name' => 'المدير التنفيذي العام (CEO)',           'description' => 'قيادة الشركة والتوجيه الاستراتيجي لكافة الفروع'],
            ['code' => 'HR-01',   'name' => 'مدير الموارد البشرية والشؤون الإدارية', 'description' => 'إدارة التوظيف والمتابعة والرواتب والتطوير المؤسسي'],
            ['code' => 'DEV-01',  'name' => 'كبير مهندسي البرمجيات (Lead Dev)',       'description' => 'الإشراف على البنية التقنية وهندسة الأنظمة'],
            ['code' => 'DEV-02',  'name' => 'مهندس برمجيات Full-Stack أول',          'description' => 'تطوير تطبيقات الويب وواجهات برمجة التطبيقات'],
            ['code' => 'QA-01',   'name' => 'مهندس فحص وضمان جودة البرمجيات (QA)',    'description' => 'اختبار النظم واكتشاف الثغرات وضمان كفاءة الأداء'],
            ['code' => 'UIUX-01', 'name' => 'مصمم واجهات وتجربة المستخدم (UI/UX)',   'description' => 'تصميم نماذج وتجربة الاستخدام لكافة الأنظمة'],
            ['code' => 'MKT-01',  'name' => 'مدير التسويق الرقمي والنمو',             'description' => 'إدارة الحملات الإعلانية وصناعة المحتوى والنمو'],
            ['code' => 'ACC-01',  'name' => 'محاسب مالي أول ومسؤول ضرائب',          'description' => 'إدارة الدفاتر المحاسبية والتقارير المالية والعهد'],
            ['code' => 'PRO-01',  'name' => 'مسؤول علاقات حكومية وشؤون عامة (PRO)',  'description' => 'متابعة المعاملات الحكومية والتأمينات والتراخيص'],
            ['code' => 'CS-01',   'name' => 'أخصائي خدمة عملاء ودعم فني',            'description' => 'تقديم الدعم والمساندة للعملاء على مدار الساعة'],
        ];

        $jobTitles = [];
        foreach ($titlesData as $t) {
            $jobTitles[$t['code']] = JobTitle::updateOrCreate(
                ['name' => $t['name']],
                ['description' => $t['description'], 'is_active' => true]
            );
        }

        // 3. Comprehensive Employees List
        $employeesData = [
            [
                'code'               => 'EMP-001',
                'name'               => 'عبد الرحمن محمد النفيلى',
                'national_id'        => '29001010101234',
                'birth_date'         => '1990-05-15',
                'gender'             => 'male',
                'marital_status'     => 'married',
                'nationality'        => 'مصري',
                'phone'              => '+201012345671',
                'phone_country'      => 'EG',
                'alt_phone'          => '+201112345671',
                'alt_phone_country'  => 'EG',
                'personal_email'     => 'elnafily@sbbs.com',
                'emergency_contact_name'          => 'محمد النفيلى',
                'emergency_contact_phone'         => '+201212345671',
                'emergency_contact_phone_country' => 'EG',
                'address'            => 'القاهرة الجديدة، التجمع الأول، مصر',
                'branch_code'        => 'EG-01',
                'title_code'         => 'CEO-01',
                'manager_code'       => null,
                'hire_date'          => '2023-01-01',
                'probation_end_date' => '2023-04-01',
                'contract_type'      => 'full_time',
                'status'             => 'active',
                'notes'              => 'المدير التنفيذي العام لمجموعة الفروع والشركات',
                'basic_salary'       => 45000,
                'bank_name'          => 'البنك الأهلي المصري (NBE)',
                'bank_account_number'=> 'EG120003000000012345678901',
                'insurance_number'   => 'INS-9001234',
                'annual_leave_entitlement'       => 30,
                'monthly_permission_hours_limit' => 6,
                'daily_work_hours'   => 8,
                'custom_hourly_rate' => 250,
                'allowances'         => [
                    ['name' => 'بدل سكن وتنقلات تنفيذية', 'amount' => 10000],
                    ['name' => 'بدل تمثيل ومسؤولية',     'amount' => 5000],
                ],
            ],
            [
                'code'               => 'EMP-002',
                'name'               => 'نهال نبيل عبد الفتاح',
                'national_id'        => '29304120105678',
                'birth_date'         => '1993-04-12',
                'gender'             => 'female',
                'marital_status'     => 'single',
                'nationality'        => 'مصرية',
                'phone'              => '+201023456782',
                'phone_country'      => 'EG',
                'alt_phone'          => '+201123456782',
                'alt_phone_country'  => 'EG',
                'personal_email'     => 'nehal.nabil@sbbs.com',
                'emergency_contact_name'          => 'نبيل عبد الفتاح',
                'emergency_contact_phone'         => '+201223456782',
                'emergency_contact_phone_country' => 'EG',
                'address'            => 'مدينة نصر، الحي السابع، القاهرة',
                'branch_code'        => 'EG-01',
                'title_code'         => 'HR-01',
                'manager_code'       => 'EMP-001',
                'hire_date'          => '2023-06-01',
                'probation_end_date' => '2023-09-01',
                'contract_type'      => 'full_time',
                'status'             => 'active',
                'notes'              => 'مسؤولة إدارة الموارد البشرية، تقييم الأداء والرواتب',
                'basic_salary'       => 26000,
                'bank_name'          => 'بنك CIB مصر',
                'bank_account_number'=> 'EG440010000000098765432101',
                'insurance_number'   => 'INS-9305678',
                'annual_leave_entitlement'       => 21,
                'monthly_permission_hours_limit' => 4,
                'daily_work_hours'   => 8,
                'custom_hourly_rate' => 150,
                'allowances'         => [
                    ['name' => 'بدل انتقال ومواصلات', 'amount' => 3500],
                    ['name' => 'بدل هاتف واتصالات',    'amount' => 1500],
                ],
            ],
            [
                'code'               => 'EMP-003',
                'name'               => 'نور مجدي أحمد عبد الفتاح',
                'national_id'        => '29608150109988',
                'birth_date'         => '1996-08-15',
                'gender'             => 'male',
                'marital_status'     => 'married',
                'nationality'        => 'مصري',
                'phone'              => '+201034567893',
                'phone_country'      => 'EG',
                'alt_phone'          => '+201134567893',
                'alt_phone_country'  => 'EG',
                'personal_email'     => 'nour.magdy@sbbs.com',
                'emergency_contact_name'          => 'مجدي أحمد',
                'emergency_contact_phone'         => '+201234567893',
                'emergency_contact_phone_country' => 'EG',
                'address'            => 'دجلة المعادي، القاهرة',
                'branch_code'        => 'EG-01',
                'title_code'         => 'DEV-01',
                'manager_code'       => 'EMP-001',
                'hire_date'          => '2024-02-01',
                'probation_end_date' => '2024-05-01',
                'contract_type'      => 'full_time',
                'status'             => 'active',
                'notes'              => 'المشرف التقني ومعماري النظم والتطبيقات',
                'basic_salary'       => 32000,
                'bank_name'          => 'بنك مصر',
                'bank_account_number'=> 'EG880002000000055443322101',
                'insurance_number'   => 'INS-9609988',
                'annual_leave_entitlement'       => 21,
                'monthly_permission_hours_limit' => 4,
                'daily_work_hours'   => 8,
                'custom_hourly_rate' => 200,
                'allowances'         => [
                    ['name' => 'بدل طبيعة عمل تكنولوجي', 'amount' => 5000],
                    ['name' => 'بدل انتقال',             'amount' => 2500],
                ],
            ],
            [
                'code'               => 'EMP-004',
                'name'               => 'أحمد محمود الشناوي',
                'national_id'        => '29710200104433',
                'birth_date'         => '1997-10-20',
                'gender'             => 'male',
                'marital_status'     => 'single',
                'nationality'        => 'مصري',
                'phone'              => '+201045678904',
                'phone_country'      => 'EG',
                'alt_phone'          => '+201145678904',
                'alt_phone_country'  => 'EG',
                'personal_email'     => 'ahmed.shenawy@sbbs.com',
                'emergency_contact_name'          => 'محمود الشناوي',
                'emergency_contact_phone'         => '+201245678904',
                'emergency_contact_phone_country' => 'EG',
                'address'            => 'الدقي، الجيزة',
                'branch_code'        => 'EG-01',
                'title_code'         => 'DEV-02',
                'manager_code'       => 'EMP-003',
                'hire_date'          => '2024-03-01',
                'probation_end_date' => '2024-06-01',
                'contract_type'      => 'full_time',
                'status'             => 'active',
                'notes'              => 'مطور برمجيات Laravel و Vue.js متميز',
                'basic_salary'       => 20000,
                'bank_name'          => 'بنك QNB الأهلي',
                'bank_account_number'=> 'EG550003000000033221144501',
                'insurance_number'   => 'INS-9704433',
                'annual_leave_entitlement'       => 21,
                'monthly_permission_hours_limit' => 4,
                'daily_work_hours'   => 8,
                'custom_hourly_rate' => 125,
                'allowances'         => [
                    ['name' => 'بدل حاسب آلي وإنترنت', 'amount' => 2000],
                    ['name' => 'بدل انتقال',           'amount' => 1500],
                ],
            ],
            [
                'code'               => 'EMP-005',
                'name'               => 'سارة خالد إبراهيم مصطفى',
                'national_id'        => '29811050207766',
                'birth_date'         => '1998-11-05',
                'gender'             => 'female',
                'marital_status'     => 'single',
                'nationality'        => 'مصرية',
                'phone'              => '+201056789015',
                'phone_country'      => 'EG',
                'alt_phone'          => '+201156789015',
                'alt_phone_country'  => 'EG',
                'personal_email'     => 'sara.khaled@sbbs.com',
                'emergency_contact_name'          => 'خالد إبراهيم',
                'emergency_contact_phone'         => '+201256789015',
                'emergency_contact_phone_country' => 'EG',
                'address'            => 'لوران، الإسكندرية',
                'branch_code'        => 'EG-02',
                'title_code'         => 'QA-01',
                'manager_code'       => 'EMP-003',
                'hire_date'          => '2024-04-01',
                'probation_end_date' => '2024-07-01',
                'contract_type'      => 'full_time',
                'status'             => 'active',
                'notes'              => 'مسؤولة الجودة وفحص الأنظمة الآلي واليدوي',
                'basic_salary'       => 18000,
                'bank_name'          => 'بنك الإسكندرية',
                'bank_account_number'=> 'EG990008000000077665544301',
                'insurance_number'   => 'INS-9807766',
                'annual_leave_entitlement'       => 21,
                'monthly_permission_hours_limit' => 4,
                'daily_work_hours'   => 8,
                'custom_hourly_rate' => 110,
                'allowances'         => [
                    ['name' => 'حافز جودة الأداء', 'amount' => 2000],
                    ['name' => 'بدل انتقال',       'amount' => 1500],
                ],
            ],
            [
                'code'               => 'EMP-006',
                'name'               => 'باربرا عادل جورج',
                'national_id'        => '2456789012',
                'birth_date'         => '1995-11-20',
                'gender'             => 'female',
                'marital_status'     => 'single',
                'nationality'        => 'مصرية',
                'phone'              => '+966512345674',
                'phone_country'      => 'SA',
                'alt_phone'          => '+966522345674',
                'alt_phone_country'  => 'SA',
                'personal_email'     => 'barbara.adel@sbbs.com',
                'emergency_contact_name'          => 'عادل جورج',
                'emergency_contact_phone'         => '+966598765432',
                'emergency_contact_phone_country' => 'SA',
                'address'            => 'حي السليمانية، الرياض، المملكة العربية السعودية',
                'branch_code'        => 'SA-01',
                'title_code'         => 'UIUX-01',
                'manager_code'       => 'EMP-001',
                'hire_date'          => '2024-05-15',
                'probation_end_date' => '2024-08-15',
                'contract_type'      => 'full_time',
                'status'             => 'active',
                'notes'              => 'مسؤولة تصميم تجربة المستخدم Figma والهوية البصرية',
                'basic_salary'       => 11000,
                'bank_name'          => 'مصرف الراجحي',
                'bank_account_number'=> 'SA4480000432608010123456',
                'insurance_number'   => 'GOSI-88776655',
                'annual_leave_entitlement'       => 30,
                'monthly_permission_hours_limit' => 4,
                'daily_work_hours'   => 8,
                'custom_hourly_rate' => 70,
                'allowances'         => [
                    ['name' => 'بدل سكن شهري', 'amount' => 3000],
                    ['name' => 'بدل مواصلات',  'amount' => 1200],
                ],
            ],
            [
                'code'               => 'EMP-007',
                'name'               => 'فيصل سلطان ناصر الدوسري',
                'national_id'        => '1098765432',
                'birth_date'         => '1992-09-10',
                'gender'             => 'male',
                'marital_status'     => 'married',
                'nationality'        => 'سعودي',
                'phone'              => '+966533345677',
                'phone_country'      => 'SA',
                'alt_phone'          => '+966544345677',
                'alt_phone_country'  => 'SA',
                'personal_email'     => 'faisal.dossary@sbbs.com',
                'emergency_contact_name'          => 'سلطان الدوسري',
                'emergency_contact_phone'         => '+966555345677',
                'emergency_contact_phone_country' => 'SA',
                'address'            => 'حي النخيل، الرياض، المملكة العربية السعودية',
                'branch_code'        => 'SA-01',
                'title_code'         => 'ACC-01',
                'manager_code'       => 'EMP-001',
                'hire_date'          => '2024-06-01',
                'probation_end_date' => '2024-09-01',
                'contract_type'      => 'full_time',
                'status'             => 'active',
                'notes'              => 'المحاسب المالي ومسؤول الضرائب والزكاة بالسعودية',
                'basic_salary'       => 14000,
                'bank_name'          => 'البنك الأهلي السعودي (SNB)',
                'bank_account_number'=> 'SA9910000123456789012345',
                'insurance_number'   => 'GOSI-99881122',
                'annual_leave_entitlement'       => 30,
                'monthly_permission_hours_limit' => 4,
                'daily_work_hours'   => 8,
                'custom_hourly_rate' => 90,
                'allowances'         => [
                    ['name' => 'بدل سكن عائلي', 'amount' => 3500],
                    ['name' => 'بدل سيارة',     'amount' => 1500],
                ],
            ],
            [
                'code'               => 'EMP-008',
                'name'               => 'عمر عاصم عبد العزيز محمود',
                'national_id'        => '987654321',
                'birth_date'         => '1997-03-10',
                'gender'             => 'male',
                'marital_status'     => 'single',
                'nationality'        => 'عُماني',
                'phone'              => '+96891234567',
                'phone_country'      => 'OM',
                'alt_phone'          => '+96892234567',
                'alt_phone_country'  => 'OM',
                'personal_email'     => 'omer.asem@sbbs.com',
                'emergency_contact_name'          => 'عاصم عبد العزيز',
                'emergency_contact_phone'         => '+96898765432',
                'emergency_contact_phone_country' => 'OM',
                'address'            => 'شاطئ القرم، مسقط، سلطنة عُمان',
                'branch_code'        => 'OM-01',
                'title_code'         => 'MKT-01',
                'manager_code'       => 'EMP-001',
                'hire_date'          => '2025-01-10',
                'probation_end_date' => '2025-04-10',
                'contract_type'      => 'full_time',
                'status'             => 'active',
                'notes'              => 'إدارة الحملات الرقمية وإعلانات السوشيال ميديا بسلطنة عمان',
                'basic_salary'       => 950,
                'bank_name'          => 'بنك مسقط',
                'bank_account_number'=> 'OM3200100000000123456789',
                'insurance_number'   => 'PASI-11223344',
                'annual_leave_entitlement'       => 30,
                'monthly_permission_hours_limit' => 4,
                'daily_work_hours'   => 8,
                'custom_hourly_rate' => 6.5,
                'allowances'         => [
                    ['name' => 'علاوة انتقال وتغذية', 'amount' => 180],
                    ['name' => 'علاوة سكن وتأثيث',   'amount' => 250],
                ],
            ],
            [
                'code'               => 'EMP-009',
                'name'               => 'مريم سالم راشد الحوسني',
                'national_id'        => '876543210',
                'birth_date'         => '1999-07-25',
                'gender'             => 'female',
                'marital_status'     => 'single',
                'nationality'        => 'عُمانية',
                'phone'              => '+96893334568',
                'phone_country'      => 'OM',
                'alt_phone'          => '+96894434568',
                'alt_phone_country'  => 'OM',
                'personal_email'     => 'maryam.hosni@sbbs.com',
                'emergency_contact_name'          => 'سالم الحوسني',
                'emergency_contact_phone'         => '+96895554568',
                'emergency_contact_phone_country' => 'OM',
                'address'            => 'الغبرة الجنوبية، بوشر، مسقط',
                'branch_code'        => 'OM-01',
                'title_code'         => 'CS-01',
                'manager_code'       => 'EMP-008',
                'hire_date'          => '2025-02-01',
                'probation_end_date' => '2025-05-01',
                'contract_type'      => 'full_time',
                'status'             => 'active',
                'notes'              => 'مسؤولة دعم العملاء ومتابعة التذاكر التقنية',
                'basic_salary'       => 700,
                'bank_name'          => 'البنك الوطني العماني (NBO)',
                'bank_account_number'=> 'OM7700200000000987654321',
                'insurance_number'   => 'PASI-55667788',
                'annual_leave_entitlement'       => 30,
                'monthly_permission_hours_limit' => 4,
                'daily_work_hours'   => 8,
                'custom_hourly_rate' => 4.8,
                'allowances'         => [
                    ['name' => 'علاوة نقل', 'amount' => 120],
                    ['name' => 'علاوة هاتف', 'amount' => 50],
                ],
            ],
            [
                'code'               => 'EMP-010',
                'name'               => 'طارق زياد المنشاوي',
                'national_id'        => '29402150106677',
                'birth_date'         => '1994-02-15',
                'gender'             => 'male',
                'marital_status'     => 'married',
                'nationality'        => 'مصري',
                'phone'              => '+201067890126',
                'phone_country'      => 'EG',
                'alt_phone'          => '+201167890126',
                'alt_phone_country'  => 'EG',
                'personal_email'     => 'tarek.menshawy@sbbs.com',
                'emergency_contact_name'          => 'زياد المنشاوي',
                'emergency_contact_phone'         => '+201267890126',
                'emergency_contact_phone_country' => 'EG',
                'address'            => 'شبرا مصر، القاهرة',
                'branch_code'        => 'EG-01',
                'title_code'         => 'PRO-01',
                'manager_code'       => 'EMP-002',
                'hire_date'          => '2024-07-01',
                'probation_end_date' => '2024-10-01',
                'contract_type'      => 'full_time',
                'status'             => 'active',
                'notes'              => 'مسؤول الإجراءات الحكومية والجهات الرقابية والمأموريات',
                'basic_salary'       => 16000,
                'bank_name'          => 'بنك القاهرة',
                'bank_account_number'=> 'EG330004000000088776655401',
                'insurance_number'   => 'INS-9406677',
                'annual_leave_entitlement'       => 21,
                'monthly_permission_hours_limit' => 4,
                'daily_work_hours'   => 8,
                'custom_hourly_rate' => 100,
                'allowances'         => [
                    ['name' => 'بدل مأموريات وانتقالات', 'amount' => 3000],
                    ['name' => 'بدل اتصالات',          'amount' => 1000],
                ],
            ],
        ];

        $employees = [];
        foreach ($employeesData as $data) {
            $emp = Employee::where('national_id', $data['national_id'])->first()
                ?? Employee::where('employee_code', $data['code'])->first()
                ?? new Employee();

            $emp->fill([
                'name'                            => $data['name'],
                'national_id'                     => $data['national_id'],
                'birth_date'                      => $data['birth_date'],
                'gender'                          => $data['gender'],
                'marital_status'                  => $data['marital_status'],
                'nationality'                     => $data['nationality'],
                'phone'                           => $data['phone'],
                'phone_country'                   => $data['phone_country'],
                'alt_phone'                       => $data['alt_phone'],
                'alt_phone_country'               => $data['alt_phone_country'],
                'personal_email'                  => $data['personal_email'],
                'emergency_contact_name'          => $data['emergency_contact_name'],
                'emergency_contact_phone'         => $data['emergency_contact_phone'],
                'emergency_contact_phone_country' => $data['emergency_contact_phone_country'],
                'address'                         => $data['address'],
                'branch_id'                       => $branches[$data['branch_code']]->id,
                'job_title_id'                    => $jobTitles[$data['title_code']]->id,
                'hire_date'                       => $data['hire_date'],
                'probation_end_date'              => $data['probation_end_date'],
                'contract_type'                   => $data['contract_type'],
                'status'                          => $data['status'],
                'notes'                           => $data['notes'],
                'annual_leave_entitlement'        => $data['annual_leave_entitlement'],
                'monthly_permission_hours_limit'  => $data['monthly_permission_hours_limit'],
                'daily_work_hours'                => $data['daily_work_hours'],
                'custom_hourly_rate'              => $data['custom_hourly_rate'],
                'bank_name'                       => $data['bank_name'],
                'bank_account_number'             => $data['bank_account_number'],
                'insurance_number'                => $data['insurance_number'],
            ]);

            if (empty($emp->employee_code)) {
                $emp->employee_code = $data['code'];
            }
            $emp->save();

            // Salary record
            EmployeeSalary::updateOrCreate(
                ['employee_id' => $emp->id, 'effective_date' => $data['hire_date']],
                [
                    'basic_salary'        => $data['basic_salary'],
                    'allowances'          => collect($data['allowances'])->sum('amount'),
                    'currency'            => $branches[$data['branch_code']]->currency ?? 'EGP',
                    'change_reason'       => 'hiring',
                    'notes'               => 'الراتب المعتمد عند التعيين',
                ]
            );

            // Allowances
            EmployeeAllowance::where('employee_id', $emp->id)->delete();
            foreach ($data['allowances'] as $alw) {
                EmployeeAllowance::create([
                    'employee_id' => $emp->id,
                    'name'        => $alw['name'],
                    'amount'      => $alw['amount'],
                ]);
            }

            $employees[$data['code']] = $emp;
        }

        // Link Managers
        foreach ($employeesData as $data) {
            if ($data['manager_code'] && isset($employees[$data['manager_code']])) {
                $employees[$data['code']]->update(['manager_id' => $employees[$data['manager_code']]->id]);
            }
        }

        // 4. Employee Documents
        $sampleDocs = [
            ['title' => 'صورة بطاقة الرقم القومي / الإقامة الرسمية', 'document_type' => 'national_id', 'file_path' => '/assets/docs/sample_national_id.pdf', 'issue_date' => '2023-01-10', 'expiry_date' => '2030-01-10'],
            ['title' => 'عقد العمل المبرم والموثق 2026',            'document_type' => 'contract',    'file_path' => '/assets/docs/sample_contract.pdf',    'issue_date' => '2024-01-01', 'expiry_date' => '2027-01-01'],
            ['title' => 'شهادة التخرج والمؤهل الأكاديمي العالي',      'document_type' => 'certificate', 'file_path' => '/assets/docs/sample_degree.pdf',      'issue_date' => '2018-06-25', 'expiry_date' => null],
            ['title' => 'شهادة الخدمة العسكرية / الموقف من التجنيد', 'document_type' => 'other',       'file_path' => '/assets/docs/sample_military.pdf',    'issue_date' => '2019-01-15', 'expiry_date' => null],
            ['title' => 'شهادات الدورات التدريبية والخبرات المهنية', 'document_type' => 'certificate', 'file_path' => '/assets/docs/sample_certs.pdf',       'issue_date' => '2022-08-10', 'expiry_date' => null],
        ];

        foreach ($employees as $emp) {
            foreach ($sampleDocs as $doc) {
                EmployeeDocument::firstOrCreate(
                    ['employee_id' => $emp->id, 'title' => $doc['title']],
                    array_merge($doc, [
                        'employee_id' => $emp->id,
                        'is_verified' => true,
                        'verified_by' => $admin->id,
                        'verified_at' => now(),
                    ])
                );
            }
        }

        // 5. Employee Skills
        $skillsPool = [
            ['skill_name' => 'إدارة المشاريع والتخطيط الاستراتيجي', 'proficiency_level' => 'expert',       'years_of_experience' => 8, 'is_core' => true, 'evaluation_source' => 'manager'],
            ['skill_name' => 'برمجة PHP & Laravel Framework',       'proficiency_level' => 'advanced',     'years_of_experience' => 6, 'is_core' => true, 'evaluation_source' => 'manager'],
            ['skill_name' => 'تطوير واجهات المستخدم Vue.js & React', 'proficiency_level' => 'advanced',     'years_of_experience' => 5, 'is_core' => true, 'evaluation_source' => 'self'],
            ['skill_name' => 'تصميم النظم وقواعد البيانات MySQL',   'proficiency_level' => 'expert',       'years_of_experience' => 7, 'is_core' => true, 'evaluation_source' => 'manager'],
            ['skill_name' => 'تصميم واجهات المستخدم UI/UX Figma',  'proficiency_level' => 'expert',       'years_of_experience' => 5, 'is_core' => true, 'evaluation_source' => 'manager'],
            ['skill_name' => 'اختبار النظم الآلي واليدوي QA',       'proficiency_level' => 'advanced',     'years_of_experience' => 4, 'is_core' => true, 'evaluation_source' => 'manager'],
            ['skill_name' => 'إدارة الموارد البشرية وشؤون العمل',   'proficiency_level' => 'expert',       'years_of_experience' => 7, 'is_core' => true, 'evaluation_source' => 'manager'],
            ['skill_name' => 'المحاسبة المالية وإعداد التقارير',    'proficiency_level' => 'expert',       'years_of_experience' => 6, 'is_core' => true, 'evaluation_source' => 'manager'],
            ['skill_name' => 'التسويق الرقمي وإعلانات السوشيال',    'proficiency_level' => 'advanced',     'years_of_experience' => 5, 'is_core' => true, 'evaluation_source' => 'self'],
            ['skill_name' => 'خدمة العملاء وحل المشكلات الفنية',    'proficiency_level' => 'intermediate', 'years_of_experience' => 3, 'is_core' => true, 'evaluation_source' => 'manager'],
        ];

        foreach ($employees as $emp) {
            $assignedSkills = array_slice($skillsPool, rand(0, 4), rand(3, 5));
            foreach ($assignedSkills as $sk) {
                EmployeeSkill::firstOrCreate(
                    ['employee_id' => $emp->id, 'skill_name' => $sk['skill_name']],
                    array_merge($sk, ['employee_id' => $emp->id])
                );
            }
        }

        // 6. Employee Leave Balances (2025 & 2026)
        foreach ($employees as $emp) {
            foreach ([2025, 2026] as $yr) {
                EmployeeLeaveBalance::updateOrCreate(
                    ['employee_id' => $emp->id, 'year' => $yr],
                    [
                        'annual_balance' => $emp->annual_leave_entitlement,
                        'used_annual'    => $yr == 2025 ? rand(10, 20) : rand(2, 6),
                        'casual_balance' => 7,
                        'used_casual'    => $yr == 2025 ? rand(3, 6) : rand(1, 3),
                        'sick_balance'   => 15,
                        'used_sick'      => $yr == 2025 ? rand(1, 4) : rand(0, 2),
                    ]
                );
            }
        }

        // 7. Employee Leaves
        EmployeeLeave::whereIn('employee_id', collect($employees)->pluck('id'))->delete();
        $leavesData = [
            ['emp_code' => 'EMP-001', 'type' => 'annual', 'start' => '2026-07-10', 'end' => '2026-07-17', 'days' => 6, 'reason' => 'إجازة سنوية للاستجمام والسفر العائلي', 'status' => 'approved'],
            ['emp_code' => 'EMP-002', 'type' => 'annual', 'start' => '2026-06-01', 'end' => '2026-06-05', 'days' => 4, 'reason' => 'إجازة سنوية مجدولة', 'status' => 'approved'],
            ['emp_code' => 'EMP-003', 'type' => 'casual', 'start' => '2026-08-12', 'end' => '2026-08-13', 'days' => 2, 'reason' => 'إجازة عارضة لظرف عائلي طارئ', 'status' => 'approved'],
            ['emp_code' => 'EMP-004', 'type' => 'sick',   'start' => '2026-08-20', 'end' => '2026-08-22', 'days' => 2, 'reason' => 'وعكة صحية وإجهاد مع تقرير طبي معتمد', 'status' => 'approved'],
            ['emp_code' => 'EMP-005', 'type' => 'casual', 'start' => '2026-09-02', 'end' => '2026-09-03', 'days' => 1, 'reason' => 'ظرف شخصي عاجل', 'status' => 'approved'],
            ['emp_code' => 'EMP-006', 'type' => 'annual', 'start' => '2026-08-01', 'end' => '2026-08-10', 'days' => 8, 'reason' => 'إجازة سنوية وزيارة الأهل بالقاهرة', 'status' => 'approved'],
            ['emp_code' => 'EMP-007', 'type' => 'sick',   'start' => '2026-07-22', 'end' => '2026-07-24', 'days' => 2, 'reason' => 'مراجعة المستشفى وإجراء فحوصات طبية', 'status' => 'approved'],
            ['emp_code' => 'EMP-008', 'type' => 'unpaid', 'start' => '2026-08-15', 'end' => '2026-08-18', 'days' => 3, 'reason' => 'إنهاء أوراق ومعاملات دراسية خاصة', 'status' => 'approved'],
            ['emp_code' => 'EMP-009', 'type' => 'annual', 'start' => '2026-09-15', 'end' => '2026-09-22', 'days' => 6, 'reason' => 'إجازة سنوية مجدولة مع العائلة', 'status' => 'pending'],
            ['emp_code' => 'EMP-010', 'type' => 'casual', 'start' => '2026-08-05', 'end' => '2026-08-06', 'days' => 1, 'reason' => 'تجديد وثائق رسمية بالشهر العقاري', 'status' => 'approved'],
        ];

        foreach ($leavesData as $lv) {
            EmployeeLeave::create([
                'employee_id' => $employees[$lv['emp_code']]->id,
                'leave_type'  => $lv['type'],
                'start_date'  => $lv['start'],
                'end_date'    => $lv['end'],
                'days_count'  => $lv['days'],
                'reason'      => $lv['reason'],
                'status'      => $lv['status'],
                'approved_by' => $lv['status'] === 'approved' ? $admin->id : null,
                'approved_at' => $lv['status'] === 'approved' ? now() : null,
            ]);
        }

        // 8. Employee Permissions
        EmployeePermission::whereIn('employee_id', collect($employees)->pluck('id'))->delete();
        $permissionsData = [
            ['emp_code' => 'EMP-002', 'date' => '2026-08-05', 'start' => '10:00', 'end' => '12:00', 'hours' => 2.0, 'reason' => 'مراجعة مكتب التأمينات الاجتماعية لتوثيق ملفات الموظفين', 'status' => 'approved'],
            ['emp_code' => 'EMP-003', 'date' => '2026-08-10', 'start' => '14:00', 'end' => '16:00', 'hours' => 2.0, 'reason' => 'زيارة مصلحة الجوازات لإنهاء معاملات رسمية', 'status' => 'approved'],
            ['emp_code' => 'EMP-004', 'date' => '2026-08-16', 'start' => '13:00', 'end' => '15:30', 'hours' => 2.5, 'reason' => 'كشف طبي دوري ومراجعة عيادة الأسنان', 'status' => 'approved'],
            ['emp_code' => 'EMP-005', 'date' => '2026-08-25', 'start' => '11:00', 'end' => '13:00', 'hours' => 2.0, 'reason' => 'ظرف عائلي مفاجئ يتطلب الحضور', 'status' => 'approved'],
            ['emp_code' => 'EMP-007', 'date' => '2026-08-18', 'start' => '09:00', 'end' => '11:00', 'hours' => 2.0, 'reason' => 'مراجعة هيئة الزكاة والضريبة والجمارك بالرياض', 'status' => 'approved'],
            ['emp_code' => 'EMP-010', 'date' => '2026-08-28', 'start' => '12:00', 'end' => '14:00', 'hours' => 2.0, 'reason' => 'استلام مستندات وتراخيص من الغرفة التجارية', 'status' => 'approved'],
            ['emp_code' => 'EMP-004', 'date' => '2026-09-02', 'start' => '14:30', 'end' => '16:30', 'hours' => 2.0, 'reason' => 'موعد شخصي عاجل في البنك', 'status' => 'pending'],
        ];

        foreach ($permissionsData as $p) {
            EmployeePermission::create([
                'employee_id'     => $employees[$p['emp_code']]->id,
                'permission_date' => $p['date'],
                'start_time'      => $p['start'],
                'end_time'        => $p['end'],
                'hours_count'     => $p['hours'],
                'reason'          => $p['reason'],
                'status'          => $p['status'],
                'approved_by'     => $p['status'] === 'approved' ? $admin->id : null,
                'approved_at'     => $p['status'] === 'approved' ? now() : null,
            ]);
        }

        // 9. Employee Penalties
        EmployeePenalty::whereIn('employee_id', collect($employees)->pluck('id'))->delete();
        $penaltiesData = [
            ['emp_code' => 'EMP-004', 'type' => 'days_deduction',   'days' => 1.0, 'amount' => 0,   'date' => '2026-07-25', 'reason' => 'تأخير متكرر عن موعد بدء العمل الصباحي لأكثر من ساعتين دون إذن'],
            ['emp_code' => 'EMP-005', 'type' => 'days_deduction',   'days' => 0.5, 'amount' => 0,   'date' => '2026-08-10', 'reason' => 'الغياب دون إخطار مسبق لإدارة الفريق'],
            ['emp_code' => 'EMP-010', 'type' => 'amount_deduction', 'days' => 0,   'amount' => 500, 'date' => '2026-08-14', 'reason' => 'التقصير في تسليم التقرير الإداري الأسبوعي في موعده'],
            ['emp_code' => 'EMP-009', 'type' => 'warning',          'days' => 0,   'amount' => 0,   'date' => '2026-08-20', 'reason' => 'تنبيه إداري لعدم الالتزام بجدول مناوبات خدمة العملاء'],
        ];

        foreach ($penaltiesData as $pen) {
            EmployeePenalty::create([
                'employee_id'      => $employees[$pen['emp_code']]->id,
                'penalty_type'     => $pen['type'],
                'days_count'       => $pen['days'],
                'deduction_amount' => $pen['amount'],
                'penalty_date'     => $pen['date'],
                'reason'           => $pen['reason'],
                'created_by'       => $admin->id,
            ]);
        }

        // 10. Employee Advances
        EmployeeAdvance::whereIn('employee_id', collect($employees)->pluck('id'))->delete();
        $advancesData = [
            ['emp_code' => 'EMP-003', 'amount' => 12000, 'installments' => 4, 'monthly' => 3000, 'paid' => 6000, 'remaining' => 6000, 'date' => '2026-06-15', 'start_date' => '2026-07-01', 'reason' => 'سلفة مصاريف علاجية وطبية عائلية طارئة', 'status' => 'approved'],
            ['emp_code' => 'EMP-004', 'amount' => 6000,  'installments' => 3, 'monthly' => 2000, 'paid' => 4000, 'remaining' => 2000, 'date' => '2026-06-20', 'start_date' => '2026-07-01', 'reason' => 'سلفة تجديد رخصة السيارة ومصاريف صيانة', 'status' => 'approved'],
            ['emp_code' => 'EMP-006', 'amount' => 4500,  'installments' => 3, 'monthly' => 1500, 'paid' => 1500, 'remaining' => 3000, 'date' => '2026-07-15', 'start_date' => '2026-08-01', 'reason' => 'تأثيث وتجهيزات منزلية بالرياض',       'status' => 'approved'],
            ['emp_code' => 'EMP-010', 'amount' => 5000,  'installments' => 5, 'monthly' => 1000, 'paid' => 0,    'remaining' => 5000, 'date' => '2026-08-28', 'start_date' => '2026-09-01', 'reason' => 'سلفة مصاريف دراسية للأبناء',            'status' => 'approved'],
        ];

        foreach ($advancesData as $adv) {
            EmployeeAdvance::create([
                'employee_id'        => $employees[$adv['emp_code']]->id,
                'amount'             => $adv['amount'],
                'installments_count' => $adv['installments'],
                'monthly_installment'=> $adv['monthly'],
                'paid_amount'        => $adv['paid'],
                'remaining_amount'   => $adv['remaining'],
                'advance_date'       => $adv['date'],
                'start_date'         => $adv['start_date'],
                'status'             => $adv['status'],
                'reason'             => $adv['reason'],
                'approved_by'        => $admin->id,
                'approved_at'        => now(),
            ]);
        }

        // 11. Payrolls & PayrollItems (2026-07, 2026-08, 2026-09)
        Payroll::query()->delete();
        $payrollMonths = [
            ['year' => 2026, 'month' => 7, 'status' => 'paid',     'title' => 'مسير رواتب شهر يوليو 2026'],
            ['year' => 2026, 'month' => 8, 'status' => 'approved', 'title' => 'مسير رواتب شهر أغسطس 2026'],
            ['year' => 2026, 'month' => 9, 'status' => 'draft',    'title' => 'مسير رواتب شهر سبتمبر 2026'],
        ];

        // كل كشف تابع لفرع. الكشف المجمّع (branch_id = null) مابقاش
        // مسموح: الفلترة على فرع مابترجعهوش، والتقارير مابتعرفش
        // توزّع المبالغ، وكل فرع ليه عملته وخزينته.
        $employeesByBranch = collect($employees)->groupBy('branch_id');

        foreach ($payrollMonths as $pm) {
            foreach ($employeesByBranch as $branchId => $branchEmployees) {
                $branchName = $branchEmployees->first()->branch?->name ?? '';

                $payroll = Payroll::create([
                    'title'       => $pm['title'] . ($branchName ? ' - ' . $branchName : ''),
                    'year'        => $pm['year'],
                    'month'       => $pm['month'],
                    'branch_id'   => $branchId,
                    'status'      => $pm['status'],
                    'created_by'  => $admin->id,
                    'approved_by' => in_array($pm['status'], ['approved', 'paid']) ? $admin->id : null,
                    'approved_at' => in_array($pm['status'], ['approved', 'paid']) ? Carbon::create($pm['year'], $pm['month'], 28) : null,
                    'paid_by'     => $pm['status'] === 'paid' ? $admin->id : null,
                    'paid_at'     => $pm['status'] === 'paid' ? Carbon::create($pm['year'], $pm['month'], 30) : null,
                    'notes'       => 'تم اعتماد وصرف المسير بالحسابات البنكية للموظفين',
                ]);

                $totalBasic      = 0;
                $totalAllowances = 0;
                $totalDeductions = 0;
                $totalNet        = 0;

                foreach ($branchEmployees as $emp) {
                    $salary = $emp->latestSalary;
                    $basic = $salary ? (float) $salary->basic_salary : 10000;
                    $alwTotal = (float) $emp->allowances()->sum('amount');

                    $deductions = 0;
                    $dedDetails = [];

                    if ($emp->employee_code === 'EMP-003' && in_array($pm['month'], [7, 8])) {
                        $deductions += 3000;
                        $dedDetails[] = ['type' => 'advance', 'name' => 'قسط سلفة علاجية', 'amount' => 3000];
                    }

                    if ($emp->employee_code === 'EMP-004' && in_array($pm['month'], [7, 8])) {
                        $deductions += 2000;
                        $dedDetails[] = ['type' => 'advance', 'name' => 'قسط سلفة سيارة', 'amount' => 2000];
                    }

                    if ($emp->employee_code === 'EMP-004' && $pm['month'] == 7) {
                        $deductions += 666.67;
                        $dedDetails[] = ['type' => 'penalty', 'name' => 'جزاء تأخير (يوم عمل)', 'amount' => 666.67];
                    }

                    if ($emp->employee_code === 'EMP-005' && $pm['month'] == 8) {
                        $deductions += 300;
                        $dedDetails[] = ['type' => 'penalty', 'name' => 'جزاء غياب نصف يوم', 'amount' => 300];
                    }

                    $net = ($basic + $alwTotal) - $deductions;

                    PayrollItem::create([
                        'payroll_id'        => $payroll->id,
                        'employee_id'       => $emp->id,
                        'basic_salary'      => $basic,
                        'allowances'        => $alwTotal,
                        'allowance_details' => $emp->allowances()->get(['name', 'amount'])->toArray(),
                        'deductions'        => $deductions,
                        'deduction_details' => $dedDetails,
                        'net_salary'        => $net,
                        'status'            => $pm['status'] === 'paid' ? 'paid' : 'pending',
                        'payment_method'    => 'bank_transfer',
                        'paid_at'           => $pm['status'] === 'paid' ? Carbon::create($pm['year'], $pm['month'], 30) : null,
                        'paid_by'           => $pm['status'] === 'paid' ? $admin->id : null,
                        'notes'             => 'تمت المراجعة والاعتماد المحاسبي',
                    ]);

                    $totalBasic      += $basic;
                    $totalAllowances += $alwTotal;
                    $totalDeductions += $deductions;
                    $totalNet        += $net;
                }

                $payroll->update([
                    'total_basic_salary' => $totalBasic,
                    'total_allowances'   => $totalAllowances,
                    'total_deductions'   => $totalDeductions,
                    'total_net_salary'   => $totalNet,
                ]);
            }
        }

        // 12. Expense Categories
        $categoriesData = [
            ['name' => 'أدوات مكتبية وقرطاسية ومطبوعات', 'code' => 'OFF-01', 'description' => 'شراء أوراق تصوير، أقلام، أحبار طابعات ومطبوعات رسمية'],
            ['name' => 'بوفيه وضيافة ومشروبات واجتماعات', 'code' => 'HOS-02', 'description' => 'مستلزمات الضيافة، مياه، شاي، قهوة ووجبات ضيوف واجتماعات'],
            ['name' => 'صيانة ونظافة ومرافق وتشغيل',      'code' => 'MNT-03', 'description' => 'أدوات نظافة، صيانة مكيفات، أدوات كهرباء وسباكة'],
            ['name' => 'انتقالات ومواصلات وشحن لوجستي',   'code' => 'TRN-04', 'description' => 'تاكسي مأموريات، شحن مستندات وبضائع عبر البريد السريع'],
            ['name' => 'برمجيات وسيرفرات واشتراكات سحابية', 'code' => 'SFT-05', 'description' => 'اشتراكات سحابية، استضافة مواقع، أدوات ومكتبات برمجية'],
            ['name' => 'إكراميات وخدمات بريدية ولوجستية',  'code' => 'LOG-06', 'description' => 'إكراميات الشحن والتوصيل والخدمات اللوجستية السريعة'],
            ['name' => 'مصروفات حكومية وتراخيص وتأمينات',  'code' => 'GOV-07', 'description' => 'رسوم تجديد التراخيص والتوثيق والشهادات الرسمية'],
            ['name' => 'تسويق ودعاية وترويج محلي',        'code' => 'MKT-08', 'description' => 'طباعة بروشورات، رول أب وإعلانات ترويجية محلية'],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[$cat['code']] = ExpenseCategory::updateOrCreate(['code' => $cat['code']], array_merge($cat, ['is_active' => true]));
        }

        // 13. Petty Cash Balances
        PettyCashBalance::query()->delete();
        $pettyBalances = [
            // Company-wide / Cairo
            ['branch_id' => null,                   'year' => 2026, 'month' => 7, 'allocated' => 30000, 'carried' => 0,    'total' => 30000, 'notes' => 'ميزانية العهدة النقدية لشهر يوليو'],
            ['branch_id' => null,                   'year' => 2026, 'month' => 8, 'allocated' => 25000, 'carried' => 6450, 'total' => 31450, 'notes' => 'ميزانية العهدة النقدية لشهر أغسطس مع الترحيل'],
            ['branch_id' => null,                   'year' => 2026, 'month' => 9, 'allocated' => 25000, 'carried' => 4120, 'total' => 29120, 'notes' => 'ميزانية العهدة النقدية لشهر سبتمبر الحالي'],
            // Branch specific
            ['branch_id' => $branches['EG-01']->id, 'year' => 2026, 'month' => 8, 'allocated' => 15000, 'carried' => 3000, 'total' => 18000, 'notes' => 'عهدة فرع القاهرة'],
            ['branch_id' => $branches['SA-01']->id, 'year' => 2026, 'month' => 8, 'allocated' => 6000,  'carried' => 1200, 'total' => 7200,  'notes' => 'عهدة فرع الرياض'],
            ['branch_id' => $branches['OM-01']->id, 'year' => 2026, 'month' => 8, 'allocated' => 600,   'carried' => 100,  'total' => 700,   'notes' => 'عهدة فرع مسقط'],
        ];

        foreach ($pettyBalances as $pb) {
            PettyCashBalance::create([
                'branch_id'           => $pb['branch_id'],
                'year'                => $pb['year'],
                'month'               => $pb['month'],
                'allocated_amount'    => $pb['allocated'],
                'carried_over_amount' => $pb['carried'],
                'total_balance'       => $pb['total'],
                'notes'               => $pb['notes'],
                'created_by'          => $admin->id,
            ]);
        }

        // 14. Expenses
        Expense::query()->delete();
        $expensesData = [
            // July 2026
            ['title' => 'شراء ورق A4 وأقلام ومستلزمات مكتبية',       'cat' => 'OFF-01', 'br' => 'EG-01', 'emp' => 'EMP-010', 'amount' => 1450.00, 'currency' => 'EGP', 'date' => '2026-07-05', 'method' => 'cash',          'inv' => 'INV-2026-0701', 'notes' => 'شراء من مكتبة سمير وعلي بالتجمع الخامس'],
            ['title' => 'شراء خامات بوفيه وضيافة ومشروبات لشهر 7',     'cat' => 'HOS-02', 'br' => 'EG-01', 'emp' => 'EMP-010', 'amount' => 2350.00, 'currency' => 'EGP', 'date' => '2026-07-08', 'method' => 'cash',          'inv' => 'INV-2026-0702', 'notes' => 'شاي، بن، سكر، مناديل ومياه معدنية كرتونة كبيرة'],
            ['title' => 'صيانة التكييف المركزي بالدور الثاني',        'cat' => 'MNT-03', 'br' => 'EG-01', 'emp' => 'EMP-003', 'amount' => 3200.00, 'currency' => 'EGP', 'date' => '2026-07-12', 'method' => 'bank_transfer', 'inv' => 'INV-2026-0703', 'notes' => 'صيانة دورية وشحن فريون للتكييفات'],
            ['title' => 'شحن مستندات وعقود إلى فرع الرياض عبر DHL',   'cat' => 'TRN-04', 'br' => 'EG-01', 'emp' => 'EMP-010', 'amount' => 950.00,  'currency' => 'EGP', 'date' => '2026-07-16', 'method' => 'card',          'inv' => 'INV-2026-0704', 'notes' => 'بوليصة شحن رقم DHL-88992211'],
            ['title' => 'اشتراك استضافة وسيرفرات AWS السحابية لشهر 7', 'cat' => 'SFT-05', 'br' => null,    'emp' => 'EMP-003', 'amount' => 220.00,  'currency' => 'USD', 'date' => '2026-07-20', 'method' => 'card',          'inv' => 'INV-2026-0705', 'notes' => 'فاتورة الدفع الإلكتروني لسيرفرات الإنتاج بالدولار'],
            ['title' => 'إكراميات ونقل أثاث مكتبي جديد',               'cat' => 'LOG-06', 'br' => 'EG-01', 'emp' => 'EMP-010', 'amount' => 600.00,   'currency' => 'EGP', 'date' => '2026-07-25', 'method' => 'cash',          'inv' => 'INV-2026-0706', 'notes' => 'نقل وتعتيق كراسي ومكاتب إضافية'],

            // August 2026
            ['title' => 'تجديد أحبار طابعات HP ليزر ملونة وأسود',     'cat' => 'OFF-01', 'br' => 'EG-01', 'emp' => 'EMP-003', 'amount' => 2800.00, 'currency' => 'EGP', 'date' => '2026-08-03', 'method' => 'cash',          'inv' => 'INV-2026-0801', 'notes' => 'طابعة قسم الحسابات والموارد البشرية'],
            ['title' => 'مستلزمات نظافة وتطهير للفرع لشهر أغسطس',      'cat' => 'MNT-03', 'br' => 'EG-01', 'emp' => 'EMP-010', 'amount' => 1250.00, 'currency' => 'EGP', 'date' => '2026-08-06', 'method' => 'cash',          'inv' => 'INV-2026-0802', 'notes' => 'معقمات ومنظفات وأكياس نفايات وسوائل غسيل'],
            ['title' => 'ضيافة وفطور جماعي بمناسبة انتهاء مرحلة المشروع', 'cat' => 'HOS-02', 'br' => 'EG-01', 'emp' => 'EMP-002', 'amount' => 1950.00, 'currency' => 'EGP', 'date' => '2026-08-10', 'method' => 'cash',          'inv' => 'INV-2026-0803', 'notes' => 'ضيافة لفريق العمل والمطورين'],
            ['title' => 'اشتراك خدمة Figma Organization لشهر أغسطس',  'cat' => 'SFT-05', 'br' => 'SA-01', 'emp' => 'EMP-006', 'amount' => 60.00,    'currency' => 'USD', 'date' => '2026-08-12', 'method' => 'card',          'inv' => 'INV-2026-0804', 'notes' => 'اشتراك تراخيص التصميم لـ 4 مقاعد'],
            ['title' => 'رسوم توثيق وتصديق عقود بالغرفة التجارية',      'cat' => 'GOV-07', 'br' => 'EG-01', 'emp' => 'EMP-010', 'amount' => 1100.00, 'currency' => 'EGP', 'date' => '2026-08-15', 'method' => 'cash',          'inv' => 'INV-2026-0805', 'notes' => 'تصديق قرارات الجمعية وتجديد السجل التجاري'],
            ['title' => 'شراء كابلات شبكة ومحولات TP-Link سريعة',       'cat' => 'OFF-01', 'br' => 'EG-02', 'emp' => 'EMP-005', 'amount' => 850.00,  'currency' => 'EGP', 'date' => '2026-08-18', 'method' => 'cash',          'inv' => 'INV-2026-0806', 'notes' => 'توصيل شبكة فرع الإسكندرية'],
            ['title' => 'مستلزمات مكتبية وصيانة فرع الرياض',          'cat' => 'OFF-01', 'br' => 'SA-01', 'emp' => 'EMP-006', 'amount' => 750.00,  'currency' => 'SAR', 'date' => '2026-08-20', 'method' => 'card',          'inv' => 'INV-2026-0807-SA', 'notes' => 'شراء أدوات مكتبية لفرع الرياض بالريال'],
            ['title' => 'انتقالات مأموريات الشهر العقاري والضرائب',     'cat' => 'TRN-04', 'br' => 'EG-01', 'emp' => 'EMP-010', 'amount' => 750.00,  'currency' => 'EGP', 'date' => '2026-08-22', 'method' => 'cash',          'inv' => 'INV-2026-0807', 'notes' => 'فواتير أوبر وتاكسي للمأموريات المعتمدة'],
            ['title' => 'طباعة رول أب وبنرات تسويقية لمعرض التوظيف',   'cat' => 'MKT-08', 'br' => 'EG-01', 'emp' => 'EMP-002', 'amount' => 2400.00, 'currency' => 'EGP', 'date' => '2026-08-25', 'method' => 'bank_transfer', 'inv' => 'INV-2026-0808', 'notes' => 'طباعة 4 بنرات بتصميم الهوية المعتمد'],
            ['title' => 'فاتورة إنترنت فايبر عالي السرعة لشهر أغسطس',   'cat' => 'SFT-05', 'br' => 'EG-01', 'emp' => 'EMP-003', 'amount' => 2100.00, 'currency' => 'EGP', 'date' => '2026-08-27', 'method' => 'bank_transfer', 'inv' => 'INV-2026-0809', 'notes' => 'شركة We للاتصالات فايبر أعمال 200 ميجا'],

            // September 2026 (Current)
            ['title' => 'شراء خامات بوفيه وضيافة لشهر سبتمبر',          'cat' => 'HOS-02', 'br' => 'EG-01', 'emp' => 'EMP-010', 'amount' => 2200.00, 'currency' => 'EGP', 'date' => '2026-09-02', 'method' => 'cash',          'inv' => 'INV-2026-0901', 'notes' => 'مستلزمات بوفيه فرع القاهرة الكاملة'],
            ['title' => 'صيانة دورية للإنارة وتبديل كشافات LED',        'cat' => 'MNT-03', 'br' => 'EG-01', 'emp' => 'EMP-010', 'amount' => 950.00,  'currency' => 'EGP', 'date' => '2026-09-03', 'method' => 'cash',          'inv' => 'INV-2026-0902', 'notes' => 'تبديل 8 كشافات ليد بقاعات الاجتماعات والممرات'],
            ['title' => 'شحن طرود ومستندات رسمية عبر أرامكس لفرع مسقط', 'cat' => 'TRN-04', 'br' => 'EG-01', 'emp' => 'EMP-010', 'amount' => 880.00,  'currency' => 'EGP', 'date' => '2026-09-04', 'method' => 'cash',          'inv' => 'INV-2026-0903', 'notes' => 'بوليصة شحن مستندات Aramex-998811'],
            ['title' => 'شراء بطاريات وحافظات وأدوات أرشفة ومجلدات',    'cat' => 'OFF-01', 'br' => 'EG-01', 'emp' => 'EMP-002', 'amount' => 650.00,  'currency' => 'EGP', 'date' => '2026-09-05', 'method' => 'cash',          'inv' => 'INV-2026-0904', 'notes' => 'أرشفة ملفات الموظفين وسجلات الإجازات السنوية'],
        ];

        foreach ($expensesData as $exp) {
            Expense::create([
                'title'               => $exp['title'],
                'expense_category_id' => $categories[$exp['cat']]->id,
                'branch_id'           => $exp['br'] ? $branches[$exp['br']]->id : null,
                'employee_id'         => $exp['emp'] ? $employees[$exp['emp']]->id : null,
                'amount'              => $exp['amount'],
                'currency'            => $exp['currency'] ?? 'EGP',
                'expense_date'        => $exp['date'],
                'payment_method'      => $exp['method'],
                'invoice_number'      => $exp['inv'],
                'attachment'          => '/assets/docs/sample_invoice.pdf',
                'notes'               => $exp['notes'],
                'created_by'          => $admin->id,
            ]);
        }

        // 15. Clean old dummy records for re-seeding
        SaleItem::query()->forceDelete();
        Sale::withTrashed()->forceDelete();
        SalesChannel::withTrashed()->forceDelete();
        Customer::withTrashed()->forceDelete();
        VendorPayment::query()->forceDelete();
        VendorSubscription::withTrashed()->forceDelete();
        Vendor::withTrashed()->forceDelete();

        $vendorsData = [
            [
                'code'           => 'VND-001',
                'name'           => 'أمازون ويب سيرفيسز',
                'company_name'   => 'Amazon Web Services Inc.',
                'contact_person' => 'Enterprise Support EMEA',
                'phone'          => '+12062661000',
                'email'          => 'billing@aws.amazon.com',
                'tax_number'     => 'US-911928421',
                'notes'          => 'مزود البنية التحتية السحابية وسيرفرات التطبيقات وقواعد البيانات',
                'is_active'      => true,
            ],
            [
                'code'           => 'VND-002',
                'name'           => 'جوجل كلاود للحلول الرقمية',
                'company_name'   => 'Google Cloud EMEA Limited',
                'contact_person' => 'Google Workspace Billing',
                'phone'          => '+35314361000',
                'email'          => 'workspace-billing@google.com',
                'tax_number'     => 'IE-6388047V',
                'notes'          => 'اشتراكات البريد الإلكتروني السحابي وحزمة Google Workspace',
                'is_active'      => true,
            ],
            [
                'code'           => 'VND-003',
                'name'           => 'شركة التوريدات المكتبية الحديثة',
                'company_name'   => 'مؤسسة سمير وعلي للتجارة والتوريدات',
                'contact_person' => 'أ. حسام فؤاد',
                'phone'          => '+201012345678',
                'email'          => 'b2b@samirandali.com',
                'tax_number'     => '102-458-991',
                'notes'          => 'توريد مستلزمات الطباعة والأوراق والأدوات المكتبية لكافة الفروع',
                'is_active'      => true,
            ],
            [
                'code'           => 'VND-004',
                'name'           => 'شركة الأفق لتقنية المعلومات والشبكات',
                'company_name'   => 'Horizon IT Solutions Riyadh',
                'contact_person' => 'م. فهد السديري',
                'phone'          => '+966551234567',
                'email'          => 'info@horizon-it.sa',
                'tax_number'     => '310928374600003',
                'notes'          => 'صيانة ودعم فني لشبكات وسيرفرات فرع المملكة العربية السعودية',
                'is_active'      => true,
            ],
            [
                'code'           => 'VND-005',
                'name'           => 'شركة ميتا للحلول الإعلانية',
                'company_name'   => 'Meta Platforms Ireland Ltd',
                'contact_person' => 'Meta Business Support',
                'phone'          => '+16505434800',
                'email'          => 'support@business.meta.com',
                'tax_number'     => 'IE-9692928F',
                'notes'          => 'منصة الإعلانات الممولة والحملات التسويقية الرقمية',
                'is_active'      => true,
            ],
        ];

        $vendors = [];
        foreach ($vendorsData as $v) {
            $vendors[$v['code']] = Vendor::create($v);
        }

        // 16. Vendor Subscriptions & Payments
        $subscriptionsData = [
            [
                'key'                => 'SUB-AWS',
                'title'              => 'بنية تحتية سحابية وسيرفرات AWS EC2 & RDS',
                'vendor_id'          => $vendors['VND-001']->id,
                'branch_id'          => null,
                'type'               => 'subscription',
                'billing_cycle'      => 'monthly',
                'cost'               => 350.00,
                'currency'           => 'USD',
                'start_date'         => '2026-01-01',
                'next_billing_date'  => '2026-10-01',
                'auto_renew'         => true,
                'status'             => 'active',
                'notes'              => 'استضافة بيئة الإنتاج وقواعد بيانات النظام بالدولار',
                'payments'           => [
                    ['amount' => 350.00, 'date' => '2026-07-01', 'method' => 'card', 'invoice' => 'AWS-INV-701', 'ref' => 'TX-AWS-7701'],
                    ['amount' => 350.00, 'date' => '2026-08-01', 'method' => 'card', 'invoice' => 'AWS-INV-801', 'ref' => 'TX-AWS-8801'],
                    ['amount' => 350.00, 'date' => '2026-09-01', 'method' => 'card', 'invoice' => 'AWS-INV-901', 'ref' => 'TX-AWS-9901'],
                ],
            ],
            [
                'key'                => 'SUB-GSUITE',
                'title'              => 'اشتراك Google Workspace Enterprise (20 مستخدم)',
                'vendor_id'          => $vendors['VND-002']->id,
                'branch_id'          => null,
                'type'               => 'subscription',
                'billing_cycle'      => 'monthly',
                'cost'               => 120.00,
                'currency'           => 'USD',
                'start_date'         => '2026-01-15',
                'next_billing_date'  => '2026-10-15',
                'auto_renew'         => true,
                'status'             => 'active',
                'notes'              => 'حسابات البريد الإلكتروني الرسمي لجميع الموظفين وسعة تخزين سحابية',
                'payments'           => [
                    ['amount' => 120.00, 'date' => '2026-07-15', 'method' => 'card', 'invoice' => 'GGL-INV-715', 'ref' => 'TX-GGL-7715'],
                    ['amount' => 120.00, 'date' => '2026-08-15', 'method' => 'card', 'invoice' => 'GGL-INV-815', 'ref' => 'TX-GGL-8815'],
                ],
            ],
            [
                'key'                => 'SUB-RIYADH-IT',
                'title'              => 'عقد صيانة شبكات ودعم فني شهري لفرع الرياض',
                'vendor_id'          => $vendors['VND-004']->id,
                'branch_id'          => $branches['SA-01']->id,
                'type'               => 'contract',
                'billing_cycle'      => 'monthly',
                'cost'               => 1200.00,
                'currency'           => 'SAR',
                'start_date'         => '2026-03-01',
                'next_billing_date'  => '2026-10-01',
                'auto_renew'         => true,
                'status'             => 'active',
                'notes'              => 'دعم تقني وصيانة دورية لأجهزة وأنظمة فرع الرياض بالريال السعودي',
                'payments'           => [
                    ['amount' => 1200.00, 'date' => '2026-07-01', 'method' => 'bank_transfer', 'invoice' => 'HRZ-INV-701', 'ref' => 'SAR-TX-701'],
                    ['amount' => 1200.00, 'date' => '2026-08-01', 'method' => 'bank_transfer', 'invoice' => 'HRZ-INV-801', 'ref' => 'SAR-TX-801'],
                    ['amount' => 1200.00, 'date' => '2026-09-01', 'method' => 'bank_transfer', 'invoice' => 'HRZ-INV-901', 'ref' => 'SAR-TX-901'],
                ],
            ],
            [
                'key'                => 'SUB-OFFICE-SUPPLY',
                'title'              => 'عقد توريد شهري للمستلزمات المكتبية ومطبوعات الهوية',
                'vendor_id'          => $vendors['VND-003']->id,
                'branch_id'          => $branches['EG-01']->id,
                'type'               => 'contract',
                'billing_cycle'      => 'monthly',
                'cost'               => 4500.00,
                'currency'           => 'EGP',
                'start_date'         => '2026-02-01',
                'next_billing_date'  => '2026-10-01',
                'auto_renew'         => true,
                'status'             => 'active',
                'notes'              => 'توريد دوري للأوراق والأحبار ومستلزمات الفرع الرئيسي بالجنيه المصري',
                'payments'           => [
                    ['amount' => 4500.00, 'date' => '2026-07-01', 'method' => 'bank_transfer', 'invoice' => 'SA-INV-701', 'ref' => 'EGP-TX-701'],
                    ['amount' => 4500.00, 'date' => '2026-08-01', 'method' => 'bank_transfer', 'invoice' => 'SA-INV-801', 'ref' => 'EGP-TX-801'],
                    ['amount' => 4500.00, 'date' => '2026-09-01', 'method' => 'bank_transfer', 'invoice' => 'SA-INV-901', 'ref' => 'EGP-TX-901'],
                ],
            ],
            [
                'key'                => 'SUB-META-ADS',
                'title'              => 'ميزانية إدارة الحملات الإعلانية والترويج على منصات Meta',
                'vendor_id'          => $vendors['VND-005']->id,
                'branch_id'          => null,
                'type'               => 'subscription',
                'billing_cycle'      => 'monthly',
                'cost'               => 500.00,
                'currency'           => 'USD',
                'start_date'         => '2026-05-01',
                'next_billing_date'  => '2026-10-01',
                'auto_renew'         => true,
                'status'             => 'active',
                'notes'              => 'حملات إعلانات فيسبوك وانستغرام لاكتساب عملاء جدد بالدولار',
                'payments'           => [
                    ['amount' => 500.00, 'date' => '2026-07-01', 'method' => 'card', 'invoice' => 'META-INV-701', 'ref' => 'TX-META-7701'],
                    ['amount' => 500.00, 'date' => '2026-08-01', 'method' => 'card', 'invoice' => 'META-INV-801', 'ref' => 'TX-META-8801'],
                ],
            ],
        ];

        foreach ($subscriptionsData as $subItem) {
            $payments = $subItem['payments'];
            unset($subItem['payments'], $subItem['key']);

            $subItem['created_by'] = $admin->id;
            $subscription = VendorSubscription::create($subItem);

            foreach ($payments as $p) {
                VendorPayment::create([
                    'vendor_subscription_id' => $subscription->id,
                    'amount'                 => $p['amount'],
                    'payment_date'           => $p['date'],
                    'payment_method'         => $p['method'],
                    'invoice_number'         => $p['invoice'],
                    'transaction_reference'  => $p['ref'],
                    'notes'                  => 'دفعة اشتراك دورية مسددة بنجاح',
                    'created_by'             => $admin->id,
                ]);
            }
        }

        // 17. Customers
        $customersData = [
            [
                'code'           => 'CUST-001',
                'name'           => 'مجموعة الخليج للحلول الرقمية',
                'company_name'   => 'Gulf Digital Solutions Co.',
                'phone'          => '+966541234567',
                'email'          => 'procurement@gulfdigital.sa',
                'tax_number'     => '300192837400003',
                'address'        => 'طريق الملك عبد العزيز، حي الورود، الرياض، السعودية',
                'notes'          => 'عميل مؤسسي متميز في قطاع التحول الرقمي وحلول الأعمال',
                'is_active'      => true,
            ],
            [
                'code'           => 'CUST-002',
                'name'           => 'شركة الأهرام للتطوير والخدمات العقارية',
                'company_name'   => 'Al-Ahram Real Estate Developments',
                'phone'          => '+201122334455',
                'email'          => 'contracts@ahram-dev.eg',
                'tax_number'     => '220-441-890',
                'address'        => 'شارع التسعين الجنوبي، التجمع الخامس، القاهرة',
                'notes'          => 'عميل استراتيجي - مشاريع أنظمة إدارة موارد الشركات والتسويق',
                'is_active'      => true,
            ],
            [
                'code'           => 'CUST-003',
                'name'           => 'مؤسسة الرواد للتعليم والتدريب الحديث',
                'company_name'   => 'Al-Rowad Educational Academy',
                'phone'          => '+201288997766',
                'email'          => 'admin@alrowad-academy.edu.eg',
                'tax_number'     => '180-332-654',
                'address'        => 'شارع رشدي، الإسكندرية، مصر',
                'notes'          => 'منصات تدريبية وإدارة فصول تعليمية وتطبيقات جوال',
                'is_active'      => true,
            ],
            [
                'code'           => 'CUST-004',
                'name'           => 'شركة النخبة العالمية للخدمات اللوجستية',
                'company_name'   => 'Elite Global Logistics LLC',
                'phone'          => '+96898765432',
                'email'          => 'contact@elitelogistics.om',
                'tax_number'     => 'OM-9928174',
                'address'        => 'منطقة روي التجارية، مسقط، سلطنة عُمان',
                'notes'          => 'أنظمة تتبع شحنات ولوحات تحكم عمليات لوجستية',
                'is_active'      => true,
            ],
            [
                'code'           => 'CUST-005',
                'name'           => 'م. أحمد الشريف',
                'company_name'   => 'مكتب الشريف للاستشارات الهندسية',
                'phone'          => '+201099887766',
                'email'          => 'asharif@alsharif-eng.com',
                'tax_number'     => '115-998-321',
                'address'        => 'حي المعادي، القاهرة، مصر',
                'notes'          => 'عميل فردي - استشارات وتطوير موقع إلكتروني مخصص',
                'is_active'      => true,
            ],
        ];

        $customers = [];
        foreach ($customersData as $c) {
            $customers[$c['code']] = Customer::create($c);
        }

        // 18. Sales Channels
        $channelsData = [
            ['code' => 'CH-WEB',    'name' => 'الموقع الإلكتروني والمنصة الرقمية', 'type' => 'online', 'notes' => 'قناة المبيعات المباشرة عبر البوابة الإلكترونية', 'is_active' => true],
            ['code' => 'CH-CAIRO',  'name' => 'فرع القاهرة - التجمع الخامس',      'type' => 'direct', 'notes' => 'المبيعات المباشرة والعقود المبرمة بفرع القاهرة', 'is_active' => true],
            ['code' => 'CH-RIYADH', 'name' => 'فرع المملكة - الرياض',            'type' => 'direct', 'notes' => 'المبيعات المباشرة بالمملكة العربية السعودية', 'is_active' => true],
            ['code' => 'CH-B2B',    'name' => 'شبكة الوسطاء وشركاء الأعمال',       'type' => 'partner','notes' => 'المبيعات المحققة عبر الشركاء والعمولات',        'is_active' => true],
        ];

        $channels = [];
        foreach ($channelsData as $ch) {
            $channels[$ch['code']] = SalesChannel::create($ch);
        }

        // 19. Sales & Sale Items (Multi-Currency: EGP, USD, SAR)
        $salesData = [
            // EGP Sales
            [
                'inv'      => 'INV-2026-001',
                'customer' => 'CUST-002',
                'channel'  => 'CH-CAIRO',
                'branch'   => 'EG-01',
                'type'     => 'external_project',
                'date'     => '2026-07-10',
                'currency' => 'EGP',
                'subtotal' => 60000.00,
                'tax'      => 8400.00,
                'discount' => 3400.00,
                'total'    => 65000.00,
                'paid'     => 65000.00,
                'due'      => 0.00,
                'status'   => 'paid',
                'method'   => 'bank_transfer',
                'notes'    => 'مشروع تطوير نظام إدارة الموارد البشرية والرواتب المخصص للشركة',
                'items'    => [
                    ['item_name' => 'تطوير وتخصيص نظام إدارة الموارد البشرية HRMS', 'qty' => 1, 'unit_price' => 45000.00, 'total' => 45000.00],
                    ['item_name' => 'تدريب الكوادر الإدارية وتهيئة الخوادم',        'qty' => 1, 'unit_price' => 15000.00, 'total' => 15000.00],
                ],
            ],
            [
                'inv'      => 'INV-2026-002',
                'customer' => 'CUST-005',
                'channel'  => 'CH-WEB',
                'branch'   => 'EG-01',
                'type'     => 'external_project',
                'date'     => '2026-07-22',
                'currency' => 'EGP',
                'subtotal' => 20000.00,
                'tax'      => 2800.00,
                'discount' => 800.00,
                'total'    => 22000.00,
                'paid'     => 22000.00,
                'due'      => 0.00,
                'status'   => 'paid',
                'method'   => 'card',
                'notes'    => 'تصميم وبرمجة موقع تعريفي وهوية رقمية مع استضافة عام كامل',
                'items'    => [
                    ['item_name' => 'تصميم وبرمجة موقع إلكتروني متجاوب', 'qty' => 1, 'unit_price' => 16000.00, 'total' => 16000.00],
                    ['item_name' => 'حزمة استضافة وحماية سنوية',         'qty' => 1, 'unit_price' => 4000.00,  'total' => 4000.00],
                ],
            ],
            [
                'inv'      => 'INV-2026-003',
                'customer' => 'CUST-003',
                'channel'  => 'CH-CAIRO',
                'branch'   => 'EG-02',
                'type'     => 'external_project',
                'date'     => '2026-08-14',
                'currency' => 'EGP',
                'subtotal' => 38000.00,
                'tax'      => 5320.00,
                'discount' => 1320.00,
                'total'    => 42000.00,
                'paid'     => 30000.00,
                'due'      => 12000.00,
                'status'   => 'partially_paid',
                'method'   => 'bank_transfer',
                'notes'    => 'تطوير منصة تعليمية وتطبيق للطلاب - دفعة مقدمة مستلمة بنجاح',
                'items'    => [
                    ['item_name' => 'برمجة لوحة تحكم إدارة الفصول والطلاب', 'qty' => 1, 'unit_price' => 25000.00, 'total' => 25000.00],
                    ['item_name' => 'تكامل بوابات الدفع الإلكتروني',       'qty' => 1, 'unit_price' => 13000.00, 'total' => 13000.00],
                ],
            ],
            [
                'inv'      => 'INV-2026-004',
                'customer' => 'CUST-002',
                'channel'  => 'CH-WEB',
                'branch'   => 'EG-01',
                'type'     => 'app_service',
                'date'     => '2026-09-02',
                'currency' => 'EGP',
                'subtotal' => 15000.00,
                'tax'      => 2100.00,
                'discount' => 1100.00,
                'total'    => 16000.00,
                'paid'     => 16000.00,
                'due'      => 0.00,
                'status'   => 'paid',
                'method'   => 'bank_transfer',
                'notes'    => 'عقد دعم فني وتحديثات برمجية للربع الثالث من عام 2026',
                'items'    => [
                    ['item_name' => 'اشتراك باقة الدعم الفني المتقدمة', 'qty' => 1, 'unit_price' => 15000.00, 'total' => 15000.00],
                ],
            ],

            // SAR Sales
            [
                'inv'      => 'INV-2026-SAR-01',
                'customer' => 'CUST-001',
                'channel'  => 'CH-RIYADH',
                'branch'   => 'SA-01',
                'type'     => 'external_project',
                'date'     => '2026-07-18',
                'currency' => 'SAR',
                'subtotal' => 25000.00,
                'tax'      => 3750.00,
                'discount' => 1750.00,
                'total'    => 27000.00,
                'paid'     => 27000.00,
                'due'      => 0.00,
                'status'   => 'paid',
                'method'   => 'bank_transfer',
                'notes'    => 'عقد استشارات تحول رقمي وربط برمجيات مع هيئة الزكاة والضريبة (الفاتورة الإلكترونية)',
                'items'    => [
                    ['item_name' => 'استشارات الامتثال لمتطلبات الفوترة الإلكترونية المرحلة الثانية', 'qty' => 1, 'unit_price' => 15000.00, 'total' => 15000.00],
                    ['item_name' => 'ربط واجهات الـ API وحزمة التشفير والتوقيع الرقمي',             'qty' => 1, 'unit_price' => 10000.00, 'total' => 10000.00],
                ],
            ],
            [
                'inv'      => 'INV-2026-SAR-02',
                'customer' => 'CUST-001',
                'channel'  => 'CH-RIYADH',
                'branch'   => 'SA-01',
                'type'     => 'app_service',
                'date'     => '2026-08-20',
                'currency' => 'SAR',
                'subtotal' => 12000.00,
                'tax'      => 1800.00,
                'discount' => 800.00,
                'total'    => 13000.00,
                'paid'     => 13000.00,
                'due'      => 0.00,
                'status'   => 'paid',
                'method'   => 'bank_transfer',
                'notes'    => 'اشتراك سنوي في خدمة المراقبة السحابية والاستجابة السريعة للأعطال',
                'items'    => [
                    ['item_name' => 'باقة المراقبة والتحسين المستمر السنوية', 'qty' => 1, 'unit_price' => 12000.00, 'total' => 12000.00],
                ],
            ],
            [
                'inv'      => 'INV-2026-SAR-03',
                'customer' => 'CUST-001',
                'channel'  => 'CH-RIYADH',
                'branch'   => 'SA-01',
                'type'     => 'external_project',
                'date'     => '2026-09-03',
                'currency' => 'SAR',
                'subtotal' => 9000.00,
                'tax'      => 1350.00,
                'discount' => 350.00,
                'total'    => 10000.00,
                'paid'     => 5000.00,
                'due'      => 5000.00,
                'status'   => 'partially_paid',
                'method'   => 'bank_transfer',
                'notes'    => 'خدمات التدريب التقني للكوادر البرمجية بالرياض - قسط أول مسدد',
                'items'    => [
                    ['item_name' => 'ورشة عمل هندسة الحلول السحابية المتقدمة', 'qty' => 1, 'unit_price' => 9000.00, 'total' => 9000.00],
                ],
            ],

            // USD Sales
            [
                'inv'      => 'INV-2026-USD-01',
                'customer' => 'CUST-004',
                'channel'  => 'CH-B2B',
                'branch'   => 'OM-01',
                'type'     => 'external_project',
                'date'     => '2026-07-28',
                'currency' => 'USD',
                'subtotal' => 3500.00,
                'tax'      => 0.00,
                'discount' => 300.00,
                'total'    => 3200.00,
                'paid'     => 3200.00,
                'due'      => 0.00,
                'status'   => 'paid',
                'method'   => 'bank_transfer',
                'notes'    => 'بناء وتكامل واجهات لوجستية دولية وتتبع الأساطيل بالدولار الأمريكي',
                'items'    => [
                    ['item_name' => 'API Integration for Fleet Tracking & Dispatch', 'qty' => 1, 'unit_price' => 2500.00, 'total' => 2500.00],
                    ['item_name' => 'Custom Telematics Reporting Dashboard',         'qty' => 1, 'unit_price' => 1000.00, 'total' => 1000.00],
                ],
            ],
            [
                'inv'      => 'INV-2026-USD-02',
                'customer' => 'CUST-004',
                'channel'  => 'CH-WEB',
                'branch'   => 'OM-01',
                'type'     => 'direct_sale',
                'date'     => '2026-08-25',
                'currency' => 'USD',
                'subtotal' => 1800.00,
                'tax'      => 0.00,
                'discount' => 100.00,
                'total'    => 1700.00,
                'paid'     => 1700.00,
                'due'      => 0.00,
                'status'   => 'paid',
                'method'   => 'card',
                'notes'    => 'رخصة استخدام برمجيات إدارة الشحنات الدولية وتحديثاتها',
                'items'    => [
                    ['item_name' => 'Enterprise License - Logistics Core Suite', 'qty' => 1, 'unit_price' => 1800.00, 'total' => 1800.00],
                ],
            ],
        ];

        foreach ($salesData as $s) {
            $items = $s['items'];
            unset($s['items']);

            $sale = Sale::create([
                'invoice_number'   => $s['inv'],
                'customer_id'      => $customers[$s['customer']]->id,
                'sales_channel_id' => $channels[$s['channel']]->id,
                'sales_channel'    => $channels[$s['channel']]->name,
                'branch_id'        => $branches[$s['branch']]->id ?? null,
                'sale_type'        => $s['type'],
                'sale_date'        => $s['date'],
                'subtotal'         => $s['subtotal'],
                'tax_amount'       => $s['tax'],
                'discount_amount'  => $s['discount'],
                'total_amount'     => $s['total'],
                'currency'         => $s['currency'],
                'paid_amount'      => $s['paid'],
                'due_amount'       => $s['due'],
                'payment_status'   => $s['status'],
                'payment_method'   => $s['method'],
                'attachment'       => '/assets/docs/sample_invoice.pdf',
                'notes'            => $s['notes'],
                'created_by'       => $admin->id,
            ]);

            foreach ($items as $item) {
                SaleItem::create([
                    'sale_id'     => $sale->id,
                    'item_name'   => $item['item_name'],
                    'quantity'    => $item['qty'],
                    'unit_price'  => $item['unit_price'],
                    'total_price' => $item['total'],
                ]);
            }
        }
    }
}
