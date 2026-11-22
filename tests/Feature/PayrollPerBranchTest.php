<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Payroll;
use App\Models\Role;
use App\Services\HR\PayrollGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * إصدار كشف مستقل لكل فرع.
 *
 * "جميع الفروع" كان بيعمل كشف واحد فيه كل الموظفين وبـ branch_id
 * فاضي — فالفلترة على فرع مابترجعش الكشف، والتقارير مابتعرفش
 * توزّع المبالغ، والصرف بيتم من خزينة واحدة رغم إن كل فرع ليه
 * عملته وميزانيته.
 */
class PayrollPerBranchTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected JobTitle $job;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@pay.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->job = JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        $this->actingAs($this->admin, 'admin');
    }

    /** فرع ومعاه موظفين نشطين. */
    private function branchWith(string $name, string $code, int $employees, string $currency = 'EGP'): Branch
    {
        $branch = Branch::create([
            'name' => $name, 'code' => $code, 'country' => 'Egypt',
            'currency' => $currency, 'is_active' => true,
        ]);

        for ($i = 0; $i < $employees; $i++) {
            Employee::create([
                'branch_id' => $branch->id, 'job_title_id' => $this->job->id,
                'name' => $name . $i, 'employee_code' => $code . '-' . $i,
                'phone' => '+20100' . $code . $i, 'status' => 'active', 'basic_salary' => 3000,
                'hire_date' => '2024-01-01',
            ]);
        }

        return $branch;
    }

    /** كل فرع بياخد كشفه المستقل. */
    public function test_each_branch_gets_its_own_payroll(): void
    {
        $a = $this->branchWith('فرع أ', 'PB-A', 3);
        $b = $this->branchWith('فرع ب', 'PB-B', 2, 'SAR');

        $result = app(PayrollGenerator::class)->generateForAllBranches(2025, 4);

        // السيدرز فيها فروع كمان، فبنتأكد من فرعينا مش العدد الكلي
        $createdBranches = collect($result['created'])->pluck('branch_id');

        $this->assertContains($a->id, $createdBranches->all(), 'الفرع الأول مالوش كشف');
        $this->assertContains($b->id, $createdBranches->all(), 'الفرع التاني مالوش كشف');

        foreach ([$a->id => 3, $b->id => 2] as $branchId => $expected) {
            $payroll = Payroll::withoutGlobalScopes()
                ->where('year', 2025)->where('month', 4)
                ->where('branch_id', $branchId)
                ->first();

            $this->assertNotNull($payroll, "الفرع {$branchId} مالوش كشف");
            $this->assertEquals($expected, $payroll->items()->count(), 'عدد الموظفين في الكشف غلط');
        }
    }

    /** مفيش كشف مجمّع من غير فرع. */
    public function test_no_lump_payroll_is_created(): void
    {
        $this->branchWith('فرع ج', 'PB-C', 2);

        app(PayrollGenerator::class)->generateForAllBranches(2025, 5);

        $this->assertEquals(
            0,
            Payroll::withoutGlobalScopes()->where('year', 2025)->where('month', 5)->whereNull('branch_id')->count(),
            'لسه بيتعمل كشف مجمّع من غير فرع'
        );
    }

    /** موظفو الفرع مايظهروش في كشف فرع تاني. */
    public function test_employees_do_not_leak_across_branches(): void
    {
        $a = $this->branchWith('فرع د', 'PB-D', 2);
        $b = $this->branchWith('فرع هـ', 'PB-E', 2);

        app(PayrollGenerator::class)->generateForAllBranches(2025, 6);

        $payrollA = Payroll::withoutGlobalScopes()
            ->where('year', 2025)->where('month', 6)->where('branch_id', $a->id)->first();

        $employeeIds = $payrollA->items()->pluck('employee_id');

        $foreign = Employee::whereIn('id', $employeeIds)
            ->where('branch_id', '!=', $a->id)
            ->count();

        $this->assertEquals(0, $foreign, 'كشف الفرع فيه موظفين من فرع تاني');
    }

    /** الفرع اللي مالوش موظفين نشطين مابياخدش كشف. */
    public function test_a_branch_without_active_employees_is_skipped(): void
    {
        $this->branchWith('فرع و', 'PB-F', 1);

        Branch::create([
            'name' => 'فرع فاضي', 'code' => 'PB-EMPTY', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $result = app(PayrollGenerator::class)->generateForAllBranches(2025, 7);

        foreach ($result['created'] as $payroll) {
            $this->assertGreaterThan(0, $payroll->items()->count(), 'اتعمل كشف فاضي');
        }
    }

    /** التشغيل تاني مابيكررش الكشوف. */
    public function test_running_twice_does_not_duplicate(): void
    {
        $this->branchWith('فرع ز', 'PB-G', 2);

        $first  = app(PayrollGenerator::class)->generateForAllBranches(2025, 8);
        $second = app(PayrollGenerator::class)->generateForAllBranches(2025, 8);

        $this->assertNotEmpty($first['created']);
        $this->assertEmpty($second['created'], 'اتعمل كشف مكرر');
        $this->assertNotEmpty($second['skipped'], 'الفروع المتخطّاة مش مسجّلة');
    }

    /** الإصدار من الشاشة بيعمل كشف لكل فرع. */
    public function test_creating_from_the_screen_generates_per_branch(): void
    {
        $a = $this->branchWith('فرع ح', 'PB-H', 2);
        $b = $this->branchWith('فرع ط', 'PB-I', 1);

        $this->post(route('admin.payrolls.store'), [
            'year' => 2025, 'month' => 9, 'branch_id' => null,
        ])->assertRedirect();

        foreach ([$a->id, $b->id] as $branchId) {
            $this->assertDatabaseHas('payrolls', [
                'year' => 2025, 'month' => 9, 'branch_id' => $branchId,
            ]);
        }

        $this->assertDatabaseMissing('payrolls', [
            'year' => 2025, 'month' => 9, 'branch_id' => null,
        ]);
    }

    /**
     * السيدر نفسه لازم يمشي على نفس القاعدة.
     *
     * كان بيعمل كشف واحد لكل شهر بـ branch_id فاضي، فالبيانات
     * التجريبية بتخالف سلوك الشاشة وبتخفي مشاكل الفلترة.
     */
    public function test_the_seeder_creates_payrolls_per_branch(): void
    {
        $this->seed(\Database\Seeders\ComprehensiveDummySeeder::class);

        $payrolls = Payroll::withoutGlobalScopes()->get();

        $this->assertNotEmpty($payrolls, 'السيدر مانشأش كشوف أصلاً');

        $this->assertEquals(
            0,
            $payrolls->whereNull('branch_id')->count(),
            'السيدر لسه بيعمل كشف مجمّع من غير فرع'
        );

        // وكل كشف موظفينه من فرعه هو بس
        foreach ($payrolls as $payroll) {
            $foreign = Employee::withoutGlobalScopes()
                ->whereIn('id', $payroll->items()->pluck('employee_id'))
                ->where('branch_id', '!=', $payroll->branch_id)
                ->count();

            $this->assertEquals(0, $foreign, "الكشف #{$payroll->id} فيه موظفين من فرع تاني");
        }
    }

    /** واختيار فرع بعينه بيعمل كشف واحد بس. */
    public function test_selecting_one_branch_creates_a_single_payroll(): void
    {
        $a = $this->branchWith('فرع ي', 'PB-J', 2);
        $this->branchWith('فرع ك', 'PB-K', 2);

        $this->post(route('admin.payrolls.store'), [
            'year' => 2025, 'month' => 10, 'branch_id' => $a->id,
        ])->assertRedirect();

        $this->assertEquals(
            1,
            Payroll::withoutGlobalScopes()->where('year', 2025)->where('month', 10)->count(),
            'المفروض كشف واحد بس للفرع المختار'
        );
    }
}
