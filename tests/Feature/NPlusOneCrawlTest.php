<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Employee;
use App\Models\Role;
use App\Services\Portal\PortalAccessService;
use Database\Seeders\ComprehensiveDummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * فحص N+1 في اللوحتين: بيزور كل صفحة GET (والنسخة ajax للجداول) ببيانات
 * حقيقية متعددة.
 *
 * - LAZY_LOADING_THROW=true في phpunit.xml: أي علاقة اتحمّلت lazy على موديل
 *   جاي من مجموعة (= N+1 فعلياً) بترمي استثناء والصفحة بتطلع 500.
 * - عدد الاستعلامات لكل صفحة له سقف: بيكشف N+1 اللي مش علاقات (استعلام
 *   جوّه لوب في accessor أو view).
 */
class NPlusOneCrawlTest extends TestCase
{
    use RefreshDatabase;

    /** سقف الاستعلامات للصفحة الواحدة — أكتر من كده غالباً لوب بيستعلم. */
    private const MAX_QUERIES = 60;

    /** مسارات مالهاش لازمة في الفحص (تصدير ملفات، تحويل، خروج...). */
    private const SKIP = [
        '/export', '/print', 'logout', 'filemanager', 'broadcasting', 'storage', '_ignition', 'sanctum',
        'up', 'login', 'forgot', 'reset-password', 'password/reset', 'link.redirect',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->seed(ComprehensiveDummySeeder::class);

        // بيانات مش في الـ seeder: أكتر من سجل عشان N+1 يبان
        \App\Models\Link::create(['short_code' => 'Crw1111', 'original_url' => 'https://example.com/a', 'is_active' => true]);
        \App\Models\Link::create(['short_code' => 'Crw2222', 'original_url' => 'https://example.com/b', 'is_active' => true]);

        $this->seedTasks(Employee::withoutGlobalScopes()->orderBy('id')->first());

        $advanceId = DB::table('employee_advances')->min('id');
        foreach ([100, 50] as $amount) {
            \App\Models\EmployeeAdvanceRepayment::create([
                'employee_advance_id' => $advanceId, 'amount' => $amount,
                'repayment_date' => now()->toDateString(), 'type' => 'manual_payment',
            ]);
        }
    }

    public function test_admin_pages_have_no_n_plus_one(): void
    {
        $role = Role::create(['name' => 'Crawler', 'roles' => all_roles()]);
        $admin = Admin::create([
            'name' => 'Crawler', 'email' => 'crawler@test.com', 'password' => bcrypt('x'),
            'main' => true, 'role_id' => $role->id,
        ]);

        $this->crawl('admin.', $admin, 'admin');
    }

    public function test_portal_pages_have_no_n_plus_one(): void
    {
        // الموظف اللي عنده أكتر بيانات
        $employee = Employee::withoutGlobalScopes()
            ->withCount(['leaves', 'permissions', 'advances', 'payrollItems', 'documents'])
            ->get()
            ->sortByDesc(fn ($e) => $e->leaves_count + $e->permissions_count + $e->advances_count + $e->payroll_items_count + $e->documents_count)
            ->first();

        $this->giveEmployeeRowsOfEverything($employee);

        app(PortalAccessService::class)->enable($employee, 'Secret123');
        $employee->forceFill(['must_change_password' => false])->save();
        $this->crawl('portal.', $employee->fresh(), 'employee');
    }

