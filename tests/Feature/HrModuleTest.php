<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeePenalty;
use App\Models\EmployeePermission;
use App\Models\EmployeeSalary;
use App\Models\JobTitle;
use App\Models\Payroll;
use App\Models\Role;
use App\Services\HR\EmployeeSalaryService;
use App\Services\HR\PayrollGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * مراجعة قسم الموارد البشرية.
 *
 * الاختبارات دي بتحرس أخطاء اتكشفت في مراجعة شاملة للقسم:
 *  - 8 موديلات كانت من غير عزل فروع (تسريب رواتب وجزاءات)
 *  - المسير كان بيعيد حساب الجزاء من سعر اليوم الحالي بدل المخزّن
 *  - ساعات الاستئذان كانت بتتاخد من إدخال المستخدم من غير تحقق
 *  - الأذونات كان ممكن تتعمد أكتر من مرة
 *  - تاريخ الرواتب مكانش بيتزامن مع basic_salary اللي المسير بيستخدمه
 */
class HrModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $mainAdmin;
    protected Admin $branchAdmin;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Employee $employeeA;
    protected Employee $employeeB;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);
        $jobTitle = JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        $this->branchA = Branch::create([
            'name' => 'Branch A', 'code' => 'HA', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);
        $this->branchB = Branch::create([
            'name' => 'Branch B', 'code' => 'HB', 'country' => 'Oman',
            'currency' => 'OMR', 'is_active' => true,
        ]);

        $this->mainAdmin = Admin::create([
            'name' => 'Root', 'email' => 'root@hr.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->employeeA = Employee::create([
            'branch_id' => $this->branchA->id, 'job_title_id' => $jobTitle->id,
            'name' => 'Employee A', 'employee_code' => 'HR-A', 'phone' => '0100',
            'status' => 'active', 'basic_salary' => 3000, 'hire_date' => '2024-01-01',
        ]);

        $this->employeeB = Employee::create([
            'branch_id' => $this->branchB->id, 'job_title_id' => $jobTitle->id,
            'name' => 'Employee B', 'employee_code' => 'HR-B', 'phone' => '0101',
            'status' => 'active', 'basic_salary' => 6000, 'hire_date' => '2024-01-01',
        ]);

        $this->branchAdmin = Admin::create([
            'name' => 'Branch A Admin', 'email' => 'a@hr.test',
            'password' => bcrypt('x'), 'main' => false,
            'role_id' => $role->id, 'employee_id' => $this->employeeA->id,
        ]);
    }

    // ========================================================
    //  عزل الفروع
    // ========================================================

    public function test_hr_records_are_isolated_by_branch(): void
    {
        EmployeePenalty::create([
            'employee_id' => $this->employeeB->id, 'penalty_type' => 'amount_deduction',
            'days_count' => 0, 'deduction_amount' => 100,
            'penalty_date' => '2026-09-01', 'reason' => 'B',
        ]);

        EmployeeSalary::create([
            'employee_id' => $this->employeeB->id, 'basic_salary' => 6000,
            'allowances' => 0, 'currency' => 'OMR',
            'effective_date' => '2024-01-01', 'change_reason' => 'hiring',
        ]);

        EmployeePermission::create([
            'employee_id' => $this->employeeB->id, 'permission_date' => '2026-09-02',
            'start_time' => '09:00', 'end_time' => '10:00',
            'hours_count' => 1, 'status' => 'pending',
        ]);

        $this->actingAs($this->branchAdmin, 'admin');

        $this->assertEquals(0, EmployeePenalty::count(), 'جزاءات فرع تاني ظاهرة');
        $this->assertEquals(0, EmployeeSalary::count(), 'رواتب فرع تاني ظاهرة');
        $this->assertEquals(0, EmployeePermission::count(), 'أذونات فرع تاني ظاهرة');
    }

    public function test_payroll_is_isolated_by_branch(): void
    {
        Payroll::create([
            'title' => 'B payroll', 'year' => 2026, 'month' => 9,
            'branch_id' => $this->branchB->id, 'status' => 'draft',
        ]);

        $this->actingAs($this->branchAdmin, 'admin');

        $this->assertEquals(0, Payroll::count(), 'مسير فرع تاني ظاهر');
    }

    public function test_main_admin_still_sees_everything(): void
    {
        EmployeePenalty::create([
            'employee_id' => $this->employeeB->id, 'penalty_type' => 'amount_deduction',
            'days_count' => 0, 'deduction_amount' => 100,
            'penalty_date' => '2026-09-01', 'reason' => 'B',
        ]);

        $this->actingAs($this->mainAdmin, 'admin');

        // بنتأكد إن جزاء الفرع B ظاهر للمدير العام تحديداً،
        // مش بنعد الكل عشان الاختبار مايعتمدش على بيانات تانية.
        $this->assertEquals(
            1,
            EmployeePenalty::where('employee_id', $this->employeeB->id)->count(),
            'المدير العام مبيشوفش جزاءات الفرع B'
        );
    }

    // ========================================================
    //  الجزاءات
    // ========================================================

    /** المسير بيخصم المبلغ المخزّن، مش يعيد حسابه من راتب اتغيّر. */
    public function test_payroll_uses_the_stored_penalty_amount(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        // سعر اليوم = 3000/30 = 100 · جزاء يومين = 200
        // معتمد عشان يدخل المسير (الجزاء بقى له دورة اعتماد)
        EmployeePenalty::create([
            'employee_id' => $this->employeeA->id, 'penalty_type' => 'days_deduction',
            'days_count' => 2, 'deduction_amount' => 200,
            'penalty_date' => '2026-09-10', 'reason' => 'اختبار',
            'status' => 'approved',
        ]);

        // الراتب اتضاعف بعد الجزاء
        $this->employeeA->update(['basic_salary' => 6000]);

        $payroll = app(PayrollGenerator::class)->generate(2026, 9, $this->branchA->id);
        $item = $payroll->items()->where('employee_id', $this->employeeA->id)->first();

        $this->assertEquals(
            200.00,
            (float) $item->deductions,
            'المسير أعاد حساب الجزاء من الراتب الجديد بدل المبلغ المعتمد'
        );
    }

    // ========================================================
    //  الأذونات
    // ========================================================

    /** الساعات بتتحسب من الوقت مش من إدخال المستخدم. */
    public function test_permission_hours_are_derived_from_the_time_range(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        $this->post(route('admin.permissions.store'), [
            'employee_id'     => $this->employeeA->id,
            'permission_date' => '2026-09-11',
            'start_time'      => '09:00',
            'end_time'        => '11:00',   // ساعتين
            'hours_count'     => 8,          // إدخال مبالغ فيه
        ]);

        $permission = EmployeePermission::where('employee_id', $this->employeeA->id)->first();

        $this->assertNotNull($permission);
        $this->assertEquals(2.0, (float) $permission->hours_count, 'الساعات اتاخدت من الإدخال مش من الوقت');
    }

    public function test_permission_end_time_must_be_after_start(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        $this->post(route('admin.permissions.store'), [
            'employee_id'     => $this->employeeA->id,
            'permission_date' => '2026-09-11',
            'start_time'      => '11:00',
            'end_time'        => '09:00',
            'hours_count'     => 2,
        ])->assertSessionHasErrors('end_time');
    }

    /** الطلب اللي اتبت فيه مايتعمدش تاني. */
    public function test_decided_permission_cannot_be_approved_again(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        $permission = EmployeePermission::create([
            'employee_id' => $this->employeeA->id, 'permission_date' => '2026-09-12',
            'start_time' => '09:00', 'end_time' => '11:00',
            'hours_count' => 2, 'status' => 'rejected',
        ]);

        $this->post(route('admin.permissions.approve', $permission->id));

        $this->assertEquals('rejected', $permission->fresh()->status, 'طلب مرفوض اتعمد');
    }

    // ========================================================
    //  الرواتب
    // ========================================================

    /** تسجيل راتب جديد بيحدّث basic_salary اللي المسير بيقراه. */
    public function test_adding_a_salary_record_syncs_the_employee_basic_salary(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        app(EmployeeSalaryService::class)->create([
            'employee_id'    => $this->employeeA->id,
            'basic_salary'   => 4500,
            'allowances'     => 0,
            'currency'       => 'EGP',
            'effective_date' => now()->subDay()->toDateString(),
            'change_reason'  => 'annual_raise',
        ]);

        $this->assertEquals(
            4500.00,
            (float) $this->employeeA->fresh()->basic_salary,
            'الراتب الأساسي مااتزامنش — المسير هيصرف بالقديم'
        );
    }

    /** العلاوة المجدولة للمستقبل ماتتطبقش قبل موعدها. */
    public function test_future_dated_salary_is_not_applied_yet(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        app(EmployeeSalaryService::class)->create([
            'employee_id'    => $this->employeeA->id,
            'basic_salary'   => 9000,
            'allowances'     => 0,
            'currency'       => 'EGP',
            'effective_date' => now()->addMonth()->toDateString(),
            'change_reason'  => 'promotion',
        ]);

        $this->assertEquals(
            3000.00,
            (float) $this->employeeA->fresh()->basic_salary,
            'علاوة مستقبلية اتطبقت بدري'
        );
    }

    // ========================================================
    //  الثوابت
    // ========================================================

    /** الحد الافتراضي لساعات الاستئذان موحّد في كل الكود. */
    public function test_permission_hours_limit_default_is_consistent(): void
    {
        $employee = Employee::withoutGlobalScope('branch')->find($this->employeeA->id);

        // الافتراضي في الـ migration
        $this->assertEquals(4.00, (float) $employee->monthly_permission_hours_limit);

        $controller = file_get_contents(
            app_path('Http/Controllers/Admin/HR/EmployeePermissionController.php')
        );

        $this->assertStringNotContainsString(
            'monthly_permission_hours_limit ?? 2.0',
            $controller,
            'لسه في حد افتراضي متعارض (2 بدل 4)'
        );
    }
}
