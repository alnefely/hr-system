<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLeave;
use App\Models\EmployeePermission;
use App\Models\JobTitle;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Role;
use App\Services\Portal\PortalAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بوابة الموظف: الدخول برقم الهاتف، الإيقاف بسبب، والخدمة الذاتية.
 */
class EmployeePortalTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Secret123';

    protected Branch $branch;
    protected JobTitle $jobTitle;
    protected Employee $employee;
    protected Employee $colleague;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true,
        ]);
        $this->jobTitle = JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        $this->employee  = $this->makeEmployee('+201000000001', 'HQ-0001');
        $this->colleague = $this->makeEmployee('+201000000002', 'HQ-0002');

        app(PortalAccessService::class)->enable($this->employee, self::PASSWORD);
        app(PortalAccessService::class)->enable($this->colleague, self::PASSWORD);
    }

    private function makeEmployee(string $phone, string $code, string $status = 'active'): Employee
    {
        return Employee::create([
            'branch_id' => $this->branch->id, 'job_title_id' => $this->jobTitle->id,
            'name' => 'Emp ' . $code, 'employee_code' => $code, 'phone' => $phone,
            'status' => $status, 'basic_salary' => 6000, 'hire_date' => '2024-01-01',
        ]);
    }

    private function login(string $phone = '+201000000001', string $password = self::PASSWORD)
    {
        return $this->post(route('portal.login.attempt'), ['phone' => $phone, 'password' => $password]);
    }

    /**
     * صفوف جدول البوابة زي ما DataTables بيطلبها (ajax على نفس رابط الصفحة).
     *
     * @return \Illuminate\Support\Collection<int, array>
     */
    private function rows(string $url): \Illuminate\Support\Collection
    {
        return collect($this->get($url, ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'));
    }

    /** دخول وكلمة المرور الأولية اتغيّرت — للاختبارات اللي مش بتختبر الدخول نفسه. */
    private function actingAsEmployee(?Employee $employee = null): void
    {
        $employee ??= $this->employee;
        $employee->forceFill(['must_change_password' => false])->save();
        $this->actingAs($employee->fresh(), 'employee');
    }

    // ========================================================
    //  الدخول
    // ========================================================

    public function test_employee_logs_in_with_phone_and_must_change_initial_password(): void
    {
        $this->login()->assertRedirect(route('portal.home'));
        $this->assertAuthenticatedAs($this->employee->fresh(), 'employee');

        // أول دخول: أي صفحة بتحوّل لتغيير كلمة المرور
        $this->get(route('portal.home'))->assertRedirect(route('portal.password.edit'));

        $this->put(route('portal.password.update'), [
            'current_password' => self::PASSWORD,
            'password' => 'NewPass456', 'password_confirmation' => 'NewPass456',
        ])->assertRedirect(route('portal.home'));

        $this->assertFalse($this->employee->fresh()->must_change_password);
        $this->get(route('portal.home'))->assertOk();
        $this->assertNotNull($this->employee->fresh()->last_login_at);
    }

    /** نفس الرسالة للرقم غير الموجود ولكلمة المرور الغلط — مانكشفش المسجّلين. */
    public function test_wrong_credentials_give_the_same_generic_error(): void
    {
        $this->login(password: 'wrong')->assertSessionHas('error', __('portal.auth.invalid_credentials'));
        $this->login('+209999999999')->assertSessionHas('error', __('portal.auth.invalid_credentials'));
        $this->assertGuest('employee');
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        foreach (range(1, 5) as $i) {
            $this->login(password: 'wrong');
        }

        $this->login()->assertSessionHas('error');
        $this->assertGuest('employee');
    }

    /** السبب بيظهر لصاحب الحساب اللي عرف كلمة المرور بس. */
    public function test_suspended_employee_sees_the_reason(): void
    {
        app(PortalAccessService::class)->disable($this->employee, 'مراجعة إدارية');

        $this->login(password: 'wrong')->assertSessionMissing('blocked');

        $this->login()->assertSessionHas('blocked', __('portal.blocked.suspended', ['reason' => 'مراجعة إدارية']));
        $this->assertGuest('employee');
    }

    public function test_not_activated_and_terminated_employees_cannot_log_in(): void
    {
        $new = $this->makeEmployee('+201000000003', 'HQ-0003');
        $new->forceFill(['password' => self::PASSWORD])->save(); // كلمة مرور من غير تفعيل

        $this->login('+201000000003')->assertSessionHas('blocked', __('portal.blocked.not_activated'));

        $this->employee->update(['status' => 'terminated']);
        $this->login()->assertSessionHas('blocked', __('portal.blocked.terminated'));
        $this->assertGuest('employee');
    }

    /** الإيقاف بيطلّع الموظف فوراً حتى لو كان فاتح البوابة. */
    public function test_suspension_logs_out_an_active_session(): void
    {
        // دخول حقيقي عبر الجلسة (مش actingAs) عشان كل طلب يعيد قراءة الموظف
        $this->employee->forceFill(['must_change_password' => false])->save();
        $this->login();
        $this->get(route('portal.home'))->assertOk();

        app(PortalAccessService::class)->disable($this->employee->fresh(), 'تسوية');

        // طلب جديد: الـ guard بيقرا الموظف من قاعدة البيانات من أول وجديد
        $this->app['auth']->forgetGuards();

        $this->get(route('portal.home'))
            ->assertRedirect(route('portal.login'))
            ->assertSessionHas('blocked');
        $this->assertGuest('employee');
    }

    /** الجلستين منفصلتين: الموظف مايوصلش للإدارة، والمشرف مايفتحش البوابة. */
    public function test_portal_and_admin_sessions_are_isolated(): void
    {
        $this->actingAsEmployee();
        $this->get(route('admin.home'))->assertRedirect(route('admin.login'));

        $this->app['auth']->forgetGuards();
        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $this->actingAs(Admin::create([
            'name' => 'Root', 'email' => 'root@test.com', 'password' => bcrypt('password'),
            'main' => true, 'role_id' => $role->id,
        ]), 'admin');

        $this->get(route('portal.home'))->assertRedirect(route('portal.login'));
    }

    // ========================================================
    //  الخدمة الذاتية
    // ========================================================

    public function test_permission_request_is_created_for_self_and_notifies_reviewers(): void
    {
        $role = Role::create(['name' => 'HR', 'roles' => ['permission_show', 'dashboard_show']]);
        $hr = Admin::create([
            'name' => 'HR', 'email' => 'hr@test.com', 'password' => bcrypt('password'),
            'main' => true, 'role_id' => $role->id,
        ]);

        $this->actingAsEmployee();

        $this->post(route('portal.permissions.store'), [
            'employee_id' => $this->colleague->id, // محاولة تسجيل لزميل — بتتجاهل
            'permission_date' => '2026-11-20', 'start_time' => '09:00', 'end_time' => '10:30',
            'hours_count' => 8,                    // بتتجاهل — الساعات من الوقت
            'reason' => 'مشوار',
        ])->assertRedirect(route('portal.permissions.index'));

        $permission = EmployeePermission::sole();
        $this->assertSame($this->employee->id, $permission->employee_id);
        $this->assertSame('pending', $permission->status);
        $this->assertEquals(1.5, (float) $permission->hours_count);

        $this->assertTrue(AdminNotification::where('admin_id', $hr->id)
            ->where('type', NotificationType::PermissionRequested->value)->exists());
    }

    public function test_leave_and_advance_requests_follow_the_same_rules_as_admin(): void
    {
        $this->actingAsEmployee();

        $this->post(route('portal.leaves.store'), [
            'leave_type' => 'annual', 'start_date' => '2026-12-01', 'end_date' => '2026-12-03', 'days_count' => 3,
        ])->assertSessionHas('success');

        // تداخل مع الطلب الأول
        $this->post(route('portal.leaves.store'), [
            'leave_type' => 'casual', 'start_date' => '2026-12-02', 'end_date' => '2026-12-02', 'days_count' => 1,
        ])->assertSessionHas('error', __('leave.errors.overlap'));

        $this->post(route('portal.advances.store'), [
            'amount' => 1200, 'installments_count' => 3,
            'start_date' => now()->addMonth()->startOfMonth()->toDateString(), 'reason' => 'ظرف طارئ',
        ])->assertSessionHas('success');

        $this->assertSame(1, EmployeeLeave::where('employee_id', $this->employee->id)->count());
        $advance = EmployeeAdvance::sole();
        $this->assertSame($this->employee->id, $advance->employee_id);
        $this->assertSame('pending', $advance->status);
        $this->assertEquals(400, (float) $advance->monthly_installment);
    }

    public function test_employee_can_cancel_only_own_pending_requests(): void
    {
        $mine = EmployeePermission::create([
            'employee_id' => $this->employee->id, 'permission_date' => '2026-11-20',
            'start_time' => '09:00', 'end_time' => '10:00', 'hours_count' => 1, 'status' => 'pending',
        ]);
        $decided = EmployeePermission::create([
            'employee_id' => $this->employee->id, 'permission_date' => '2026-11-21',
            'start_time' => '09:00', 'end_time' => '10:00', 'hours_count' => 1, 'status' => 'approved',
        ]);
        $theirs = EmployeePermission::create([
            'employee_id' => $this->colleague->id, 'permission_date' => '2026-11-20',
            'start_time' => '09:00', 'end_time' => '10:00', 'hours_count' => 1, 'status' => 'pending',
        ]);

        $this->actingAsEmployee();

        $this->delete(route('portal.permissions.destroy', $theirs->id))->assertNotFound();
        $this->delete(route('portal.permissions.destroy', $decided->id))->assertSessionHas('error');
        $this->delete(route('portal.permissions.destroy', $mine->id))->assertSessionHas('success');

        $this->assertSoftDeleted($mine);
        $this->assertNotSoftDeleted($decided);
        $this->assertNotSoftDeleted($theirs);
    }

    public function test_lists_show_only_own_records(): void
    {
        EmployeeLeave::create([
            'employee_id' => $this->colleague->id, 'leave_type' => 'annual',
            'start_date' => '2026-12-10', 'end_date' => '2026-12-10', 'days_count' => 1,
            'status' => 'pending', 'reason' => 'COLLEAGUE-SECRET',
        ]);

        $mine = EmployeeLeave::create([
            'employee_id' => $this->employee->id, 'leave_type' => 'annual',
            'start_date' => '2026-12-12', 'end_date' => '2026-12-12', 'days_count' => 1,
            'status' => 'pending', 'reason' => 'MINE',
        ]);

        $this->actingAsEmployee();

        // الجدول (server-side): صف واحد بس — بتاعي
        $rows = $this->rows(route('portal.leaves.index'));
        $this->assertSame([$mine->id], $rows->pluck('id')->all());

        $this->get(route('portal.leaves.index'))->assertOk()->assertDontSee('COLLEAGUE-SECRET');
        $this->get(route('portal.home'))->assertOk();
        $this->get(route('portal.profile'))->assertOk()->assertSee($this->employee->name);
    }

    /** قسائم الراتب: المصروفة بس، وبتاعتي بس. */
    public function test_payslips_show_only_own_paid_items(): void
    {
        $payroll = Payroll::create([
            'title' => 'Nov', 'year' => 2026, 'month' => 11, 'branch_id' => $this->branch->id, 'status' => 'paid',
        ]);
        $item = fn (Employee $e, string $status) => PayrollItem::create([
            'payroll_id' => $payroll->id, 'employee_id' => $e->id, 'basic_salary' => 6000,
            'allowances' => 0, 'deductions' => 0, 'status' => $status,
            'paid_at' => $status === 'paid' ? now() : null,
        ]);

        $paid    = $item($this->employee, 'paid');
        $theirs  = $item($this->colleague, 'paid');
        $payroll2 = Payroll::create([
            'title' => 'Dec', 'year' => 2026, 'month' => 12, 'branch_id' => $this->branch->id, 'status' => 'approved',
        ]);
        $unpaid = PayrollItem::create([
            'payroll_id' => $payroll2->id, 'employee_id' => $this->employee->id, 'basic_salary' => 6000,
            'allowances' => 0, 'deductions' => 0, 'status' => 'pending',
        ]);

        $this->actingAsEmployee();

        $this->get(route('portal.payslips.show', $paid->id))->assertOk();
        $this->get(route('portal.payslips.show', $theirs->id))->assertNotFound();
        $this->get(route('portal.payslips.show', $unpaid->id))->assertNotFound();

        // الجدول (server-side): المصروفة بتاعتي بس
        $this->assertSame([$paid->id], $this->rows(route('portal.payslips.index'))->pluck('id')->all());
    }

    /** أيام الإجازة بتتحسب من التاريخين — المكتوب بيتجاهل، ونص اليوم لليوم الواحد بس. */
    public function test_portal_leave_days_are_calculated_from_dates(): void
    {
        $this->actingAsEmployee();

        $this->post(route('portal.leaves.store'), [
            'leave_type' => 'annual', 'start_date' => '2026-12-01', 'end_date' => '2026-12-03', 'days_count' => 99,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(3, EmployeeLeave::whereDate('start_date', '2026-12-01')->sole()->days_count);

        $this->post(route('portal.leaves.store'), [
            'leave_type' => 'casual', 'start_date' => '2026-12-10', 'end_date' => '2026-12-10', 'half_day' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(0.5, EmployeeLeave::whereDate('start_date', '2026-12-10')->sole()->days_count);

        // نص يوم لأكتر من يوم: مرفوض
        $this->post(route('portal.leaves.store'), [
            'leave_type' => 'casual', 'start_date' => '2026-12-20', 'end_date' => '2026-12-21', 'half_day' => 1,
        ])->assertSessionHasErrors('half_day');
    }

    /**
     * كل عمود في جداول البوابة (columns.data) راجع من السيرفر — وإلا
     * DataTables بيطلّع "Requested unknown parameter" للموظف.
     */
    public function test_portal_table_columns_match_server_rows(): void
    {
        $this->actingAsEmployee();
        $e = $this->employee;
        $day = now()->toDateString();

        // صف واحد على الأقل في كل جدول
        EmployeeLeave::create(['employee_id' => $e->id, 'leave_type' => 'annual', 'start_date' => $day, 'end_date' => $day, 'days_count' => 1, 'status' => 'pending']);
        \App\Models\EmployeePermission::create(['employee_id' => $e->id, 'permission_date' => $day, 'start_time' => '10:00', 'end_time' => '11:00', 'hours_count' => 1, 'status' => 'pending']);
        \App\Models\EmployeeAdvance::create(['employee_id' => $e->id, 'amount' => 300, 'installments_count' => 3, 'monthly_installment' => 100, 'remaining_amount' => 300, 'advance_date' => $day, 'start_date' => $day, 'status' => 'pending', 'reason' => 'x']);
        \App\Models\EmployeeReward::create(['employee_id' => $e->id, 'reward_type' => 'bonus', 'title' => 'R', 'amount' => 10, 'reward_date' => $day, 'status' => 'approved']);
        \App\Models\EmployeePenalty::create(['employee_id' => $e->id, 'penalty_type' => 'warning', 'penalty_date' => $day, 'reason' => 'x', 'status' => 'approved']);
        \App\Models\Expense::create(['title' => 'E', 'expense_category_id' => \App\Models\ExpenseCategory::create(['name' => 'C', 'code' => 'C1', 'is_active' => true])->id,
            'branch_id' => $this->branch->id, 'employee_id' => $e->id, 'amount' => 5, 'currency' => 'EGP', 'expense_date' => $day, 'payment_method' => 'cash']);
        $payroll = Payroll::create(['title' => 'P', 'year' => 2026, 'month' => 1, 'branch_id' => $this->branch->id, 'status' => 'paid']);
        PayrollItem::create(['payroll_id' => $payroll->id, 'employee_id' => $e->id, 'basic_salary' => 6000, 'allowances' => 0, 'deductions' => 0, 'status' => 'paid', 'paid_at' => now()]);

        foreach (['permissions', 'leaves', 'advances', 'rewards', 'penalties', 'expenses', 'payslips'] as $table) {
            $url = route("portal.{$table}.index");
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertSame(1, preg_match('/var columns = (\[.*?\]);\r?\n/s', $html, $m), "{$table}: تعريف الأعمدة مش موجود");

            $row = $this->rows($url)->first();
            $this->assertIsArray($row, "{$table}: مفيش صفوف");

            foreach (json_decode($m[1], true) as $column) {
                if (isset($column['data']) && ! array_key_exists('defaultContent', $column)) {
                    $this->assertArrayHasKey($column['data'], $row, "{$table}: العمود «{$column['data']}» مش راجع من السيرفر");
                }
            }
        }
    }

    /** جداول الطلبات (server-side) بتتفلتر بالتاريخ ومابتعرضش غير طلباتي. */
    public function test_request_tables_are_server_side_and_date_filtered(): void
    {
        $this->actingAsEmployee();

        $perm = fn (Employee $e, string $date) => \App\Models\EmployeePermission::create([
            'employee_id' => $e->id, 'permission_date' => $date, 'start_time' => '10:00', 'end_time' => '11:00',
            'hours_count' => 1, 'status' => 'pending',
        ]);

        $nov = $perm($this->employee, '2026-11-10');
        $oct = $perm($this->employee, '2026-10-10');
        $perm($this->colleague, '2026-11-10');

        $this->assertEqualsCanonicalizing([$nov->id, $oct->id], $this->rows(route('portal.permissions.index'))->pluck('id')->all());
        $this->assertSame([$nov->id], $this->rows(route('portal.permissions.index', ['from_date' => '2026-11-01', 'to_date' => '2026-11-30']))->pluck('id')->all());

        // زرار الإلغاء جاي جاهز مع الصف المعلّق
        $this->assertStringContainsString(route('portal.permissions.destroy', $nov->id), $this->rows(route('portal.permissions.index'))->firstWhere('id', $nov->id)['action']);

        // السلف
        \App\Models\EmployeeAdvance::create([
            'employee_id' => $this->employee->id, 'amount' => 1200, 'installments_count' => 3, 'monthly_installment' => 400,
            'remaining_amount' => 1200, 'advance_date' => '2026-11-01', 'start_date' => '2026-12-01', 'status' => 'pending', 'reason' => 'x',
        ]);
        $this->assertCount(1, $this->rows(route('portal.advances.index')));
        $this->assertCount(0, $this->rows(route('portal.advances.index', ['from_date' => '2026-12-01'])));
    }

    /** كل شاشات البوابة بتفتح من غير أخطاء. */
    public function test_every_portal_page_renders(): void
    {
        $this->get(route('portal.login'))->assertOk()->assertSee(__('portal.auth.title'));

        $this->actingAsEmployee();

        foreach (['portal.home', 'portal.profile', 'portal.password.edit', 'portal.permissions.index',
                  'portal.leaves.index', 'portal.advances.index', 'portal.payslips.index',
                  'portal.rewards.index', 'portal.penalties.index', 'portal.expenses.index',
                  'portal.documents.index', 'portal.salary.index'] as $route) {
            $this->get(route($route))->assertOk();
        }

        // المسجّل لو فتح شاشة الدخول يروح للرئيسية
        $this->get(route('portal.login'))->assertRedirect(route('portal.home'));
    }

    // ========================================================
    //  لوحة الإدارة
    // ========================================================

    public function test_employee_profile_shows_portal_card(): void
    {
        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $this->actingAs(Admin::create([
            'name' => 'Root', 'email' => 'root@test.com', 'password' => bcrypt('password'),
            'main' => true, 'role_id' => $role->id,
        ]), 'admin');

        $this->get(route('admin.employees.show', $this->employee->id))
            ->assertOk()
            ->assertSee(__('portal.admin.title'))
            ->assertSee(route('admin.employees.portal.disable', $this->employee->id), false);

        $this->get(route('admin.employees.create'))->assertOk()->assertSee(__('portal.admin.form_hint_new'));
    }

    private function actingAsAdmin(array $permissions, ?int $employeeId = null): Admin
    {
        $role = Role::create(['name' => 'R' . uniqid(), 'roles' => $permissions]);
        $admin = Admin::create([
            'name' => 'Admin', 'email' => uniqid() . '@test.com', 'password' => bcrypt('password'),
            'main' => false, 'role_id' => $role->id, 'employee_id' => $employeeId,
        ]);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_hr_enables_suspends_and_resets_portal_access(): void
    {
        $new = $this->makeEmployee('+201000000009', 'HQ-0009');
        $this->actingAsAdmin(['employee_show', 'employee_portal_manage']);

        $this->post(route('admin.employees.portal.enable', $new->id), [
            'password' => 'Initial123', 'password_confirmation' => 'Initial123',
        ])->assertSessionHas('success');
        $this->assertTrue($new->fresh()->canAccessPortal());
        $this->assertTrue($new->fresh()->must_change_password);

        // الإيقاف من غير سبب بيترفض
        $this->post(route('admin.employees.portal.disable', $new->id), ['reason' => ''])->assertSessionHasErrors('reason');

        $this->post(route('admin.employees.portal.disable', $new->id), ['reason' => 'انتهاء العقد'])->assertSessionHas('success');
        $this->assertSame('انتهاء العقد', $new->fresh()->portal_disabled_reason);
        $this->assertFalse($new->fresh()->canAccessPortal());

        // إعادة التفعيل بتمسح السبب
        $this->post(route('admin.employees.portal.enable', $new->id), [
            'password' => 'Again1234', 'password_confirmation' => 'Again1234',
        ]);
        $this->assertNull($new->fresh()->portal_disabled_reason);

        $this->post(route('admin.employees.portal.reset-password', $new->id), [
            'password' => 'Reset1234', 'password_confirmation' => 'Reset1234',
        ])->assertSessionHas('success');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Reset1234', $new->fresh()->password));
    }

    public function test_portal_management_requires_permission_and_respects_branch(): void
    {
        $this->actingAsAdmin(['employee_show']);
        $this->post(route('admin.employees.portal.disable', $this->employee->id), ['reason' => 'x x x'])
            ->assertRedirect(route('admin.error_403'));

        // مشرف فرع تاني مايوصلش لموظف الفرع ده
        $other = Branch::create(['name' => 'Other', 'code' => 'OT', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $otherEmp = Employee::create([
            'branch_id' => $other->id, 'job_title_id' => $this->jobTitle->id, 'name' => 'Other Mgr',
            'employee_code' => 'OT-0001', 'phone' => '+201000000077', 'status' => 'active',
            'basic_salary' => 1000, 'hire_date' => '2024-01-01',
        ]);
        $this->app['auth']->forgetGuards();
        $this->actingAsAdmin(['employee_show', 'employee_portal_manage'], $otherEmp->id);

        $this->post(route('admin.employees.portal.disable', $this->employee->id), ['reason' => 'x x x'])->assertNotFound();
        $this->assertTrue($this->employee->fresh()->canAccessPortal());
    }

    public function test_terminated_employee_cannot_be_enabled(): void
    {
        $gone = $this->makeEmployee('+201000000010', 'HQ-0010', 'terminated');
        $this->actingAsAdmin(['employee_portal_manage']);

        $this->post(route('admin.employees.portal.enable', $gone->id), [
            'password' => 'Initial123', 'password_confirmation' => 'Initial123',
        ])->assertSessionHas('error', __('portal.admin.cannot_enable_terminated'));

        $this->assertFalse($gone->fresh()->portal_enabled);
    }

    public function test_portal_can_be_enabled_while_creating_employee(): void
    {
        $this->actingAsAdmin(['employee_create', 'employee_portal_manage']);

        $payload = [
            'name' => 'New Hire', 'phone' => '+201000000055', 'branch_id' => $this->branch->id,
            'job_title_id' => $this->jobTitle->id, 'hire_date' => '2026-01-01', 'status' => 'active',
        ];

        // أقصر من الحد الأدنى بتترفض
        $this->post(route('admin.employees.store'), $payload + ['portal_password' => '12'])
            ->assertSessionHasErrors('portal_password');

        // أرقام بس ومن غير تأكيد: مقبولة
        $this->post(route('admin.employees.store'), $payload + ['portal_password' => '1234'])
            ->assertSessionHasNoErrors();

        $hire = Employee::where('phone', '+201000000055')->sole();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('1234', $hire->password));
        $this->assertTrue($hire->canAccessPortal());
        $this->assertTrue($hire->must_change_password);
    }

    /** من غير كلمة مرور الموظف بيتضاف والدخول مقفول — يتفعّل بعدين. */
    public function test_employee_created_without_portal_password_stays_inactive(): void
    {
        $this->actingAsAdmin(['employee_create', 'employee_portal_manage']);

        $this->post(route('admin.employees.store'), [
            'name' => 'Later', 'phone' => '+201000000056', 'branch_id' => $this->branch->id,
            'job_title_id' => $this->jobTitle->id, 'hire_date' => '2026-01-01', 'status' => 'active',
        ])->assertSessionHasNoErrors();

        $this->assertFalse(Employee::where('phone', '+201000000056')->sole()->portal_enabled);
    }

    /** التعديل: كلمة مرور جديدة بتفعّل (أو تلغي الإيقاف)، والفاضية مابتغيّرش حاجة. */
    public function test_portal_password_from_edit_form_enables_or_keeps_access(): void
    {
        $this->actingAsAdmin(['employee_update', 'employee_portal_manage']);

        $employee = $this->makeEmployee('+201000000057', 'HQ-0057');
        $employee->forceFill(['portal_disabled_reason' => 'old reason'])->save();
        $base = [
            'name' => $employee->name, 'phone' => $employee->phone, 'branch_id' => $this->branch->id,
            'job_title_id' => $this->jobTitle->id, 'hire_date' => '2026-01-01', 'status' => 'active',
        ];

        $this->put(route('admin.employees.update', $employee), $base)->assertSessionHasNoErrors();
        $this->assertFalse($employee->fresh()->portal_enabled);

        $this->put(route('admin.employees.update', $employee), $base + [
            'portal_password' => 'Welcome123', 'portal_password_confirmation' => 'Welcome123',
        ])->assertSessionHasNoErrors();

        $fresh = $employee->fresh();
        $this->assertTrue($fresh->canAccessPortal());
        $this->assertNull($fresh->portal_disabled_reason);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Welcome123', $fresh->password));

        // حفظ عادي من غير كلمة مرور: الحالة وكلمة المرور زي ما هي
        $this->put(route('admin.employees.update', $employee), $base)->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Welcome123', $employee->fresh()->password));
        $this->assertTrue($employee->fresh()->portal_enabled);
    }

    /** شاشتي الإضافة والتعديل فيهم جزء البوابة، ومافيهمش حساب لوحة التحكم. */
    public function test_employee_forms_show_portal_section_without_admin_account(): void
    {
        $this->actingAsAdmin(['employee_create', 'employee_update', 'employee_portal_manage']);
        $employee = $this->employee;

        foreach ([route('admin.employees.create'), route('admin.employees.edit', $employee)] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('name="portal_password"', false)
                ->assertDontSee('name="create_account"', false)
                ->assertDontSee('name="account_email"', false);
        }
    }

    /** حساب لوحة التحكم بيتربط بملف الموظف من شاشة المشرفين، والربط متطابق في الجدولين. */
    public function test_admin_account_is_linked_to_employee_from_admins_screen(): void
    {
        $this->actingAsAdmin(['admin_create', 'admin_update']);
        $role = Role::create(['name' => 'Accountant', 'roles' => ['expense_show']]);

        $this->get(route('admin.admins.create'))->assertOk()->assertSee($this->employee->employee_code);

        $this->post(route('admin.admins.store'), [
            'name' => 'Acc', 'email' => 'acc@test.com', 'password' => 'secret12',
            'role_id' => $role->id, 'employee_id' => $this->employee->id,
        ])->assertSessionHasNoErrors();

        $acc = Admin::where('email', 'acc@test.com')->sole();
        $this->assertSame($this->employee->id, $acc->employee_id);
        $this->assertSame($acc->id, $this->employee->fresh()->admin_id);

        // الموظف الواحد مايتربطش بمشرفين اتنين
        $this->post(route('admin.admins.store'), [
            'name' => 'Dup', 'email' => 'dup@test.com', 'password' => 'secret12',
            'role_id' => $role->id, 'employee_id' => $this->employee->id,
        ])->assertSessionHasErrors('employee_id');

        // تغيير الربط لموظف تاني بيفك الربط القديم
        $this->put(route('admin.admins.update', $acc), [
            'name' => 'Acc', 'email' => 'acc@test.com', 'role_id' => $role->id,
            'employee_id' => $this->colleague->id,
        ])->assertSessionHasNoErrors();

        $this->assertNull($this->employee->fresh()->admin_id);
        $this->assertSame($acc->id, $this->colleague->fresh()->admin_id);
    }

    /**
     * مكتبة الأرقام في شاشة الدخول: الـ CSS لازم يوصل للـ head والـ JS للصفحة.
     * لو الشاشة اترجعت صفحة مستقلة (مش layout) الـ head بيترندر قبل المكوّن
     * والـ CSS بيضيع فالحقل بيبان مكسور.
     */
    public function test_login_page_loads_phone_library_assets(): void
    {
        $html = $this->get(route('portal.login'))->assertOk()->getContent();
        $head = substr($html, 0, strpos($html, '</head>'));

        $this->assertStringContainsString('intlTelInput.min.css', $head);
        $this->assertStringContainsString('intlTelInput.min.js', $html);
        $this->assertStringContainsString('data-iti-phone', $html);
    }

    /** أي صيغة للرقم بتتوحّد للصيغة الدولية. */
    public function test_phone_numbers_are_normalized_to_e164(): void
    {
        $cases = [
            ['10 21464469', 'EG', '+201021464469'],
            ['01021464469', 'EG', '+201021464469'],
            ['+20 102 146 4469', 'EG', '+201021464469'],
            ['00201021464469', 'EG', '+201021464469'],
            ['201021464469', 'EG', '+201021464469'],
            ['0501234567', 'SA', '+966501234567'],
        ];

        foreach ($cases as [$input, $country, $expected]) {
            $this->assertSame($expected, \App\Support\PhoneNumber::toE164($input, $country), $input);
        }
    }

    /** الدخول شغال حتى لو الرقم وصل بالصيغة المحلية (الـ JS ماشتغلش). */
    public function test_login_accepts_local_phone_format(): void
    {
        $this->login('01000000001')->assertRedirect(route('portal.home'));
        $this->assertAuthenticatedAs($this->employee->fresh(), 'employee');
    }

    /** الحفظ من شاشة الموظف بيوحّد الرقم، والتكرار بيتكشف بعد التوحيد. */
    public function test_employee_form_stores_phone_in_international_format(): void
    {
        $this->actingAsAdmin(['employee_create']);

        $payload = [
            'name' => 'Local', 'branch_id' => $this->branch->id, 'job_title_id' => $this->jobTitle->id,
            'hire_date' => '2026-01-01', 'status' => 'active', 'phone_country' => 'EG',
        ];

        $this->post(route('admin.employees.store'), $payload + ['phone' => '010 2222 3333'])->assertSessionHasNoErrors();
        $this->assertTrue(Employee::where('phone', '+201022223333')->exists());

        // نفس الرقم بصيغة تانية = مكرر
        $this->post(route('admin.employees.store'), $payload + ['phone' => '+201022223333'])
            ->assertSessionHasErrors('phone');
    }

    /** نوافذ البوابة برّه التبويبات — جوّاها الخلفية المعتمة بتغطيها والصفحة بتقف. */
    public function test_portal_modals_render_outside_profile_tabs(): void
    {
        $this->actingAsAdmin(['employee_show', 'employee_portal_manage']);

        $html = $this->get(route('admin.employees.show', $this->employee->id))->assertOk()->getContent();

        $modal = strpos($html, 'id="portalResetModal"');
        $this->assertNotFalse($modal);
        $this->assertGreaterThan(strpos($html, 'id="tab-skills"'), $modal);
    }

    /** التأكيد العام قبل الإرسال موجود في اللوحتين (ومكتبته محمّلة في البوابة). */
    public function test_confirm_before_submit_is_loaded_in_both_panels(): void
    {
        $this->actingAsEmployee();
        $this->get(route('portal.permissions.index'))->assertOk()
            ->assertSee('__confirmSubmitBound', false)
            ->assertSee('sweetalert2.js', false);

        $this->actingAsAdmin(['employee_show']);
        $this->get(route('admin.employees.show', $this->employee->id))->assertOk()
            ->assertSee('__confirmSubmitBound', false);

        // شاشات الدخول مالهاش تأكيد
        auth('admin')->logout();
        auth('employee')->logout();
        $this->get(route('portal.login'))->assertDontSee('__confirmSubmitBound', false);
    }

    /** مكافآتي: المعتمد والمصروف بتوعي بس — لا المعلّق ولا المرفوض ولا بتوع زميلي. */
    public function test_employee_sees_only_own_approved_and_paid_rewards(): void
    {
        $make = fn (Employee $e, string $title, string $status, float $amount) => \App\Models\EmployeeReward::create([
            'employee_id' => $e->id, 'reward_type' => 'bonus', 'title' => $title,
            'amount' => $amount, 'reward_date' => now()->toDateString(), 'status' => $status,
        ]);

        $make($this->employee, 'Approved One', 'approved', 300);
        $make($this->employee, 'Paid One', 'paid', 200);
        $make($this->employee, 'Pending One', 'pending', 999);
        $make($this->employee, 'Rejected One', 'rejected', 888);
        $make($this->colleague, 'Colleague Reward', 'approved', 777);

        $this->actingAsEmployee();

        // الجدول: المعتمد والمصروف بتوعي بس
        $titles = $this->rows(route('portal.rewards.index'))->pluck('title')->implode(' ');
        $this->assertStringContainsString('Approved One', $titles);
        $this->assertStringContainsString('Paid One', $titles);
        foreach (['Pending One', 'Rejected One', 'Colleague Reward'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $titles);
        }

        // الكروت الملخّصة
        $this->get(route('portal.rewards.index'))->assertOk()
            ->assertSee('500.00')   // إجمالي الفترة
            ->assertSee('300.00')   // معتمد لسه هيتصرف
            ->assertSee('200.00');  // تم صرفه

        // فلتر بفترة قديمة: مفيش حاجة
        $old = ['from_date' => '2020-01-01', 'to_date' => '2020-12-31'];
        $this->assertCount(0, $this->rows(route('portal.rewards.index', $old)));
    }

    /** جزاءاتي: المعتمد بتاعي بس، والمبلغ بنفس حساب المسير. */
    public function test_employee_sees_only_own_approved_penalties(): void
    {
        $make = fn (Employee $e, string $reason, string $status, array $extra = []) => \App\Models\EmployeePenalty::create([
            'employee_id' => $e->id, 'penalty_date' => now()->toDateString(), 'reason' => $reason, 'status' => $status,
        ] + $extra + ['penalty_type' => 'amount_deduction', 'deduction_amount' => 150]);

        $make($this->employee, 'Late arrival', 'approved');
        // خصم أيام من غير مبلغ مخزّن: يوم × (6000 / 30) = 200
        $make($this->employee, 'Absent day', 'approved', ['penalty_type' => 'days_deduction', 'days_count' => 1, 'deduction_amount' => 0]);
        $make($this->employee, 'Pending case', 'pending');
        $make($this->employee, 'Rejected case', 'rejected');
        $make($this->colleague, 'Colleague case', 'approved');

        $this->actingAsEmployee();

        $rows = $this->rows(route('portal.penalties.index'));
        $this->assertEqualsCanonicalizing(['Late arrival', 'Absent day'], $rows->pluck('reason')->all());

        $deductions = $rows->pluck('deduction')->implode(' ');
        $this->assertStringContainsString('-150.00', $deductions);
        $this->assertStringContainsString('-200.00', $deductions);

        $this->get(route('portal.penalties.index'))->assertOk()->assertSee('350.00');   // إجمالي الفترة
    }

    /** مصروفات عهدتي: بتاعتي في الشهر المختار بس، والإجمالي لكل عملة. */
    public function test_employee_sees_own_custody_expenses_by_month(): void
    {
        $category = \App\Models\ExpenseCategory::create(['name' => 'Fuel', 'code' => 'FUEL', 'is_active' => true]);

        $make = fn (?Employee $e, string $title, string $date, float $amount, string $currency = 'EGP') => \App\Models\Expense::create([
            'title' => $title, 'expense_category_id' => $category->id, 'branch_id' => $this->branch->id,
            'employee_id' => $e?->id, 'amount' => $amount, 'currency' => $currency,
            'expense_date' => $date, 'payment_method' => 'cash',
        ]);

        $make($this->employee, 'Taxi November', '2026-11-05', 120);
        $make($this->employee, 'Printer ink', '2026-11-20', 80);
        $make($this->employee, 'Hotel USD', '2026-11-21', 50, 'USD');
        $make($this->employee, 'October fuel', '2026-10-10', 300);
        $make($this->colleague, 'Colleague taxi', '2026-11-06', 999);
        $make(null, 'Branch rent', '2026-11-01', 5000);

        $this->actingAsEmployee();

        $november = ['from_date' => '2026-11-01', 'to_date' => '2026-11-30'];

        $titles = $this->rows(route('portal.expenses.index', $november))->pluck('title')->implode(' ');
        foreach (['Taxi November', 'Printer ink', 'Hotel USD'] as $shown) {
            $this->assertStringContainsString($shown, $titles);
        }
        foreach (['October fuel', 'Colleague taxi', 'Branch rent'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $titles);
        }

        // الإجمالي لكل عملة لوحدها
        $this->get(route('portal.expenses.index', $november))->assertOk()
            ->assertSee('200.00')     // EGP: 120 + 80
            ->assertSee('50.00');     // USD لوحده

        $october = $this->rows(route('portal.expenses.index', ['from_date' => '2026-10-01', 'to_date' => '2026-10-31']));
        $this->assertStringContainsString('October fuel', $october->pluck('title')->implode(' '));
        $this->assertCount(1, $october);

        // "كل الفترات" (فاضي صراحةً): الأربعة بتوعي
        $this->assertCount(4, $this->rows(route('portal.expenses.index', ['from_date' => '', 'to_date' => ''])));

        // تاريخ بصيغة غلط بيتجاهل من غير خطأ
        $this->get(route('portal.expenses.index', ['from_date' => 'bad', 'to_date' => '2026-13-45']))->assertOk();
    }

    /** بياناتي: الراتب الحالي والبدلات وسجل التعديلات. */
    public function test_profile_shows_salary_allowances_and_history(): void
    {
        $this->employee->allowances()->create(['name' => 'Transport', 'amount' => 500]);
        \App\Models\EmployeeSalary::create([
            'employee_id' => $this->employee->id, 'basic_salary' => 5000, 'allowances' => 0,
            'currency' => 'EGP', 'effective_date' => '2025-01-01', 'change_reason' => 'hiring',
        ]);
        \App\Models\EmployeeSalary::create([
            'employee_id' => $this->employee->id, 'basic_salary' => 6000, 'allowances' => 500,
            'currency' => 'EGP', 'effective_date' => '2026-01-01', 'change_reason' => 'annual_raise',
        ]);

        $this->actingAsEmployee();

        $this->get(route('portal.salary.index'))->assertOk()
            ->assertSee(__('portal.profile.salary'))
            ->assertSee('Transport')
            ->assertSee('6,500.00')                       // 6000 + 500
            ->assertSee(__('salary.reasons.annual_raise'))
            ->assertSee(__('salary.reasons.hiring'));
    }

    /** الرئيسية بتنبّه بالمستندات المنتهية أو القريبة من الانتهاء بس. */
    public function test_home_alerts_about_expiring_documents(): void
    {
        $doc = fn (string $title, ?string $expiry) => $this->employee->documents()->create([
            'document_type' => 'other', 'title' => $title, 'file_path' => '/x.pdf', 'expiry_date' => $expiry,
        ]);

        $doc('Old Passport', now()->subDays(3)->toDateString());
        $doc('National ID', now()->addDays(10)->toDateString());
        $doc('Driving License', now()->addDays(90)->toDateString());
        $doc('Degree', null);

        $this->actingAsEmployee();

        $this->get(route('portal.home'))->assertOk()
            ->assertSee(__('portal.home.documents_alert'))
            ->assertSee('Old Passport')->assertSee('National ID')
            ->assertDontSee('Driving License')->assertDontSee('Degree');
    }

    /**
     * طلب من البوابة بيوصل إشعاره للمشرف حتى لو نفس المتصفح داخل لوحة
     * الإدارة كمان. قبل كده المشرف كان بيتستبعد كأنه هو اللي قدّم الطلب.
     */
    public function test_portal_request_notifies_admin_logged_in_same_browser(): void
    {
        $admin = $this->actingAsAdmin(['permission_show', 'leave_show', 'advance_show']);
        $this->actingAsEmployee();

        $this->post(route('portal.permissions.store'), [
            'permission_date' => '2026-12-01', 'start_time' => '10:00', 'end_time' => '11:00',
        ])->assertSessionHasNoErrors();

        $notification = \App\Models\AdminNotification::where('admin_id', $admin->id)
            ->where('type', \App\Enums\NotificationType::PermissionRequested->value)
            ->sole();

        // صاحب الحدث الموظف، مش المشرف اللي في نفس الجلسة
        $this->assertNull($notification->causer_id);

        // وبيظهر في كارت "يحتاج إجراء" في رئيسية الإدارة
        $this->actingAs(Admin::create([
            'name' => 'Boss', 'email' => 'boss@test.com', 'password' => bcrypt('password'),
            'main' => true, 'role_id' => Role::create(['name' => 'All', 'roles' => all_roles()])->id,
        ]), 'admin');
        $this->get(route('admin.home'))->assertOk()
            ->assertSee(__('dashboard.pending.permissions'))
            ->assertSee(route('admin.permissions.index', ['status' => 'pending']), false);
    }

    /** مشرف فرع تاني في نفس المتصفح مايفلترش بيانات الموظف في البوابة. */
    public function test_branch_admin_in_same_browser_does_not_filter_portal_data(): void
    {
        $other = Branch::create(['name' => 'Other', 'code' => 'OT', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $otherEmp = Employee::create([
            'branch_id' => $other->id, 'job_title_id' => $this->jobTitle->id, 'name' => 'Other Emp',
            'employee_code' => 'OT-0001', 'phone' => '+201000000077', 'status' => 'active', 'basic_salary' => 5000, 'hire_date' => '2024-01-01',
        ]);
        $this->actingAsAdmin(['permission_show'], $otherEmp->id);   // مقيّد بفرع Other

        $leave = EmployeeLeave::create([
            'employee_id' => $this->employee->id, 'leave_type' => 'annual',
            'start_date' => '2026-12-01', 'end_date' => '2026-12-01', 'days_count' => 1, 'status' => 'pending',
        ]);

        $this->actingAsEmployee();
        \App\Support\BranchScopeResolver::forget();

        $this->assertSame([$leave->id], $this->rows(route('portal.leaves.index'))->pluck('id')->all());
    }

    /** مستنداتي: بتاعتي بس، مع حالة الانتهاء ورابط الملف. */
    public function test_documents_page_lists_own_documents_with_expiry_state(): void
    {
        $this->employee->documents()->create([
            'document_type' => 'other', 'title' => 'My Passport', 'file_path' => '/files/passport.pdf',
            'expiry_date' => now()->subDay()->toDateString(),
        ]);
        $this->colleague->documents()->create([
            'document_type' => 'other', 'title' => 'Colleague ID', 'file_path' => '/files/x.pdf',
        ]);

        $this->actingAsEmployee();

        $this->get(route('portal.documents.index'))->assertOk()
            ->assertSee('My Passport')
            ->assertSee('/files/passport.pdf', false)
            ->assertSee(__('portal.documents.expired'))
            ->assertDontSee('Colleague ID');

        // بياناتي فيها اختصار للصفحتين
        $this->get(route('portal.profile'))->assertOk()
            ->assertSee(route('portal.documents.index'), false)
            ->assertSee(route('portal.salary.index'), false);
    }

    /** زرار الخروج من البوابة مايسألش تأكيد. */
    public function test_portal_logout_form_skips_confirmation(): void
    {
        $this->actingAsEmployee();

        $this->get(route('portal.home'))->assertOk()
            ->assertSee('action="' . route('portal.logout') . '" method="POST" class="m-0" data-no-confirm', false);
    }

    /** الصفحة الرئيسية بتعرض اختيار اللوحتين. */
    public function test_landing_page_links_to_both_panels(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(route('portal.login'), false)
            ->assertSee(route('admin.login'), false);
    }

    /** الخروج من لوحة مايخرجش من التانية لو الاتنين في نفس المتصفح. */
    public function test_logging_out_of_one_panel_keeps_the_other(): void
    {
        $this->employee->forceFill(['must_change_password' => false])->save();
        $this->actingAsAdmin(['employee_show']);

        $this->login()->assertRedirect(route('portal.home'));
        $this->assertTrue(auth('employee')->check());

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->app['auth']->forgetGuards();

        $this->get(route('portal.home'))->assertOk();
        $this->assertGuest('admin');
    }

    /** من غير صلاحية إدارة البوابة، حقول التفعيل بتتجاهل حتى لو اتبعتت يدوياً. */
    public function test_portal_fields_are_ignored_without_permission(): void
    {
        $this->actingAsAdmin(['employee_create']);

        $this->post(route('admin.employees.store'), [
            'name' => 'Sneaky', 'phone' => '+201000000066', 'branch_id' => $this->branch->id,
            'job_title_id' => $this->jobTitle->id, 'hire_date' => '2026-01-01', 'status' => 'active',
            'portal_password' => 'Welcome123', 'portal_password_confirmation' => 'Welcome123',
        ])->assertSessionHasNoErrors();

        $this->assertFalse(Employee::where('phone', '+201000000066')->sole()->portal_enabled);
    }

    /** رقم الهاتف اسم الدخول، فمايتكررش بين موظفين. */
    public function test_phone_must_be_unique_across_employees(): void
    {
        $this->actingAsAdmin(['employee_create', 'employee_update']);

        $this->post(route('admin.employees.store'), [
            'name' => 'Dup', 'phone' => '+201000000001', 'branch_id' => $this->branch->id,
            'job_title_id' => $this->jobTitle->id, 'hire_date' => '2026-01-01', 'status' => 'active',
        ])->assertSessionHasErrors('phone');
    }
}
