<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * نموذج المصروفات — حقل المبلغ تحديداً.
 *
 * كان الحقل متبني بـ x-inputs.field اللي بيلف الـ input في div،
 * والـ div ده جوّه input-group بيكسر تخطيط بوتستراب فالحقل بيبان
 * مضغوط وصعب الوصول له.
 */
class ExpenseFormTest extends TestCase
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
            'name' => 'Root', 'email' => 'root@e.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->category = ExpenseCategory::create([
            'name' => 'Office', 'code' => 'OFF', 'is_active' => true,
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    /** حقل المبلغ ابن مباشر للـ input-group — بدون div لافّ. */
    public function test_amount_input_is_a_direct_child_of_the_input_group(): void
    {
        $html = $this->get(route('admin.expenses.create'))->assertOk()->getContent();

        $position = strpos($html, 'id="amount"');
        $this->assertNotFalse($position, 'حقل المبلغ مش موجود');

        $groupStart = strrpos(substr($html, 0, $position), '<div class="input-group">');
        $this->assertNotFalse($groupStart, 'الحقل مش جوّه input-group');

        $between = substr($html, $groupStart, $position - $groupStart);

        $this->assertStringNotContainsString(
            'mb-3',
            $between,
            'في div لافّ جوّه الـ input-group — التخطيط هيتكسر'
        );
    }

    /** الحقل مش readonly ولا disabled. */
    public function test_amount_input_is_editable(): void
    {
        $html = $this->get(route('admin.expenses.create'))->assertOk()->getContent();

        $position = strpos($html, 'id="amount"');
        $tagStart = strrpos(substr($html, 0, $position), '<input');
        $tag = substr($html, $tagStart, strpos($html, '>', $position) - $tagStart);

        $this->assertStringNotContainsString('readonly', $tag, 'الحقل readonly');
        $this->assertStringNotContainsString('disabled', $tag, 'الحقل disabled');
        $this->assertStringContainsString('required', $tag, 'الحقل المفروض يكون مطلوب');
        $this->assertStringContainsString('type="number"', $tag);
    }

    /** شاشة التعديل بتعرض المبلغ المحفوظ. */
    public function test_edit_screen_shows_the_saved_amount(): void
    {
        $expense = Expense::create([
            'title' => 'Test', 'expense_category_id' => $this->category->id,
            'branch_id' => $this->branch->id, 'amount' => 1234.56,
            'currency' => 'EGP', 'expense_date' => '2026-09-01',
            'payment_method' => 'cash',
        ]);

        $this->get(route('admin.expenses.edit', $expense->id))
            ->assertOk()
            ->assertSee('1234.56', false);
    }

    /** الحفظ شغال فعلاً بالمبلغ المُدخل. */
    public function test_expense_can_be_saved_with_the_entered_amount(): void
    {
        $this->post(route('admin.expenses.store'), [
            'title'               => 'مصروف تجريبي',
            'expense_category_id' => $this->category->id,
            'branch_id'           => $this->branch->id,
            'amount'              => 750.25,
            'expense_date'        => '2026-09-05',
            'payment_method'      => 'cash',
        ])->assertSessionHasNoErrors();

        $expense = Expense::where('title', 'مصروف تجريبي')->first();

        $this->assertNotNull($expense, 'المصروف مااتحفظش');
        $this->assertEquals(750.25, (float) $expense->amount);
        $this->assertEquals('EGP', $expense->currency, 'العملة المفروض تتاخد من الفرع');
    }

    /** المبلغ الصفري أو السالب مرفوض. */
    public function test_invalid_amount_is_rejected(): void
    {
        $this->post(route('admin.expenses.store'), [
            'title'               => 'مرفوض',
            'expense_category_id' => $this->category->id,
            'branch_id'           => $this->branch->id,
            'amount'              => 0,
            'expense_date'        => '2026-09-05',
            'payment_method'      => 'cash',
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('expenses', ['title' => 'مرفوض']);
    }
}
