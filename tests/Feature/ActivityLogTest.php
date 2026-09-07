<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\JobTitle;
use App\Models\Role;
use App\Jobs\RecordActivityJob;
use App\Services\Activity\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * سجل حركات النظام: التسجيل، الصلاحيات، والتنظيف.
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['activity.enabled' => true]);

        $role = Role::create(['name' => 'Super Admin', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Audit Admin', 'email' => 'audit@test.com',
            'password' => bcrypt('password'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    // ========================================================
    //  التسجيل
    // ========================================================

    public function test_create_is_logged_with_causer_and_new_values(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Office Supplies', 'code' => 'OFF', 'is_active' => true,
        ]);

        $log = ActivityLog::latest('id')->first();

        $this->assertEquals('created', $log->event);
        $this->assertEquals(ExpenseCategory::class, $log->subject_type);
        $this->assertEquals($category->id, $log->subject_id);
        $this->assertEquals($this->admin->id, $log->causer_id);
        $this->assertEquals('Audit Admin', $log->causer_name);
        $this->assertEquals('Office Supplies', $log->subject_label);
        $this->assertEquals('Office Supplies', $log->new_values['name']);
    }

    public function test_update_logs_only_changed_fields_with_before_and_after(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Before', 'code' => 'C1', 'is_active' => true,
        ]);

        $category->update(['name' => 'After', 'is_active' => false]);

        $log = ActivityLog::where('event', 'updated')->latest('id')->first();

        $this->assertEquals(['name', 'is_active'], array_keys($log->old_values));
        $this->assertEquals('Before', $log->old_values['name']);
        $this->assertEquals('After', $log->new_values['name']);
        $this->assertCount(2, $log->new_values, 'اتسجّلت حقول ماتغيّرتش');
    }

    public function test_saving_without_real_change_is_not_logged(): void
    {
        $category = ExpenseCategory::create(['name' => 'Same', 'code' => 'C2', 'is_active' => true]);

        $countBefore = ActivityLog::count();
        $category->update(['name' => 'Same']);

        $this->assertEquals($countBefore, ActivityLog::count(), 'اتسجّل لوج من غير تغيير حقيقي');
    }

    public function test_soft_delete_restore_and_force_delete_are_logged(): void
    {
        $category = ExpenseCategory::create(['name' => 'Temp', 'code' => 'C3', 'is_active' => true]);

        $category->delete();
        $this->assertEquals('deleted', ActivityLog::latest('id')->first()->event);

        $category->restore();
        $this->assertEquals('restored', ActivityLog::latest('id')->first()->event);

        $category->forceDelete();
        $log = ActivityLog::latest('id')->first();
        $this->assertEquals('force_deleted', $log->event);

        // الوصف بيفضل محفوظ حتى بعد ما السجل الأصلي يختفي
        $this->assertEquals('Temp', $log->subject_label);
    }

    public function test_sensitive_fields_are_never_logged(): void
    {
        $branch = Branch::create([
            'name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);
        $jobTitle = JobTitle::create(['name' => 'Dev', 'is_active' => true]);

        $employee = Employee::create([
            'branch_id' => $branch->id, 'job_title_id' => $jobTitle->id,
            'name' => 'Secret Holder', 'employee_code' => 'EMP-S1',
            'phone' => '01000000000', 'status' => 'active',
            'basic_salary' => 5000, 'hire_date' => '2024-01-01',
            'national_id' => '29001011234567',
            'iban' => 'EG380019000500000000263180002',
            'bank_account_number' => '1234567890',
        ]);

        $log = ActivityLog::where('subject_type', Employee::class)->latest('id')->first();
        $keys = array_keys($log->new_values ?? []);

        $this->assertNotContains('national_id', $keys);
        $this->assertNotContains('iban', $keys);
        $this->assertNotContains('bank_account_number', $keys);
        $this->assertContains('name', $keys, 'الحقول العادية المفروض تتسجّل');
    }

    public function test_logging_can_be_disabled_for_bulk_operations(): void
    {
        $countBefore = ActivityLog::count();

        app(ActivityLogger::class)->withoutLogging(function () {
            ExpenseCategory::create(['name' => 'Bulk 1', 'code' => 'B1', 'is_active' => true]);
            ExpenseCategory::create(['name' => 'Bulk 2', 'code' => 'B2', 'is_active' => true]);
        });

        $this->assertEquals($countBefore, ActivityLog::count(), 'التسجيل ماتوقفش في العملية الجماعية');

        // ويرجع شغال بعدها
        ExpenseCategory::create(['name' => 'After Bulk', 'code' => 'B3', 'is_active' => true]);
        $this->assertEquals($countBefore + 1, ActivityLog::count());
    }

    public function test_activities_relation_returns_model_history(): void
    {
        $category = ExpenseCategory::create(['name' => 'Tracked', 'code' => 'T1', 'is_active' => true]);
        $category->update(['name' => 'Tracked v2']);
        $category->update(['is_active' => false]);

        $this->assertCount(3, $category->activities()->get());
    }

    public function test_queued_writes_dispatch_a_job_instead_of_writing_inline(): void
    {
        Queue::fake();
        config(['activity.queue_writes' => true, 'queue.default' => 'database']);

        $countBefore = ActivityLog::count();

        ExpenseCategory::create(['name' => 'Queued', 'code' => 'Q1', 'is_active' => true]);

        Queue::assertPushed(RecordActivityJob::class, 1);
        $this->assertEquals($countBefore, ActivityLog::count(), 'اتكتب inline رغم إن الطابور مفعّل');
    }

    public function test_sync_queue_falls_back_to_inline_write(): void
    {
        Queue::fake();
        config(['activity.queue_writes' => true, 'queue.default' => 'sync']);

        $countBefore = ActivityLog::count();

        ExpenseCategory::create(['name' => 'Sync', 'code' => 'S1', 'is_active' => true]);

        Queue::assertNothingPushed();
        $this->assertEquals($countBefore + 1, ActivityLog::count(), 'مااتكتبش inline مع طابور sync');
    }

    // ========================================================
    //  الصلاحيات
    // ========================================================

    public function test_admin_without_permission_cannot_view_logs(): void
    {
        $limitedRole = Role::create(['name' => 'Limited', 'roles' => ['dashboard_show']]);
        $limited = Admin::create([
            'name' => 'Limited', 'email' => 'limited@test.com',
            'password' => bcrypt('password'), 'main' => true, 'role_id' => $limitedRole->id,
        ]);

        $this->actingAs($limited, 'admin')
            ->get(route('admin.activity-logs.index'))
            ->assertRedirect(route('admin.error_403'));
    }

    public function test_admin_with_permission_can_view_logs(): void
    {
        $this->get(route('admin.activity-logs.index'))->assertOk();
    }

    public function test_module_scoped_log_filters_to_that_module(): void
    {
        ExpenseCategory::create(['name' => 'Cat', 'code' => 'X1', 'is_active' => true]);
        Branch::create([
            'name' => 'Branch X', 'code' => 'BX', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $response = $this->getJson(
            route('admin.activity-logs.subject', ExpenseCategory::class)
        );

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertStringContainsString(
                __('expense_category.title'),
                $row['subject_name'],
                'ظهرت حركة من موديول تاني في السجل المخصص'
            );
        }
    }

    public function test_unknown_module_returns_404(): void
    {
        $this->get(route('admin.activity-logs.subject', 'App\\Models\\NotAModel'))
            ->assertNotFound();
    }

    public function test_details_endpoint_returns_before_and_after(): void
    {
        $category = ExpenseCategory::create(['name' => 'Detail', 'code' => 'D1', 'is_active' => true]);
        $category->update(['name' => 'Detail Changed']);

        $log = ActivityLog::where('event', 'updated')->latest('id')->first();

        $this->getJson(route('admin.activity-logs.show', $log->id))
            ->assertOk()
            ->assertJsonPath('event', 'updated')
            ->assertJsonFragment(['old' => 'Detail', 'new' => 'Detail Changed']);
    }

    // ========================================================
    //  أمر التنظيف
    // ========================================================

    public function test_prune_command_deletes_only_old_records(): void
    {
        ExpenseCategory::create(['name' => 'Recent', 'code' => 'R1', 'is_active' => true]);

        // سجل قديم صناعي
        ActivityLog::create([
            'event' => 'created', 'subject_type' => ExpenseCategory::class,
            'subject_id' => 999, 'subject_label' => 'Old One',
        ]);
        ActivityLog::where('subject_id', 999)->update(['created_at' => now()->subDays(200)]);

        $this->artisan('activity:prune', ['--days' => 90, '--force' => true])
            ->assertExitCode(0);

        $this->assertEquals(0, ActivityLog::where('subject_id', 999)->count(), 'السجل القديم مااتحذفش');
        $this->assertGreaterThan(0, ActivityLog::count(), 'السجلات الحديثة اتحذفت بالغلط');
    }

    public function test_prune_dry_run_deletes_nothing(): void
    {
        ActivityLog::create([
            'event' => 'created', 'subject_type' => ExpenseCategory::class,
            'subject_id' => 888, 'subject_label' => 'Old',
        ]);
        ActivityLog::where('subject_id', 888)->update(['created_at' => now()->subDays(200)]);

        $this->artisan('activity:prune', ['--days' => 30, '--dry-run' => true])
            ->assertExitCode(0);

        $this->assertEquals(1, ActivityLog::where('subject_id', 888)->count());
    }

    public function test_prune_accepts_custom_days(): void
    {
        ActivityLog::create([
            'event' => 'created', 'subject_type' => ExpenseCategory::class,
            'subject_id' => 777, 'subject_label' => 'Ten days old',
        ]);
        ActivityLog::where('subject_id', 777)->update(['created_at' => now()->subDays(10)]);

        // 30 يوم => مايتحذفش
        $this->artisan('activity:prune', ['--days' => 30, '--force' => true]);
        $this->assertEquals(1, ActivityLog::where('subject_id', 777)->count());

        // 5 أيام => يتحذف
        $this->artisan('activity:prune', ['--days' => 5, '--force' => true]);
        $this->assertEquals(0, ActivityLog::where('subject_id', 777)->count());
    }

    public function test_prune_rejects_invalid_days(): void
    {
        $this->artisan('activity:prune', ['--days' => 0, '--force' => true])
            ->assertExitCode(1);
    }

    public function test_prune_can_target_a_single_module(): void
    {
        ActivityLog::create([
            'event' => 'created', 'subject_type' => ExpenseCategory::class,
            'subject_id' => 601, 'subject_label' => 'Cat old',
        ]);
        ActivityLog::create([
            'event' => 'created', 'subject_type' => Branch::class,
            'subject_id' => 602, 'subject_label' => 'Branch old',
        ]);
        ActivityLog::whereIn('subject_id', [601, 602])->update(['created_at' => now()->subDays(200)]);

        $this->artisan('activity:prune', [
            '--days' => 90,
            '--subject' => ExpenseCategory::class,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertEquals(0, ActivityLog::where('subject_id', 601)->count(), 'الموديول المستهدف مااتنظفش');
        $this->assertEquals(1, ActivityLog::where('subject_id', 602)->count(), 'اتحذف موديول مش مستهدف');
    }
}
