<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\ExpenseCategory;
use App\Models\JobTitle;
use App\Models\PettyCashBalance;
use App\Models\Role;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * عزل الفروع في الكتابة.
 *
 * BranchIsolationTest بيغطي القراءة والتعديل والحذف على سجلات موجودة.
 * هنا بنغطي الإنشاء: exists:branches,id و exists:employees,id كانوا
 * بيعدّوا أي فرع أو موظف، فمشرف الفرع كان يقدر يسجّل فاتورة أو مصروف
 * على فرع تاني، أو إجازة وسلفة وجزاء لموظف في فرع تاني.
 */
class BranchWriteIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $branchAdmin;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Employee $employeeA;
    protected Employee $employeeOther;
    protected Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchA = Branch::create([
            'name' => 'Branch A', 'code' => 'BR-A', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->branchB = Branch::create([
            'name' => 'Branch B', 'code' => 'BR-B', 'country' => 'Oman',
            'currency' => 'OMR', 'is_active' => true,
        ]);

        $jobTitle = JobTitle::create(['name' => 'Manager', 'is_active' => true]);
        $this->role = Role::create(['name' => 'Branch Manager', 'roles' => all_roles()]);

        $this->employeeA = Employee::create([
            'branch_id' => $this->branchA->id, 'job_title_id' => $jobTitle->id,
            'name' => 'Manager A', 'employee_code' => 'EMP-A1',
            'phone' => '01000000001', 'status' => 'active',
            'basic_salary' => 5000, 'hire_date' => '2024-01-01',
        ]);

        $this->employeeOther = Employee::create([
            'branch_id' => $this->branchB->id, 'job_title_id' => $jobTitle->id,
            'name' => 'Employee B', 'employee_code' => 'EMP-B1',
            'phone' => '01000000002', 'status' => 'active',
            'basic_salary' => 4000, 'hire_date' => '2024-01-01',
        ]);

        $this->branchAdmin = Admin::create([
            'name' => 'Branch A Admin', 'email' => 'a@test.com',
            'password' => bcrypt('password'), 'main' => false,
            'role_id' => $this->role->id, 'employee_id' => $this->employeeA->id,
        ]);

        $this->actingAs($this->branchAdmin, 'admin');
    }

    /** الفواتير والمصروفات والموظفين والمسيرات على فرع تاني لازم ترفض. */
    public function test_records_cannot_be_created_on_another_branch(): void
    {
        $category = ExpenseCategory::create(['name' => 'Office', 'is_active' => true]);

        $this->post(route('admin.sales.store'), [
            'branch_id' => $this->branchB->id, 'sale_date' => '2026-09-01',
            'subtotal' => 100, 'total_amount' => 100,
        ])->assertSessionHasErrors('branch_id');

        $this->post(route('admin.expenses.store'), [
            'title' => 'X', 'expense_category_id' => $category->id,
            'branch_id' => $this->branchB->id, 'amount' => 50,
            'expense_date' => now()->toDateString(), 'payment_method' => 'cash',
        ])->assertSessionHasErrors('branch_id');

        $this->post(route('admin.employees.store'), [
            'branch_id' => $this->branchB->id, 'name' => 'Hijack',
        ])->assertSessionHasErrors('branch_id');

        $this->post(route('admin.payrolls.store'), [
            'year' => 2026, 'month' => 9, 'branch_id' => $this->branchB->id,
        ])->assertSessionHasErrors('branch_id');

        $this->assertDatabaseMissing('sales', ['branch_id' => $this->branchB->id]);
        $this->assertDatabaseMissing('expenses', ['branch_id' => $this->branchB->id]);
        $this->assertDatabaseMissing('payrolls', ['branch_id' => $this->branchB->id]);
    }

    /** نقل موظف من فرع المشرف لفرع تاني لازم يترفض. */
    public function test_employee_cannot_be_moved_to_another_branch(): void
    {
        $this->put(route('admin.employees.update', $this->employeeA->id), [
            'branch_id' => $this->branchB->id,
        ])->assertSessionHasErrors('branch_id');

        $this->assertEquals($this->branchA->id, $this->employeeA->fresh()->branch_id);
    }

    /** طلبات الموارد البشرية لموظف في فرع تاني لازم ترفض. */
    public function test_hr_requests_cannot_target_employee_of_another_branch(): void
    {
        $other = $this->employeeOther->id;

        $this->post(route('admin.leaves.store'), [
            'employee_id' => $other, 'leave_type' => 'annual',
            'start_date' => '2026-10-01', 'end_date' => '2026-10-02', 'days_count' => 2,
        ])->assertSessionHasErrors('employee_id');

        $this->post(route('admin.advances.store'), [
            'employee_id' => $other, 'amount' => 1000,
        ])->assertSessionHasErrors('employee_id');

        $this->post(route('admin.penalties.store'), [
            'employee_id' => $other, 'penalty_type' => 'warning',
        ])->assertSessionHasErrors('employee_id');

        $this->post(route('admin.rewards.store'), [
            'employee_id' => $other, 'amount' => 100,
        ])->assertSessionHasErrors('employee_id');

        $this->post(route('admin.permissions.store'), [
            'employee_id' => $other,
        ])->assertSessionHasErrors('employee_id');

        $this->assertDatabaseMissing('employee_leaves', ['employee_id' => $other]);
        $this->assertDatabaseMissing('employee_advances', ['employee_id' => $other]);
        $this->assertDatabaseMissing('employee_penalties', ['employee_id' => $other]);
        $this->assertDatabaseMissing('employee_rewards', ['employee_id' => $other]);
        $this->assertDatabaseMissing('employee_permissions', ['employee_id' => $other]);
    }

    /** موظف الفرع نفسه لازم يعدّي — القاعدة مابتقفلش الاستخدام الطبيعي. */
    public function test_own_branch_and_employee_pass_the_rules(): void
    {
        $this->post(route('admin.leaves.store'), [
            'employee_id' => $this->employeeA->id,
        ])->assertSessionDoesntHaveErrors('employee_id');

        $this->post(route('admin.sales.store'), [
            'branch_id' => $this->branchA->id,
        ])->assertSessionDoesntHaveErrors('branch_id');
    }

    /** رابط من غير فرع (= كل الفروع) مش من حق مشرف الفرع. */
    public function test_scoped_admin_must_pick_own_branch_for_links(): void
    {
        $this->post(route('admin.links.store'), [
            'original_url' => 'https://example.com',
        ])->assertSessionHasErrors('branch_id');
    }

    /** رصيد إجازات موظف في فرع تاني مايتعدّلش بتغيير الـ id في الرابط. */
    public function test_leave_balance_of_another_branch_cannot_be_updated(): void
    {
        $balance = EmployeeLeaveBalance::acrossAllBranches()->create([
            'employee_id' => $this->employeeOther->id, 'year' => 2026,
            'annual_balance' => 21, 'casual_balance' => 7, 'sick_balance' => 14,
        ]);

        $this->put(route('admin.leaves.balances.update', $balance->id), [
            'annual_balance' => 99, 'casual_balance' => 99, 'sick_balance' => 99,
        ])->assertNotFound();

        $this->assertEquals(21, (float) $balance->fresh()->annual_balance);
    }

    /** عهدة فرع تاني: لا تتشاف ولا تتعدّل. */
    public function test_petty_cash_of_another_branch_is_isolated(): void
    {
        PettyCashBalance::create([
            'branch_id' => $this->branchB->id, 'year' => 2026, 'month' => 9,
            'allocated_amount' => 5000, 'carried_over_amount' => 0,
        ]);

        $this->assertSame(0, PettyCashBalance::count(), 'مشرف الفرع شايف عهدة فرع تاني');

        $this->getJson(route('admin.expenses.get-custody-balance', [
            'branch_id' => $this->branchB->id, 'year' => 2026, 'month' => 9,
        ]))->assertJson(['allocated_amount' => 0]);

        $this->post(route('admin.expenses.update-balance'), [
            'branch_id' => $this->branchB->id, 'year' => 2026, 'month' => 9,
            'allocated_amount' => 1,
        ])->assertSessionHasErrors('branch_id');

        $this->assertEquals(
            5000,
            (float) PettyCashBalance::acrossAllBranches()->where('branch_id', $this->branchB->id)->value('allocated_amount')
        );
    }

    /** اشتراك مورد في فرع تاني: لا يتشاف ولا يتعدّل ولا يتحذف. */
    public function test_vendor_subscription_of_another_branch_is_isolated(): void
    {
        $vendor = Vendor::create(['code' => 'V-1', 'name' => 'Vendor']);

        $other = VendorSubscription::acrossAllBranches()->create([
            'title' => 'Hosting B', 'vendor_id' => $vendor->id,
            'branch_id' => $this->branchB->id, 'cost' => 100,
            'currency' => 'OMR', 'start_date' => '2026-01-01',
        ]);

        $this->assertNull(VendorSubscription::find($other->id));
        $this->assertContains(
            $this->get(route('admin.vendor-subscriptions.show', $other->id))->getStatusCode(),
            [403, 404]
        );

        $this->delete(route('admin.vendor-subscriptions.destroy', $other->id));
        $this->assertNotSoftDeleted('vendor_subscriptions', ['id' => $other->id]);
    }

    /** المشرف الرئيسي مابيتقيّدش بأي فرع. */
    public function test_main_admin_can_write_to_any_branch(): void
    {
        $main = Admin::create([
            'name' => 'Root', 'email' => 'root@test.com',
            'password' => bcrypt('password'), 'main' => true, 'role_id' => $this->role->id,
        ]);
        $this->actingAs($main, 'admin');

        $this->post(route('admin.sales.store'), [
            'branch_id' => $this->branchB->id,
        ])->assertSessionDoesntHaveErrors('branch_id');

        $this->post(route('admin.leaves.store'), [
            'employee_id' => $this->employeeOther->id,
        ])->assertSessionDoesntHaveErrors('employee_id');

        $this->post(route('admin.links.store'), [
            'original_url' => 'https://example.com',
        ])->assertSessionDoesntHaveErrors('branch_id');
    }
}
