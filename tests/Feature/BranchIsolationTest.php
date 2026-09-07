<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLeave;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * اختبار عزل البيانات بين الفروع.
 *
 * الـ Global Scope في ScopedToBranch المفروض يقفل الطريقين:
 *  - القوائم (index) مايظهرش فيها سجلات فروع تانية.
 *  - مسارات التفاصيل/التعديل/الحذف ترجع 404 لو الـ id بتاع فرع تاني،
 *    عشان مدير الفرع مايقدرش يوصل لسجل بتغيير الرقم في الرابط (IDOR).
 */
class BranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $branchAdmin;
    protected Branch $branchA;
    protected Branch $branchB;

    protected Sale $saleOther;
    protected Expense $expenseOther;
    protected Employee $employeeOther;
    protected EmployeeLeave $leaveOther;
    protected EmployeeAdvance $advanceOther;

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
        $role = Role::create(['name' => 'Branch Manager', 'roles' => all_roles()]);

        // موظف في الفرع A، والمشرف مربوط بيه (مش main)
        $employeeA = Employee::create([
            'branch_id' => $this->branchA->id, 'job_title_id' => $jobTitle->id,
            'name' => 'Manager A', 'employee_code' => 'EMP-A1',
            'phone' => '01000000001', 'status' => 'active',
            'basic_salary' => 5000, 'hire_date' => '2024-01-01',
        ]);

        $this->branchAdmin = Admin::create([
            'name' => 'Branch A Admin', 'email' => 'a@test.com',
            'password' => bcrypt('password'), 'main' => false,
            'role_id' => $role->id, 'employee_id' => $employeeA->id,
        ]);

        // ---- سجلات كلها في الفرع B: المفروض المشرف ده مايشوفهاش ----

        $this->employeeOther = Employee::create([
            'branch_id' => $this->branchB->id, 'job_title_id' => $jobTitle->id,
            'name' => 'Employee B', 'employee_code' => 'EMP-B1',
            'phone' => '01000000002', 'status' => 'active',
            'basic_salary' => 4000, 'hire_date' => '2024-01-01',
            'annual_leave_entitlement' => 21.00,
        ]);

        $this->saleOther = Sale::create([
            'invoice_number' => 'INV-B-0001', 'sale_type' => 'direct_sale',
            'branch_id' => $this->branchB->id, 'sale_date' => '2026-09-01',
            'subtotal' => 1000, 'total_amount' => 1000, 'currency' => 'OMR',
            'paid_amount' => 0, 'due_amount' => 1000,
            'payment_status' => 'unpaid', 'payment_method' => 'cash',
        ]);

        $category = ExpenseCategory::create(['name' => 'Office', 'is_active' => true]);

        $this->expenseOther = Expense::create([
            'title' => 'Expense in B', 'expense_category_id' => $category->id,
            'branch_id' => $this->branchB->id, 'amount' => 250,
            'currency' => 'OMR', 'expense_date' => '2026-09-01',
            'payment_method' => 'cash',
        ]);

        $this->leaveOther = EmployeeLeave::create([
            'employee_id' => $this->employeeOther->id, 'leave_type' => 'annual',
            'start_date' => '2026-09-10', 'end_date' => '2026-09-12',
            'days_count' => 3, 'status' => 'pending',
        ]);

        $this->advanceOther = EmployeeAdvance::create([
            'employee_id' => $this->employeeOther->id,
            'amount' => 1000, 'installments_count' => 2,
            'monthly_installment' => 500, 'paid_amount' => 0,
            'waived_amount' => 0, 'remaining_amount' => 1000,
            'advance_date' => '2026-09-01', 'start_date' => '2026-10-01',
            'status' => 'approved',
        ]);

        $this->actingAs($this->branchAdmin, 'admin');
    }

    /** كل القوائم لازم تخفي سجلات الفروع التانية. */
    public function test_listings_hide_other_branch_records(): void
    {
        $this->assertNotContains($this->saleOther->id, Sale::pluck('id')->all(), 'قائمة المبيعات');
        $this->assertNotContains($this->expenseOther->id, Expense::pluck('id')->all(), 'قائمة المصروفات');
        $this->assertNotContains($this->employeeOther->id, Employee::pluck('id')->all(), 'قائمة الموظفين');
        $this->assertNotContains($this->leaveOther->id, EmployeeLeave::pluck('id')->all(), 'قائمة الإجازات');
        $this->assertNotContains($this->advanceOther->id, EmployeeAdvance::pluck('id')->all(), 'قائمة السلف');
    }

    /** find() على سجل فرع تاني لازم يرجع null. */
    public function test_direct_lookup_of_other_branch_records_returns_null(): void
    {
        $this->assertNull(Sale::find($this->saleOther->id), 'فاتورة');
        $this->assertNull(Expense::find($this->expenseOther->id), 'مصروف');
        $this->assertNull(Employee::find($this->employeeOther->id), 'موظف');
        $this->assertNull(EmployeeLeave::find($this->leaveOther->id), 'إجازة');
        $this->assertNull(EmployeeAdvance::find($this->advanceOther->id), 'سلفة');
    }

    /** مسارات العرض والتعديل لسجلات فرع تاني لازم ترفض. */
    public function test_view_and_edit_routes_reject_other_branch_records(): void
    {
        $blocked = [403, 404];

        $this->assertContains($this->get(route('admin.sales.show', $this->saleOther->id))->getStatusCode(), $blocked, 'عرض فاتورة');
        $this->assertContains($this->get(route('admin.sales.edit', $this->saleOther->id))->getStatusCode(), $blocked, 'تعديل فاتورة');
        $this->assertContains($this->get(route('admin.expenses.edit', $this->expenseOther->id))->getStatusCode(), $blocked, 'تعديل مصروف');
        $this->assertContains($this->get(route('admin.employees.show', $this->employeeOther->id))->getStatusCode(), $blocked, 'عرض موظف');
        $this->assertContains($this->get(route('admin.employees.edit', $this->employeeOther->id))->getStatusCode(), $blocked, 'تعديل موظف');
    }

    /** الحذف لسجلات فرع تاني لازم مايتنفذش. */
    public function test_delete_routes_reject_other_branch_records(): void
    {
        $this->delete(route('admin.sales.destroy', $this->saleOther->id));
        $this->assertNotSoftDeleted('sales', ['id' => $this->saleOther->id]);

        $this->delete(route('admin.expenses.destroy', $this->expenseOther->id));
        $this->assertNotSoftDeleted('expenses', ['id' => $this->expenseOther->id]);

        $this->delete(route('admin.leaves.destroy', $this->leaveOther->id));
        $this->assertNotSoftDeleted('employee_leaves', ['id' => $this->leaveOther->id]);
    }

    /** العمليات المالية على سجلات فرع تاني لازم ترفض. */
    public function test_financial_actions_reject_other_branch_records(): void
    {
        // اعتماد إجازة موظف في فرع تاني
        $this->post(route('admin.leaves.approve', $this->leaveOther->id));
        $this->assertEquals(
            'pending',
            $this->leaveOther->fresh()->status,
            'اتعمدت إجازة موظف من فرع تاني'
        );

        // تسجيل سداد على سلفة موظف في فرع تاني
        $this->post(route('admin.advances.record-payment', $this->advanceOther->id), [
            'amount' => 100, 'repayment_date' => '2026-10-01',
        ]);
        $this->assertEquals(
            0.00,
            (float) $this->advanceOther->fresh()->paid_amount,
            'اتسجل سداد على سلفة موظف من فرع تاني'
        );
    }

    /** المشرف الرئيسي (main) بيشوف كل الفروع. */
    public function test_main_admin_sees_all_branches(): void
    {
        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $mainAdmin = Admin::create([
            'name' => 'Root', 'email' => 'root@test.com',
            'password' => bcrypt('password'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->actingAs($mainAdmin, 'admin');

        $this->assertNotNull(Sale::find($this->saleOther->id), 'المشرف الرئيسي مبيشوفش فاتورة الفرع B');
        $this->assertNotNull(Expense::find($this->expenseOther->id), 'المشرف الرئيسي مبيشوفش مصروف الفرع B');
        $this->assertNotNull(Employee::find($this->employeeOther->id), 'المشرف الرئيسي مبيشوفش موظف الفرع B');
    }
}
