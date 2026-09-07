<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PettyCashBalance;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * شاشة المصروفات: بطاقات العهدة وشرح الفلاتر.
 *
 * الخلفية: بطاقتا "الرصيد المخصص" و"المتبقي" بتخصّا دورة شهر كاملة،
 * فلما المستخدم يحدد نطاق تواريخ مخصص بيبقى عرضهم مضلّل — الرصيد
 * بيرجع للشهر بينما المنصرف بيتحسب على الفترة، وطرحهم من بعض بيدي
 * رقم مالوش معنى محاسبي.
 */
class ExpenseFiltersUiTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Branch $branch;
    protected ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@f.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->category = ExpenseCategory::create([
            'name' => 'Office', 'code' => 'OFF', 'is_active' => true,
        ]);

        PettyCashBalance::create([
            'branch_id' => $this->branch->id, 'year' => 2026, 'month' => 9,
            'allocated_amount' => 10000, 'carried_over_amount' => 0,
        ]);

        Expense::create([
            'title' => 'Test', 'expense_category_id' => $this->category->id,
            'branch_id' => $this->branch->id, 'amount' => 500,
            'currency' => 'EGP', 'expense_date' => '2026-09-10',
            'payment_method' => 'cash',
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    /** الوضع الافتراضي (دورة شهر): البطاقات الثلاث كلها ظاهرة. */
    public function test_custody_cards_are_visible_on_a_full_month_cycle(): void
    {
        $html = $this->get(route('admin.expenses.index', [
            'year' => 2026, 'month' => 9, 'branch_id' => $this->branch->id,
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('fa-vault', $html, 'بطاقة الرصيد المخصص مخفية');
        $this->assertStringContainsString('fa-scale-balanced', $html, 'بطاقة المتبقي مخفية');
        $this->assertStringNotContainsString('نطاق مخصص', $html);
    }

    /** مع نطاق تواريخ: بطاقتا العهدة بتتخفي. */
    public function test_custody_cards_are_hidden_with_a_custom_date_range(): void
    {
        $html = $this->get(route('admin.expenses.index', [
            'year' => 2026, 'month' => 9, 'branch_id' => $this->branch->id,
            'date_from' => '2026-09-01', 'date_to' => '2026-09-15',
        ]))->assertOk()->getContent();

        $this->assertStringNotContainsString('fa-vault', $html, 'بطاقة الرصيد المخصص المفروض تتخفي');
        $this->assertStringNotContainsString('fa-scale-balanced', $html, 'بطاقة المتبقي المفروض تتخفي');
    }

    /** وبتظهر بدلها بطاقة عدد العمليات + تنبيه واضح. */
    public function test_period_card_and_warning_replace_them(): void
    {
        $html = $this->get(route('admin.expenses.index', [
            'date_from' => '2026-09-01', 'date_to' => '2026-09-15',
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('نطاق مخصص', $html, 'شارة النطاق المخصص مش ظاهرة');
        $this->assertStringContainsString('عدد المصروفات في الفترة', $html);
        $this->assertStringContainsString('بطاقات العهدة مخفية', $html, 'التنبيه مش ظاهر');
    }

    /** تاريخ واحد (من فقط) يكفي لاعتبارها فترة مخصصة. */
    public function test_a_single_date_bound_also_counts_as_a_custom_range(): void
    {
        $html = $this->get(route('admin.expenses.index', [
            'date_from' => '2026-09-05',
        ]))->assertOk()->getContent();

        $this->assertStringNotContainsString('fa-scale-balanced', $html);
        $this->assertStringContainsString('نطاق مخصص', $html);
    }

    /** أيقونة الشرح موجودة ومحتواها بيتحمّل. */
    public function test_help_popover_is_present_with_content(): void
    {
        $html = $this->get(route('admin.expenses.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-bs-toggle="popover"', $html, 'أيقونة الشرح مش موجودة');
        $this->assertStringContainsString('دورة العهدة', $html);
        $this->assertStringContainsString('ليه بنخفي البطاقات؟', $html, 'محتوى الشرح مااتحقنش');
    }

    /** التسميات الجديدة بتوضّح الفرق. */
    public function test_filter_labels_explain_the_difference(): void
    {
        $html = $this->get(route('admin.expenses.index'))->assertOk()->getContent();

        $this->assertStringContainsString('دورة العهدة (الشهر / السنة)', $html);
        $this->assertStringContainsString('نطاق تواريخ مخصص', $html);
    }

    /** الـ popover مفعّل في الـ layout (الثيم بيفعّل الـ tooltip بس). */
    public function test_popovers_are_initialized(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/admin/app.blade.php'));

        $this->assertStringContainsString(
            'bootstrap.Popover',
            $layout,
            'الـ popover مش مفعّل — الأيقونة مش هتشتغل'
        );
    }
}