    /**
     * 3 سجلات من كل نوع للموظف (بعلاقاتها) — N+1 مابيبانش غير مع أكتر
     * من سجل في القائمة.
     */
    private function giveEmployeeRowsOfEverything(Employee $employee): void
    {
        $adminId = DB::table('admins')->min('id');
        $category = \App\Models\ExpenseCategory::query()->firstOrCreate(['code' => 'CRWL'], ['name' => 'Crawl', 'is_active' => true]);
        $payroll = \App\Models\Payroll::create([
            'title' => 'Crawl', 'year' => 2025, 'month' => 1, 'branch_id' => $employee->branch_id, 'status' => 'paid',
        ]);

        foreach ([1, 2, 3] as $i) {
            $day = "2026-0{$i}-1{$i}";

            \App\Models\EmployeeLeave::create([
                'employee_id' => $employee->id, 'leave_type' => 'annual', 'start_date' => $day, 'end_date' => $day,
                'days_count' => 1, 'status' => $i === 1 ? 'rejected' : 'approved', 'rejection_reason' => 'x', 'approved_by' => $adminId,
            ]);
            \App\Models\EmployeePermission::create([
                'employee_id' => $employee->id, 'permission_date' => $day, 'start_time' => '10:00', 'end_time' => '11:00',
                'hours_count' => 1, 'status' => 'approved', 'approved_by' => $adminId,
            ]);
            \App\Models\EmployeeAdvance::create([
                'employee_id' => $employee->id, 'amount' => 300, 'installments_count' => 3, 'monthly_installment' => 100,
                'remaining_amount' => 300, 'advance_date' => $day, 'start_date' => $day, 'status' => 'approved', 'reason' => 'x',
            ]);
            \App\Models\EmployeeReward::create([
                'employee_id' => $employee->id, 'reward_type' => 'bonus', 'title' => "R{$i}", 'amount' => 100,
                'reward_date' => $day, 'status' => 'approved',
            ]);
            \App\Models\EmployeePenalty::create([
                'employee_id' => $employee->id, 'penalty_type' => 'days_deduction', 'days_count' => 1,
                'penalty_date' => $day, 'reason' => 'x', 'status' => 'approved',
            ]);
            \App\Models\Expense::create([
                'title' => "E{$i}", 'expense_category_id' => $category->id, 'branch_id' => $employee->branch_id,
                'employee_id' => $employee->id, 'amount' => 10, 'currency' => 'EGP', 'expense_date' => now()->startOfMonth()->addDays($i)->toDateString(),
                'payment_method' => 'cash',
            ]);
            $employee->documents()->create([
                'document_type' => 'other', 'title' => "D{$i}", 'file_path' => '/x.pdf', 'expiry_date' => now()->addDays(10 * $i)->toDateString(),
            ]);
            \App\Models\EmployeeSalary::create([
                'employee_id' => $employee->id, 'basic_salary' => 5000 + $i, 'allowances' => 0, 'currency' => 'EGP',
                'effective_date' => "202{$i}-01-01", 'change_reason' => 'annual_raise',
            ]);
        }

        $this->seedTasks($employee);

        // الفحص بيفتح أول مهمة في الجدول — الموظف بيتابع كل المهام عشان يشوفها
        foreach (\App\Models\Task::withoutGlobalScopes()->pluck('id') as $taskId) {
            \App\Models\TaskWatcher::firstOrCreate(['task_id' => $taskId, 'watcher_type' => Employee::class, 'watcher_id' => $employee->id]);
        }
        foreach (\App\Models\Project::withoutGlobalScopes()->get() as $p) {
            $p->members()->syncWithoutDetaching([$employee->id => ['role' => $p->manager_id === $employee->id ? 'manager' : 'viewer']]);
        }

        // قسيمتين مصروفين في مسيرين مختلفين (عشان علاقة payroll)
        foreach ([$payroll, \App\Models\Payroll::create([
            'title' => 'Crawl 2', 'year' => 2025, 'month' => 2, 'branch_id' => $employee->branch_id, 'status' => 'paid',
        ])] as $p) {
            \App\Models\PayrollItem::create([
                'payroll_id' => $p->id, 'employee_id' => $employee->id, 'basic_salary' => 5000,
                'allowances' => 0, 'deductions' => 0, 'status' => 'paid', 'paid_at' => now(),
            ]);
        }
    }

