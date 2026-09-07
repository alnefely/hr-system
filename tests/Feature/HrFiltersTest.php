<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\EmployeePenalty;
use App\Models\JobTitle;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * فلاتر شاشتي الإجازات والجزاءات.
 *
 * كانت الحقول اللي قيمها محددة (نوع الإجازة، الحالة، نوع الجزاء)
 * بحثها كتابة حرة، والتواريخ كمان — يعني المستخدم لازم يعرف
 * القيمة المخزّنة بالظبط عشان يلاقي حاجة.
 */
class HrFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Branch $branch;
    protected Employee $employee;
    protected Employee $other;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@hrf.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'HRF', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $job = JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        $this->employee = Employee::create([
            'branch_id' => $this->branch->id, 'job_title_id' => $job->id,
            'name' => 'أحمد', 'employee_code' => 'HRF-1', 'phone' => '0100',
            'status' => 'active', 'basic_salary' => 3000, 'hire_date' => now()->toDateString(),
        ]);

        $this->other = Employee::create([
            'branch_id' => $this->branch->id, 'job_title_id' => $job->id,
            'name' => 'سعيد', 'employee_code' => 'HRF-2', 'phone' => '0101',
            'status' => 'active', 'basic_salary' => 3000, 'hire_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    /** الكنترولر بيرجّع JSON بس مع هيدر الـ ajax. */
    private function rows(array $params): array
    {
        return $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.leaves.index', $params))
            ->assertOk()
            ->json('data');
    }

    private function makeLeave(Employee $employee, string $type, string $status): EmployeeLeave
    {
        return EmployeeLeave::create([
            'employee_id' => $employee->id,
            'branch_id'   => $this->branch->id,
            'leave_type'  => $type,
            'start_date'  => now()->toDateString(),
            'end_date'    => now()->addDay()->toDateString(),
            'days_count'  => 2,
            'status'      => $status,
            'reason'      => 'سبب',
        ]);
    }

    // =====================================================
    //  الإجازات
    // =====================================================

    /** شريط فلترة الموظف ظاهر فوق زي شاشة الأذونات. */
    public function test_leaves_screen_has_an_employee_filter(): void
    {
        $html = $this->get(route('admin.leaves.index'))->assertOk()->getContent();

        $this->assertStringContainsString('name="employee_id"', $html, 'فلتر الموظف مش موجود');
        $this->assertStringContainsString($this->employee->name, $html);
        $this->assertStringContainsString('name="leave_type"', $html, 'فلتر النوع مش موجود');
        $this->assertStringContainsString('name="status"', $html, 'فلتر الحالة مش موجود');
    }

    /**
     * إعدادات الجدول بتوصل للواجهة بنوع بحث مناسب: قائمة للنوع
     * والحالة، وحقل تاريخ للتواريخ.
     */
    public function test_leaves_table_uses_select_and_date_filters(): void
    {
        $html = $this->get(route('admin.leaves.index'))->assertOk()->getContent();

        // الإعدادات بتتحوّل لـ JSON جوه سكربت الجدول
        $this->assertStringContainsString('"searchType":"select"', $html, 'مفيش عمود بحثه قائمة');
        $this->assertStringContainsString('"searchType":"date"', $html, 'التواريخ لسه بحثها كتابة حرة');

        // وقيم النوع الأربعة موجودة كخيارات
        foreach (\App\Enums\LeaveType::cases() as $type) {
            $this->assertStringContainsString($type->label(), $html, "النوع {$type->value} مش ظاهر");
        }
    }

    /** نفس الحاجة في شاشة الجزاءات. */
    public function test_penalties_table_uses_select_and_date_filters(): void
    {
        $html = $this->get(route('admin.penalties.index'))->assertOk()->getContent();

        $this->assertStringContainsString('"searchType":"select"', $html, 'نوع الجزاء لسه كتابة حرة');
        $this->assertStringContainsString('"searchType":"date"', $html, 'تاريخ الجزاء لسه كتابة حرة');
    }

    /** فلتر الموظف بيشتغل فعلاً على البيانات. */
    public function test_leaves_are_filtered_by_employee(): void
    {
        $this->makeLeave($this->employee, 'annual', 'pending');
        $this->makeLeave($this->other, 'sick', 'pending');

        $rows = $this->rows(['employee_id' => $this->employee->id]);

        $this->assertNotEmpty($rows);

        foreach ($rows as $row) {
            $this->assertSame($this->employee->name, $row['employee_name'], 'الفلتر رجّع موظف تاني');
        }
    }

    /** فلتر نوع الإجازة بيشتغل. */
    public function test_leaves_are_filtered_by_type(): void
    {
        $this->makeLeave($this->employee, 'annual', 'pending');
        $this->makeLeave($this->other, 'sick', 'pending');

        // السيدرز فيها إجازات كمان، فبنتأكد من المحتوى مش العدد
        $ids = collect($this->rows(['leave_type' => 'sick']))->pluck('id');

        $this->assertTrue(
            EmployeeLeave::whereIn('id', $ids)->where('leave_type', '!=', 'sick')->doesntExist(),
            'الفلتر رجّع أنواع تانية'
        );

        $sick = EmployeeLeave::where('employee_id', $this->other->id)->first();
        $this->assertContains($sick->id, $ids->all(), 'الإجازة المطابقة مش راجعة');
    }

    /** فلتر الحالة بيشتغل. */
    public function test_leaves_are_filtered_by_status(): void
    {
        $this->makeLeave($this->employee, 'annual', 'pending');
        $approved = $this->makeLeave($this->other, 'annual', 'approved');

        $ids = collect($this->rows(['status' => 'approved']))->pluck('id');

        $this->assertTrue(
            EmployeeLeave::whereIn('id', $ids)->where('status', '!=', 'approved')->doesntExist(),
            'الفلتر رجّع حالات تانية'
        );

        $this->assertContains($approved->id, $ids->all(), 'الإجازة المعتمدة مش راجعة');
    }

    // =====================================================
    //  الجزاءات
    // =====================================================

    /** شاشة الجزاءات بتفتح ونوع الجزاء ليه قائمة قيم. */
    public function test_penalties_screen_renders_with_type_options(): void
    {
        $html = $this->get(route('admin.penalties.index'))->assertOk()->getContent();

        // الأنواع الأربعة لازم تكون متاحة كخيارات
        foreach (__('penalty.type_enums') as $label) {
            $this->assertStringContainsString($label, $html, "نوع الجزاء «{$label}» مش ظاهر");
        }
    }

    /** أنواع الجزاءات كلها مترجمة. */
    public function test_penalty_types_are_translated(): void
    {
        $types = __('penalty.type_enums');

        $this->assertIsArray($types);
        $this->assertNotEmpty($types);

        foreach ($types as $key => $label) {
            $this->assertIsString($label);
            $this->assertStringNotContainsString('penalty.type_enums', $label, "النوع {$key} مالوش ترجمة");
        }
    }
}
