<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PettyCashBalance;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ترحيل العهدة النقدية.
 *
 * السيناريو هو نفس أرقام الشكوى الفعلية:
 *
 *   أغسطس:  مخصص 6305 − مصروف 5014              = 1291 ← يترحّل
 *   سبتمبر: 5000 + 1291 = 6291 − (3107 + 2766)  = 418  ← يترحّل
 *   أكتوبر: 5000 + 418                          = 5418
 *
 * الشاشة كانت بتعرض 6291 لأن الترحيل اتكتب على صف بدون فرع
 * وفضل يترحّل لوحده كل شهر.
 */
class PettyCashCarryOverTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;
    protected ExpenseCategory $category;
    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-05 10:00:00');

        // السيدر بيضيف أرصدة ومصروفات لفروع تانية، والإجمالي على
        // "جميع الفروع" بيتأثر بيها — بنبدأ من جدولين فاضيين.
        \Illuminate\Support\Facades\DB::table('petty_cash_balances')->delete();
        \Illuminate\Support\Facades\DB::table('expenses')->delete();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@petty.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->branch = Branch::create([
            'name' => 'فرع التجمع', 'code' => 'PC-01', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->category = ExpenseCategory::create(['name' => 'نثريات', 'is_active' => true]);

        $this->actingAs($this->admin, 'admin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function allocate(int $month, float $amount): void
    {
        PettyCashBalance::create([
            'branch_id'        => $this->branch->id,
            'year'             => 2026,
            'month'            => $month,
            'allocated_amount' => $amount,
        ]);
    }

    private function spend(string $date, float $amount, string $method = 'cash'): Expense
    {
        return Expense::create([
            'title'               => 'مصروف',
            'expense_category_id' => $this->category->id,
            'branch_id'           => $this->branch->id,
            'amount'              => $amount,
            'currency'            => 'EGP',
            'expense_date'        => $date,
            'payment_method'      => $method,
            'created_by'          => $this->admin->id,
        ]);
    }

    /** نفس أرقام الشكوى. */
    private function seedScenario(): void
    {
        $this->allocate(8, 6305);
        $this->allocate(9, 5000);
        $this->allocate(10, 5000);

        $this->spend('2026-08-10', 5014);
        $this->spend('2026-09-10', 3107, 'cash');
        $this->spend('2026-09-15', 2766, 'bank_transfer');
    }

    private function balance(int $month): ?PettyCashBalance
    {
        return PettyCashBalance::where('branch_id', $this->branch->id)
            ->where('year', 2026)->where('month', $month)->first();
    }

    /** السلسلة بتتحسب صح شهر ورا شهر. */
    public function test_carry_over_chains_across_months(): void
    {
        $this->seedScenario();

        $this->artisan('petty-cash:carry-over')->assertSuccessful();

        $this->assertEquals(1291, (float) $this->balance(9)->carried_over_amount, 'ترحيل سبتمبر غلط');
        $this->assertEquals(418, (float) $this->balance(10)->carried_over_amount, 'ترحيل أكتوبر غلط');
        $this->assertEquals(5418, (float) $this->balance(10)->total_balance, 'إجمالي أكتوبر غلط');
    }

    /** والشاشة بتعرض 5418 مش 6291. */
    public function test_screen_shows_correct_remaining_for_all_branches(): void
    {
        $this->seedScenario();
        $this->artisan('petty-cash:carry-over');

        $response = $this->get(route('admin.expenses.index'));

        $response->assertOk();
        $this->assertEquals(5418, $response->viewData('remainingBalance'), 'المتبقي في "جميع الفروع" غلط');
    }

    public function test_screen_shows_correct_remaining_for_one_branch(): void
    {
        $this->seedScenario();
        $this->artisan('petty-cash:carry-over');

        $response = $this->get(route('admin.expenses.index', ['branch_id' => $this->branch->id]));

        $this->assertEquals(5418, $response->viewData('remainingBalance'), 'المتبقي للفرع غلط');
    }

    /**
     * مفيش ترحيل يدوي — الترحيل أوتوماتيك بالكامل.
     *
     * الزر كان بيكرر شغل النظام، وكان بيسمح بترحيل شهر لسه ماخلصش
     * (ترحيل أكتوبر لنوفمبر ونوفمبر لديسمبر وإحنا لسه في أكتوبر).
     */
    public function test_manual_rollover_is_removed(): void
    {
        $this->assertFalse(
            \Illuminate\Support\Facades\Route::has('admin.expenses.rollover-balance'),
            'راوت الترحيل اليدوي لسه موجود'
        );

        $this->allocate(10, 5000);

        $this->get(route('admin.expenses.index'))
            ->assertOk()
            ->assertDontSee('rolloverCustodyModal', false);
    }

    /** ومن غير زر، المتبقي بيوصل للشهر الجاي لوحده مع التخصيص. */
    public function test_carry_reaches_next_month_without_any_button(): void
    {
        $this->allocate(8, 6305);
        $this->spend('2026-08-10', 5014);

        // تخصيص سبتمبر من الشاشة — مفيش أي ترحيل يدوي
        $this->post(route('admin.expenses.update-balance'), [
            'branch_id' => $this->branch->id, 'year' => 2026, 'month' => 9, 'allocated_amount' => 5000,
        ])->assertRedirect();

        $this->assertEquals(1291, (float) $this->balance(9)->carried_over_amount);
        $this->assertEquals(0, PettyCashBalance::whereNull('branch_id')->count(), 'اتعمل صف بدون فرع');
    }

    /**
     * تعديل مصروف شهر قديم بيصحّح كل الشهور اللي بعده.
     *
     * قبل كده المرحّل كان بيتحسب مرة واحدة ويفضل قديم.
     */
    public function test_editing_old_expense_corrects_later_months(): void
    {
        $this->seedScenario();
        $this->artisan('petty-cash:carry-over');

        $this->assertEquals(5418, (float) $this->balance(10)->total_balance);

        // مصروف أغسطس طلع أقل بـ 100
        Expense::where('amount', 5014)->first()->update(['amount' => 4914]);

        $this->artisan('petty-cash:carry-over');

        $this->assertEquals(1391, (float) $this->balance(9)->carried_over_amount, 'سبتمبر مااتصححش');
        $this->assertEquals(518, (float) $this->balance(10)->carried_over_amount, 'أكتوبر مااتصححش');
    }

    /** والترحيل بينزل لصفر لو المصروف زاد — مايفضلش على رقم قديم. */
    public function test_carry_drops_to_zero_when_spending_exceeds_balance(): void
    {
        $this->seedScenario();
        $this->artisan('petty-cash:carry-over');

        // شرط مسبق: لازم يكون فيه ترحيل الأول عشان نختبر إنه بينزل —
        // من غيره الاختبار بيعدّي حتى لو الترحيل عمره ما اتحسب.
        $this->assertEquals(418, (float) $this->balance(10)->carried_over_amount);

        $this->spend('2026-09-20', 1000);   // سبتمبر بقى عجز

        $this->artisan('petty-cash:carry-over');

        $this->assertEquals(0, (float) $this->balance(10)->carried_over_amount, 'فضل ترحيل قديم بعد العجز');
        $this->assertEquals(5000, (float) $this->balance(10)->total_balance);
    }

    /** أي صف قديم بدون فرع مابيدخلش في الجمع. */
    public function test_branchless_rows_are_ignored_on_screen(): void
    {
        $this->seedScenario();
        $this->artisan('petty-cash:carry-over');

        PettyCashBalance::create([
            'branch_id' => null, 'year' => 2026, 'month' => 10,
            'allocated_amount' => 0, 'carried_over_amount' => 1291,
        ]);

        $response = $this->get(route('admin.expenses.index'));

        $this->assertEquals(5418, $response->viewData('remainingBalance'), 'الصف اللي بدون فرع دخل في الجمع');
    }

    /** التشغيل تاني مابيغيّرش حاجة. */
    public function test_carry_over_is_idempotent(): void
    {
        $this->seedScenario();

        $this->artisan('petty-cash:carry-over');
        $this->artisan('petty-cash:carry-over');

        $this->assertEquals(3, PettyCashBalance::count(), 'اتعملت صفوف زيادة');
        $this->assertEquals(5418, (float) $this->balance(10)->total_balance);
    }

    /** السيدر مابيعملش عهدة بدون فرع، ومرحّله متسق مع المصروفات. */
    public function test_seeder_creates_no_branchless_custody(): void
    {
        $this->seed(\Database\Seeders\ComprehensiveDummySeeder::class);

        $this->assertGreaterThan(0, PettyCashBalance::count(), 'السيدر ماعملش أرصدة أصلاً');
        $this->assertEquals(0, PettyCashBalance::whereNull('branch_id')->count(), 'السيدر عمل عهدة بدون فرع');

        // وإعادة الحساب مابتغيّرش حاجة = المرحّل اللي السيدر كتبه صح
        $this->assertEquals(
            0,
            app(\App\Services\Finance\PettyCashService::class)->carryOverAll(2026, 9),
            'مرحّل السيدر مش متسق مع المصروفات'
        );
    }

    /**
     * تعديل مصروف قديم بيصحّح الترحيل فوراً — من غير ما نستنى الأمر الشهري.
     */
    public function test_editing_expense_recalculates_immediately(): void
    {
        $this->seedScenario();
        $this->artisan('petty-cash:carry-over');

        $this->assertEquals(5418, (float) $this->balance(10)->total_balance);

        // تعديل من غير تشغيل الأمر
        Expense::where('amount', 5014)->first()->update(['amount' => 4914]);

        $this->assertEquals(518, (float) $this->balance(10)->carried_over_amount, 'الترحيل مااتحدّثش فوراً بعد التعديل');
    }

    /** وحذف مصروف كمان. */
    public function test_deleting_expense_recalculates_immediately(): void
    {
        $this->seedScenario();
        $this->artisan('petty-cash:carry-over');

        Expense::where('amount', 2766)->first()->delete();

        // سبتمبر: 6291 − 3107 = 3184
        $this->assertEquals(3184, (float) $this->balance(10)->carried_over_amount, 'الحذف ماأثّرش على الترحيل');
    }

    /** وتسجيل مصروف جديد من الشاشة. */
    public function test_new_expense_from_screen_recalculates(): void
    {
        $this->seedScenario();
        $this->artisan('petty-cash:carry-over');

        $this->post(route('admin.expenses.store'), [
            'title'               => 'مصروف متأخر لسبتمبر',
            'expense_category_id' => $this->category->id,
            'branch_id'           => $this->branch->id,
            'amount'              => 18,
            'expense_date'        => '2026-09-28',
            'payment_method'      => 'cash',
        ])->assertRedirect();

        $this->assertEquals(400, (float) $this->balance(10)->carried_over_amount, 'المصروف الجديد ماأثّرش على الترحيل');
    }

    /** تخصيص الشهر الجاي مقدماً بياخد مرحّله صح. */
    public function test_future_month_allocation_gets_correct_carry(): void
    {
        $this->seedScenario();
        $this->artisan('petty-cash:carry-over');

        $this->post(route('admin.expenses.update-balance'), [
            'branch_id'        => $this->branch->id,
            'year'             => 2026,
            'month'            => 11,
            'allocated_amount' => 4000,
        ])->assertRedirect();

        // أكتوبر مافيهوش مصروف لسه: 5418 كلها تترحّل لنوفمبر
        $this->assertEquals(5418, (float) $this->balance(11)->carried_over_amount, 'مرحّل الشهر الجاي غلط');
        $this->assertEquals(9418, (float) $this->balance(11)->total_balance);
    }

    // ========================================================
    //  شرح الترحيل في الشاشة
    // ========================================================

    /** الشاشة بتشرح الترحيل بأرقام الشهر الفعلية. */
    public function test_page_explains_carry_over_with_real_numbers(): void
    {
        $this->seedScenario();
        $this->artisan('petty-cash:carry-over');

        // سبتمبر: 5000 + 1291 = 6291 − 5873 = 418 ← أكتوبر
        $response = $this->get(route('admin.expenses.index', [
            'branch_id' => $this->branch->id,
            'date_from' => '2026-09-01',
            'date_to'   => '2026-09-30',
        ]));

        // مودال يفتح من رابط في بطاقة المخصص — مش كارت واخد مساحة
        $response->assertOk()
            ->assertSee('id="carryExplainerModal"', false)
            ->assertSee('data-bs-target="#carryExplainerModal"', false);

        $row = $response->viewData('carryBreakdown')['EGP'];

        $this->assertEquals(5000, $row['allocated']);
        $this->assertEquals(1291, $row['carried']);
        $this->assertEquals(6291, $row['total']);
        $this->assertEquals(5873, $row['spent']);
        $this->assertEquals(418, $row['remaining']);

        $response->assertSee(__('expense.carry.goes_to', ['month' => '10/2026']));
    }

    /**
     * الشرح بيتحسب على كل مصروفات الشهر حتى لو فيه فلتر.
     *
     * بطاقة "المتبقي" بتتأثر بفلتر طريقة الدفع، لكن الترحيل الفعلي لأ —
     * لو الشرح اعتمد على البطاقة كان هيقول رقم والترحيل رقم تاني.
     */
    public function test_explanation_ignores_filters_like_the_real_carry(): void
    {
        $this->seedScenario();
        $this->artisan('petty-cash:carry-over');

        $response = $this->get(route('admin.expenses.index', [
            'branch_id'      => $this->branch->id,
            'date_from'      => '2026-09-01',
            'date_to'        => '2026-09-30',
            'payment_method' => 'cash',   // البطاقة هتطرح 3107 بس
        ]));

        $row = $response->viewData('carryBreakdown')['EGP'];

        $this->assertEquals(5873, $row['spent'], 'الشرح اتأثر بالفلتر');
        $this->assertEquals(418, $row['remaining']);

        // ونفس الرقم اللي اترحّل فعلاً
        $this->assertEquals($row['remaining'], (float) $this->balance(10)->carried_over_amount);
    }

    /** العجز بيتشرح إنه مابيترحّلش. */
    public function test_explanation_shows_deficit_is_not_carried(): void
    {
        $this->seedScenario();
        $this->spend('2026-09-20', 1000);   // سبتمبر بقى عجز 582
        $this->artisan('petty-cash:carry-over');

        $response = $this->get(route('admin.expenses.index', [
            'branch_id' => $this->branch->id,
            'date_from' => '2026-09-01',
            'date_to'   => '2026-09-30',
        ]));

        $this->assertEquals(-582, $response->viewData('carryBreakdown')['EGP']['remaining']);
        $response->assertSee(__('expense.carry.deficit', ['month' => '10/2026']));
    }

    /**
     * الشرح متاح لأي حد بيشوف بطاقة الرصيد — مش لصاحب صلاحية إدارة
     * الأرصدة بس. لو المودال جوه بلوك الصلاحية، الرابط بيظهر والمودال لأ.
     */
    public function test_explainer_modal_available_without_balance_permission(): void
    {
        $this->seedScenario();

        $role  = Role::create(['name' => 'Viewer', 'roles' => ['expense_show']]);
        $viewer = Admin::create([
            'name' => 'Viewer', 'email' => 'viewer@petty.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->actingAs($viewer, 'admin')
            ->get(route('admin.expenses.index', ['branch_id' => $this->branch->id]))
            ->assertOk()
            ->assertSee('data-bs-target="#carryExplainerModal"', false)
            ->assertSee('id="carryExplainerModal"', false);
    }
}