    /**
     * مهام بكل علاقاتها (مكلّفين، خطوات، فرعية، تصنيفات، تحديثات نسبة)
     * مكلّف بيها الموظف ده — عشان N+1 في قوائم وتفاصيل المهام يبان.
     */
    private function seedTasks(Employee $employee): void
    {
        $service = app(\App\Services\Tasks\TaskService::class);
        $colleagues = Employee::withoutGlobalScopes()->where('branch_id', $employee->branch_id)
            ->whereKeyNot($employee->id)->limit(2)->pluck('id')->all();
        $label = \App\Models\TaskLabel::create(['name' => 'Crawl', 'color' => '#7367f0']);
        $template = \App\Models\ProjectTemplate::firstOrCreate(['name' => 'Crawl template'], ['is_active' => true, 'structure' => [['title' => 'T1', 'checklist' => ['a']], ['title' => 'T2']]]);
        $project = app(\App\Services\Projects\ProjectService::class)->create([
            'branch_id' => $employee->branch_id, 'name' => 'Crawl project ' . $employee->id, 'manager_id' => $employee->id,
            'members' => array_fill_keys($colleagues, 'member'), 'template_id' => $template->id,
        ], $employee);

        $previous = null;

        // قواعد تكرار (شاشة المهام المتكررة)
        foreach (['weekly', 'monthly'] as $frequency) {
            app(\App\Services\Tasks\TaskRecurrenceService::class)->save([
                'title' => "Crawl {$frequency}", 'priority' => 'low', 'branch_id' => $employee->branch_id,
                'assignee_ids' => [$employee->id, ...$colleagues], 'frequency' => $frequency, 'interval' => 1,
                'weekdays' => [0, 3], 'month_day' => 10, 'run_time' => '08:00', 'duration_days' => 1, 'starts_on' => today()->toDateString(),
            ], $employee);
        }

        foreach ([1, 2, 3] as $i) {
            $task = $service->create([
                'title' => "Crawl task {$i}", 'branch_id' => $employee->branch_id, 'project_id' => $i === 1 ? $project->id : null,
                'assignee_ids' => [$employee->id, ...$colleagues], 'checklist' => ['a', 'b'], 'label_ids' => [$label->id],
                'due_at' => now()->addDays($i - 2)->toDateTimeString(), 'starts_at' => now()->subDays(3)->toDateTimeString(),
            ], $employee);

            $service->create(['title' => "Crawl sub {$i}", 'parent_id' => $task->id, 'assignee_ids' => [$employee->id]], $employee);
            $service->updateProgress($task, 20 * $i, $employee, 'note');
            $service->changeStatus($task->fresh(), \App\Enums\TaskStatus::InReview, $employee);
            \App\Models\TaskWatcher::create(['task_id' => $task->id, 'watcher_type' => Employee::class, 'watcher_id' => $employee->id]);

            // تعليقات بردود ومرفقات + إشعارات — عشان N+1 في النقاش يبان
            $collab = app(\App\Services\Tasks\TaskCollaborationService::class);
            $comment = $collab->addComment($task, "comment {$i}", $employee, null, $colleagues,
                [\Illuminate\Http\UploadedFile::fake()->create("c{$i}.pdf", 5, 'application/pdf')]);
            $collab->addComment($task, "reply {$i}", $employee, $comment->id);
            $collab->addComment($task, "second {$i}", $employee);
            $collab->addAttachments($task, [\Illuminate\Http\UploadedFile::fake()->image("p{$i}.png")], $employee);

            // ارتباط بالمهمة اللي قبلها (نفس الفرع، من غير مشروع)
            if ($previous && ! $task->project_id && ! $previous->project_id) {
                $service->addDependency($task, $previous->id, $employee);
            }
            $previous = $task;

            // وقت متسجّل (يدوي + عدّاد) — سجل الوقت في المهمة وصفحة سجل وقتي
            $time = app(\App\Services\Tasks\TaskTimeService::class);
            $time->addManual($task, $employee, 30 * $i, today(), "work {$i}");
            $time->start($task, $employee);
        }
    }

    /** صفحة 403 للضيف بتحوّل لتسجيل الدخول بدل ما توقع (كانت 500). */
    public function test_403_page_redirects_guests_to_login(): void
    {
        $this->get(route('admin.error_403'))->assertRedirect(route('admin.login'));
    }

