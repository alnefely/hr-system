<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeePenalty;
use App\Models\EmployeeReward;
use App\Models\Link;
use App\Models\Role;
use App\Services\HR\PayrollGenerator;
use Database\Seeders\ComprehensiveDummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * كل جداول DataTables بترجع JSON سليم ببيانات حقيقية.
 *
 * أعمدة الإجراءات والحالات اتنقلت من نصوص HTML جوّه الكنترولرات لـ Blade
 * partials. الاختبار ده بيرندر كل جدول بصفوف فعلية عشان أي partial فيه
 * متغيّر ناقص أو مسار غلط يوقّع هنا مش في وش المستخدم.
 */
class DataTablesRenderTest extends TestCase
{
    use RefreshDatabase;

    private const TABLES = [
        'admin.admins',
        'admin.payrolls.index',
        'admin.leaves.index', 'admin.leaves.trash',
        'admin.permissions.index', 'admin.permissions.trash',
        'admin.penalties.index', 'admin.penalties.trash',
        'admin.rewards.index', 'admin.rewards.trash',
        'admin.advances.index', 'admin.advances.trash',
        'admin.sales.index', 'admin.sales.trash',
        'admin.expenses.index', 'admin.expenses.trash',
        'admin.customers.index', 'admin.customers.trash',
        'admin.expense-categories.index',
        'admin.customer-sources.index',
        'admin.sales-channels.index',
        'admin.vendors.index',
        'admin.vendor-subscriptions.index',
        'admin.links.index', 'admin.links.trash',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ComprehensiveDummySeeder::class);

        $role = Role::create(['name' => 'Smoke', 'roles' => all_roles()]);
        $this->actingAs(Admin::create([
            'name' => 'Smoke', 'email' => 'smoke@test.com',
            'password' => bcrypt('password'), 'main' => true, 'role_id' => $role->id,
        ]), 'admin');

        $this->ensureRowsForEveryState();
    }

    /** صفوف بكل الحالات عشان كل فروع الـ partials تترندر. */
    private function ensureRowsForEveryState(): void
    {
        $employee = Employee::first();
        $branch   = Branch::first();

        foreach (['pending', 'approved', 'rejected'] as $status) {
            EmployeePenalty::create([
                'employee_id' => $employee->id, 'penalty_type' => 'amount_deduction',
                'deduction_amount' => 50, 'penalty_date' => now()->toDateString(),
                'reason' => 'smoke', 'status' => $status,
            ]);
        }

        foreach (['pending', 'approved', 'paid'] as $status) {
            EmployeeReward::create([
                'employee_id' => $employee->id, 'reward_type' => 'bonus', 'title' => 'smoke',
                'amount' => 100, 'reward_date' => now()->toDateString(), 'status' => $status,
            ]);
        }

        EmployeeAdvance::create([
            'employee_id' => $employee->id, 'amount' => 500, 'installments_count' => 1,
            'monthly_installment' => 500, 'paid_amount' => 0, 'waived_amount' => 0,
            'remaining_amount' => 500, 'advance_date' => now()->toDateString(),
            'start_date' => now()->toDateString(), 'status' => 'pending',
        ]);

        app(PayrollGenerator::class)->generate(2026, 1, $branch->id);

        Link::create(['short_code' => 'Smk1234', 'original_url' => 'https://example.com', 'is_active' => true])->delete();
        Link::create(['short_code' => 'Smk5678', 'original_url' => 'https://example.com', 'is_active' => true]);
    }

    public function test_every_table_renders_its_rows(): void
    {
        foreach (self::TABLES as $name) {
            $response = $this->getJson(route($name) . '?draw=1&start=0&length=50', [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

            $this->assertSame(200, $response->status(), "الجدول {$name} رجّع {$response->status()}: " . mb_substr((string) $response->getContent(), 0, 300));
            $this->assertIsArray($response->json('data'), "الجدول {$name} مارجّعش data");
        }
    }

    public function test_action_columns_contain_confirm_forms(): void
    {
        $rows = $this->getJson(route('admin.penalties.index') . '?draw=1&start=0&length=50', [
            'X-Requested-With' => 'XMLHttpRequest',
        ])->json('data');

        $pending = collect($rows)->firstWhere('status', 'pending');

        $this->assertNotNull($pending);
        $this->assertStringContainsString('btn-swal-confirm', $pending['actions']);
        $this->assertStringContainsString('name="_token"', $pending['actions']);
        $this->assertStringContainsString(route('admin.penalties.approve', $pending['id']), $pending['actions']);
    }
}
