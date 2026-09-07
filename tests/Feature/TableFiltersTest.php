<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLeave;
use App\Models\EmployeePenalty;
use App\Models\JobTitle;
use App\Models\Sale;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * فلاتر الجداول في كل الموديولات.
 *
 * الحقول اللي قيمها محددة (حالة، نوع، طريقة دفع، مفعّل/معطّل)
 * كانت بحثها كتابة حرة — يعني المستخدم لازم يعرف القيمة المخزّنة
 * بالظبط. والتواريخ كمان. بقوا قوائم وحقول تاريخ.
 */
class TableFiltersTest extends TestCase
{
    use RefreshDatabase;

    /** الشاشات اللي فيها جداول واتعدّلت. */
    private const SCREENS = [
        'admin.activity-logs.index',
        'admin.expense-categories.index',
        'admin.expenses.index',
        'admin.advances.index',
        'admin.branches',
        'admin.payrolls.index',
        'admin.permissions.index',
        'admin.rewards.index',
        'admin.leaves.index',
        'admin.penalties.index',
        'admin.sales.index',
        'admin.sales-channels.index',
        'admin.vendor-subscriptions.index',
        'admin.customers.index',
        'admin.customer-sources.index',
        'admin.employees',
        'admin.admins',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $admin = Admin::create([
            'name' => 'Root', 'email' => 'root@tables.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        Branch::create([
            'name' => 'HQ', 'code' => 'TBL', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin');
    }

    /**
     * بيانات بكل قيمة enum ممكنة.
     *
     * من غيرها الاختبار بيعدّي على جدول فاضي — يعني بيقول "تمام"
     * وهو مافحصش حاجة.
     */
    private function seedEnumRows(): void
    {
        $branch   = Branch::first();
        $category = ExpenseCategory::create(['name' => 'Office', 'code' => 'OFF-T', 'is_active' => true]);

        foreach (['cash', 'bank_transfer', 'card'] as $i => $method) {
            Expense::create([
                'title'               => 'مصروف ' . $method,
                'expense_category_id' => $category->id,
                'branch_id'           => $branch->id,
                'amount'              => 100 + $i,
                'currency'            => 'EGP',
                'expense_date'        => now()->toDateString(),
                'payment_method'      => $method,
            ]);
        }

        foreach (['individual', 'company'] as $i => $type) {
            Customer::create([
                'code'          => 'C-ENM-' . $i,
                'name'          => 'عميل ' . $type,
                'customer_type' => $type,
                'is_active'     => true,
            ]);
        }

        foreach (['paid', 'unpaid', 'partially_paid'] as $i => $status) {
            Sale::create([
                'invoice_number' => 'INV-ENM-' . $i,
                'sale_type'      => 'direct_sale',
                'branch_id'      => $branch->id,
                'sale_date'      => now()->toDateString(),
                'subtotal'       => 500,
                'total_amount'   => 500,
                'currency'       => 'EGP',
                'paid_amount'    => $status === 'paid' ? 500 : 0,
                'due_amount'     => $status === 'paid' ? 0 : 500,
                'payment_status' => $status,
                'payment_method' => 'cash',
            ]);
        }

        $job = JobTitle::create(['name' => 'Staff-T', 'is_active' => true]);

        $employee = Employee::create([
            'branch_id'    => $branch->id,
            'job_title_id' => $job->id,
            'name'         => 'موظف الاختبار',
            'employee_code'=> 'EMP-ENM',
            'phone'        => '0100',
            'status'       => 'active',
            'basic_salary' => 3000,
            'hire_date'    => now()->toDateString(),
        ]);

        foreach (['annual', 'casual', 'sick', 'unpaid'] as $type) {
            EmployeeLeave::create([
                'employee_id' => $employee->id,
                'branch_id'   => $branch->id,
                'leave_type'  => $type,
                'start_date'  => now()->toDateString(),
                'end_date'    => now()->addDay()->toDateString(),
                'days_count'  => 2,
                'status'      => 'pending',
                'reason'      => 'اختبار',
            ]);
        }

        foreach (['pending', 'approved', 'rejected', 'paid'] as $i => $status) {
            EmployeeAdvance::create([
                'employee_id'         => $employee->id,
                'amount'              => 1000,
                'installments_count'  => 1,
                'monthly_installment' => 1000,
                'paid_amount'         => 0,
                'waived_amount'       => 0,
                'remaining_amount'    => 1000,
                'advance_date'        => now()->toDateString(),
                'start_date'          => now()->addMonth()->toDateString(),
                'status'              => $status,
            ]);
        }

        foreach (['days_deduction', 'amount_deduction', 'warning'] as $type) {
            EmployeePenalty::create([
                'employee_id'  => $employee->id,
                'branch_id'    => $branch->id,
                'penalty_type' => $type,
                'penalty_date' => now()->toDateString(),
                'reason'       => 'اختبار',
                'status'       => 'pending',
            ]);
        }
    }

    /**
     * كل شاشة بتفتح من غير خطأ.
     *
     * ده بيمسك المتغيّرات غير المعرّفة اللي ممكن تيجي من إضافة
     * searchOptions بيشير لمتغيّر مش موجود في الملف.
     */
    public function test_every_table_screen_renders(): void
    {
        foreach (self::SCREENS as $route) {
            $this->get(route($route))
                ->assertOk("الشاشة {$route} مابتفتحش");
        }
    }

    /** مفيش مفتاح ترجمة خام ظاهر في أي قائمة فلترة. */
    public function test_no_raw_translation_keys_in_filters(): void
    {
        foreach (self::SCREENS as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            $this->assertDoesNotMatchRegularExpression(
                '/"searchOptions":\{[^}]*"[a-z_]+\.(enums|statuses|types|status_enums|type_enums)\./',
                $html,
                "الشاشة {$route}: فيه مفتاح ترجمة خام في قائمة الفلترة"
            );
        }
    }

    /**
     * الشاشات اللي فيها عمود حالة أو نوع لازم يكون بحثه قائمة.
     */
    public function test_status_columns_use_select_filters(): void
    {
        $expected = [
            'admin.advances.index',
            'admin.payrolls.index',
            'admin.permissions.index',
            'admin.rewards.index',
            'admin.leaves.index',
            'admin.sales.index',
            'admin.vendor-subscriptions.index',
            'admin.branches',
            'admin.expense-categories.index',
            'admin.sales-channels.index',
        ];

        foreach ($expected as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            $this->assertStringContainsString(
                '"searchType":"select"',
                $html,
                "الشاشة {$route}: عمود الحالة/النوع لسه بحثه كتابة حرة"
            );
        }
    }

    /** الشاشات اللي فيها تواريخ لازم يكون بحثها حقل تاريخ. */
    public function test_date_columns_use_date_filters(): void
    {
        $expected = [
            'admin.activity-logs.index',
            'admin.expenses.index',
            'admin.advances.index',
            'admin.permissions.index',
            'admin.rewards.index',
            'admin.leaves.index',
            'admin.penalties.index',
            'admin.sales.index',
            'admin.vendor-subscriptions.index',
        ];

        foreach ($expected as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            $this->assertStringContainsString(
                '"searchType":"date"',
                $html,
                "الشاشة {$route}: عمود التاريخ لسه بحثه كتابة حرة"
            );
        }
    }

    /**
     * الجداول ما بتعرضش قيم قاعدة البيانات الخام.
     *
     * طريقة الصرف في المصروفات كانت بتظهر "cash" بدل "نقداً"،
     * لأن الكنترولر مكانش بيضيف عمود مترجم — فـ yajra بيبعت
     * القيمة المخزّنة زي ما هي.
     */
    public function test_tables_never_return_raw_enum_values(): void
    {
        $this->seedEnumRows();

        // الشاشة => [العمود المعروض => القيم الخام الممنوعة فيه]
        //
        // بنحدد العمود بالاسم لأن yajra بيبعت كل أعمدة الموديل في
        // الـ JSON حتى اللي الجدول مابيعرضهاش — وفحصها كان بيدّي
        // إنذارات كاذبة.
        $screens = [
            'admin.expenses.index' => [
                'payment_method' => ['cash', 'bank_transfer', 'card'],
            ],
            'admin.sales.index' => [
                'payment_badge' => ['paid', 'unpaid', 'partially_paid'],
            ],
            'admin.leaves.index' => [
                'leave_type_badge' => ['annual', 'casual', 'sick', 'unpaid'],
                'status_badge'     => ['pending', 'approved', 'rejected'],
            ],
            'admin.advances.index' => [
                'status_badge' => ['pending', 'approved', 'rejected', 'paid'],
            ],
            'admin.penalties.index' => [
                'type_badge' => ['days_deduction', 'amount_deduction', 'warning'],
            ],
        ];

        foreach ($screens as $route => $columns) {
            $rows = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->getJson(route($route))
                ->assertOk()
                ->json('data');

            $this->assertNotEmpty($rows, "الشاشة {$route}: مفيش صفوف — الاختبار مابيفحصش حاجة");

            foreach ($rows as $row) {
                foreach ($columns as $column => $rawValues) {
                    $value = $row[$column] ?? null;

                    if (! is_string($value)) {
                        continue;
                    }

                    // البادجات فيها HTML، فبنشيله ونفحص النص المعروض
                    $text = trim(strip_tags($value));

                    $this->assertNotContains(
                        $text,
                        $rawValues,
                        "الشاشة {$route}: العمود «{$column}» بيعرض قيمة خام «{$text}»"
                    );
                }
            }
        }
    }

    /** وسلة المحذوفات كمان. */
    public function test_trash_tables_never_return_raw_enum_values(): void
    {
        $this->seedEnumRows();

        Customer::where('code', 'like', 'C-ENM-%')->get()->each->delete();

        $rows = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.customers.trash'))
            ->assertOk()
            ->json('data');

        foreach ($rows as $row) {
            $this->assertNotContains(
                $row['customer_type'] ?? '',
                ['individual', 'company'],
                'سلة محذوفات العملاء: نوع العميل خام'
            );
        }
    }

    /** طلب DataTables كامل بترتيب على عمود معيّن. */
    private function sortBy(string $route, array $columns, int $orderIndex): array
    {
        $payload = [];

        foreach ($columns as $data => $name) {
            $payload[] = [
                'data' => $data,
                'name' => $name,
                'searchable' => 'true',
                'orderable'  => 'true',
                'search' => ['value' => '', 'regex' => 'false'],
            ];
        }

        return $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route($route, [
                'draw'    => 1,
                'columns' => $payload,
                'order'   => [['column' => $orderIndex, 'dir' => 'desc']],
                'start'   => 0,
                'length'  => 10,
                'search'  => ['value' => '', 'regex' => 'false'],
            ]))
            ->assertOk()
            ->json();
    }

    /**
     * الترتيب بعمود علاقة مابيكسرش الجدول.
     *
     * yajra بيعمل join تلقائي عشان يرتّب بـ employee.name، ولو
     * الاستعلام select * الأعمدة المتشابهة بتتدهس — id بالذات
     * بيرجع فاضي فأزرار الإجراءات بتكسر والصفوف بتختفي.
     */
    public function test_sorting_by_a_relation_column_keeps_the_rows(): void
    {
        $this->seedEnumRows();

        $columns = [
            'id'            => 'id',
            'title'         => 'title',
            'category_name' => 'category.name',
            'branch_name'   => 'branch.name',
            'employee_name' => 'employee.name',
        ];

        // الترتيب بعمود عادي — خط الأساس
        $byId = $this->sortBy('admin.expenses.index', $columns, 0);
        $this->assertNotEmpty($byId['data'], 'مفيش صفوف أصلاً');

        // والترتيب بعمود العلاقة لازم يرجّع نفس العدد
        $byEmployee = $this->sortBy('admin.expenses.index', $columns, 4);

        $this->assertCount(
            count($byId['data']),
            $byEmployee['data'],
            'الترتيب باسم الموظف ضيّع صفوف'
        );

        // وكل صف لازم يفضل معاه الـ id بتاعه
        foreach ($byEmployee['data'] as $row) {
            $this->assertNotEmpty($row['id'] ?? null, 'صف رجع من غير id بعد الترتيب بالعلاقة');
        }
    }

    /**
     * الأعمدة المحسوبة بتتفلتر برضه.
     *
     * لما بنترجم عمود بـ addColumn بيبقى محسوب، و yajra مابيبحثش
     * في المحسوب تلقائياً — فالفلتر بيتجاهل بالكامل.
     */
    public function test_computed_column_filter_actually_filters(): void
    {
        $this->seedEnumRows();

        $columns = [
            ['data' => 'id', 'name' => 'id', 'searchable' => 'true', 'orderable' => 'true',
             'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'payment_method', 'name' => 'payment_method', 'searchable' => 'true', 'orderable' => 'true',
             'search' => ['value' => 'card', 'regex' => 'false']],
        ];

        $json = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.expenses.index', [
                'draw' => 1, 'columns' => $columns,
                'order' => [['column' => 0, 'dir' => 'desc']],
                'start' => 0, 'length' => 10,
                'search' => ['value' => '', 'regex' => 'false'],
            ]))
            ->assertOk()
            ->json();

        $total = Expense::whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()])->count();

        $this->assertGreaterThan(1, $total, 'محتاجين أكتر من صف عشان الفلتر يبان');

        $this->assertLessThan(
            $total,
            $json['recordsFiltered'],
            'فلتر طريقة الصرف مش بيفلتر — بيرجّع كل الصفوف'
        );

        foreach ($json['data'] as $row) {
            $this->assertSame(
                __('expense.enums.card'),
                $row['payment_method'],
                'الفلتر رجّع طريقة صرف تانية'
            );
        }
    }

    /** تصنيف المصروف والفرع بحثهم قائمة مش كتابة حرة. */
    public function test_expense_category_and_branch_use_select_filters(): void
    {
        $html = $this->get(route('admin.expenses.index'))->assertOk()->getContent();

        foreach (['category.name', 'branch.name'] as $column) {
            $this->assertMatchesRegularExpression(
                '/"name":"' . preg_quote($column, '/') . '"[^}]*"searchType":"select"/',
                $html,
                "العمود {$column} لسه بحثه كتابة حرة"
            );
        }
    }
}