    private function crawl(string $prefix, \Illuminate\Contracts\Auth\Authenticatable $user, string $guard): void
    {
        $report = [];
        $failures = [];

        /** @var Route $route */
        foreach (app('router')->getRoutes() as $route) {
            $name = $route->getName();
            if (! $name || ! str_starts_with($name, $prefix) || ! in_array('GET', $route->methods(), true)) {
                continue;
            }
            if (Str::contains($route->uri(), self::SKIP) || Str::contains($name, self::SKIP)) {
                continue;
            }

            $params = $this->paramsFor($route);
            if ($params === null) {
                $report[] = "SKIP (no data) {$name}";
                continue;
            }

            $url = route($name, $params);

            foreach (['page' => [], 'ajax' => ['X-Requested-With' => 'XMLHttpRequest']] as $kind => $headers) {
                // نفس المستخدم في كل طلب (الطلب اللي قبله ممكن يكون غيّر الجلسة)
                $this->actingAs($user, $guard);

                DB::flushQueryLog();
                DB::enableQueryLog();

                $response = null;
                try {
                    $response = $this->get($url, $headers);
                    $status = $response->getStatusCode();
                    $error = $response->exception?->getMessage();
                } catch (\Throwable $e) {
                    $status = 500;
                    $error = $e->getMessage();
                }

                $queries = count(DB::getQueryLog());
                DB::disableQueryLog();

                $report[] = sprintf('%3d q  %d  %-5s %s', $queries, $status, $kind, $name);

                // تحويل لصفحة الدخول = الفحص ماوصلش للصفحة أصلاً
                $location = (string) ($response?->headers->get('Location') ?? '');
                if ($status === 302 && str_contains($location, 'login')) {
                    $failures[] = "{$kind} {$name}: redirected to login";
                }

                if ($status >= 500) {
                    $failures[] = "{$kind} {$name} [{$status}]: " . Str::limit((string) $error, 300);
                } elseif ($queries > self::MAX_QUERIES) {
                    $failures[] = "{$kind} {$name}: {$queries} queries (> " . self::MAX_QUERIES . ')';
                }

            }
        }

        file_put_contents(storage_path('logs/n1-crawl-' . trim($prefix, '.') . '.txt'), implode("\n", $report));

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    /**
     * قيم بارامترات المسار من أول سجل في الجدول المناسب.
     * null = مفيش بيانات تنفع (المسار بيتساب).
     */
    private function paramsFor(Route $route): ?array
    {
        $params = [];

        foreach ($route->parameterNames() as $param) {
            $table = match ($param) {
                'id', 'item'      => $this->tableFromUri($route->uri()),
                'job_title'       => 'job_titles',
                'advance'         => 'employee_advances',
                'expense_category' => 'expense_categories',
                'customer_source' => 'customer_sources',
                'sales_channel'   => 'sales_channels',
                'vendor_subscription', 'subscription' => 'vendor_subscriptions',
                'recurrence'      => 'task_recurrences',
                'group'           => null,
                default           => Str::plural(Str::snake($param)),
            };

            if ($param === 'group') {
                $params[$param] = 'general';
                continue;
            }

            // نوع سجل الحركات: مفتاح من config/activity.php
            if ($param === 'type' && str_contains($route->uri(), 'activity-logs')) {
                $params[$param] = array_key_first(config('activity.subjects', []));
                continue;
            }

            if (! $table || ! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                return null;
            }

            $value = DB::table($table)->when(\Illuminate\Support\Facades\Schema::hasColumn($table, 'deleted_at'),
                fn ($q) => str_contains($route->uri(), 'trash') ? $q : $q->whereNull('deleted_at'))->min('id');

            if ($value === null) {
                return null;
            }

            $params[$param] = $value;
        }

        return $params;
    }

    /** {id} في مسار زي admin/leaves/{id}/... → جدول employee_leaves. */
    private function tableFromUri(string $uri): ?string
    {
        $segment = collect(explode('/', $uri))->reject(fn ($s) => str_starts_with($s, '{') || in_array($s, ['admin', 'portal'], true))->first();

        $map = [
            'leaves' => 'employee_leaves', 'permissions' => 'employee_permissions', 'advances' => 'employee_advances',
            'rewards' => 'employee_rewards', 'penalties' => 'employee_penalties', 'payslips' => 'payroll_items',
            'job-titles' => 'job_titles', 'expense-categories' => 'expense_categories',
            'customer-sources' => 'customer_sources', 'sales-channels' => 'sales_channels',
            'vendor-subscriptions' => 'vendor_subscriptions', 'activity-logs' => 'activity_logs',
            'salaries' => 'employee_salaries', 'documents' => 'employee_documents', 'skills' => 'employee_skills',
        ];

        return $map[$segment] ?? ($segment ? Str::snake(str_replace('-', '_', $segment)) : null);
    }
}
