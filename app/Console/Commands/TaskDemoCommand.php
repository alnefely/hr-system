<?php

namespace App\Console\Commands;

use App\Enums\ProjectMemberRole;
use App\Enums\TaskStatus;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Task;
use App\Models\TaskLabel;
use App\Models\TaskWatcher;
use App\Services\Projects\ProjectService;
use App\Services\Tasks\TaskCollaborationService;
use App\Services\Tasks\TaskRecurrenceService;
use App\Services\Tasks\TaskReminderService;
use App\Services\Tasks\TaskService;
use App\Services\Tasks\TaskTimeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * بيانات تجربة لموديول المهام — للبيئة المحلية بس (بيرفض لو APP_ENV مش local).
 *
 * ---------------------------------------------------------------------------
 *  الاستخدام
 * ---------------------------------------------------------------------------
 *   php artisan tasks:demo           إنشاء (لو موجودة بيرفض — استخدم --fresh)
 *   php artisan tasks:demo --fresh   مسح القديم وإنشاء من جديد (بعد ما تبوّظ حاجة وانت بتجرب)
 *   php artisan tasks:demo --clean   مسح بيانات التجربة بس
 *
 * محتاج: مشرف واحد على الأقل بصلاحيات المهام (task_update و task_review)،
 * وفرع ومسمى وظيفي. بيستخدم دور "صلاحية كاملة" لو موجود.
 *
 * ---------------------------------------------------------------------------
 *  دخول البوابة — كلمة المرور لكلهم: password (من غير إجبار تغييرها)
 *  الرقم بيتكتب بالصيغة المحلية، مثال 01099900001
 * ---------------------------------------------------------------------------
 *   01099900001  منى (المديرة)  مدير مباشر للباقيين ومديرة مشروع الموقع:
 *                               "مهام فريقي"، "للمراجعة" (مهمتين)، أداء الفريق
 *   01099900002  أحمد           مهمة بخطوات (نسبة تلقائية)، مهمة متأخرة، عدّاد شغّال،
 *                               مهمة شخصية مستنية مراجعة مديره، مدير مشروع الحملة
 *   01099900003  بسمة           قرب التسليم، تذكير تحديث النسبة، مهمة مستنية مهمة تانية،
 *                               إشارة (@) ليها في تعليق
 *   01099900004  كريم           مهمة رجعت للتعديل بسبب، اعتذار عن مهمة،
 *                               مشاهد بس في المشروع، متابع لمهمة
 *
 * لوحة الإدارة: بحساب المشرف العادي — المهام، اللوحة، التقويم، المتكررة،
 * التقارير، المشاريع والقوالب.
 *
 * ---------------------------------------------------------------------------
 *  اللي بيتعمل
 * ---------------------------------------------------------------------------
 *   - ~23 مهمة في كل الحالات (جديدة، قيد التنفيذ، قيد المراجعة، مكتملة، ملغية):
 *     نسبة من الخطوات، ونسبة من المهام الفرعية، ونسبة يدوي بتحديث يومي،
 *     متأخرة، قرب التسليم، بدون موعد، بدون مكلّفين، مستنية مهمة تانية،
 *     مراجعة معتمدة وأخرى راجعة بسبب، اعتذار، تعليقات برد وإشارة، وقت يدوي.
 *   - مشروعين: واحد عادي بأعضاء بأدوار مختلفة، والتاني من قالب (مهامه اتعملت تلقائي).
 *   - قالب مشروع، و3 تصنيفات.
 *   - 3 مهام متكررة (يومية وأسبوعية ومتوقفة) — اليومية بتعمل مهمة فوراً.
 *   - بيشغّل الأتمتة مرة: المتكررة + تذكيرات قرب التسليم والتأخير وتحديث النسبة.
 *
 * بيبني كل حاجة عن طريق الخدمات الحقيقية (مش insert مباشر)، فالإشعارات
 * والخط الزمني والعدادات والمراجعات بتطلع زي الاستخدام الفعلي بالظبط.
 *
 * ---------------------------------------------------------------------------
 *  التعليم والمسح
 * ---------------------------------------------------------------------------
 * كل بيانات التجربة معلّمة عشان تتمسح من غير ما تلمس بياناتك:
 *   المهام والمشاريع والقوالب والمتكررة ← عنوانها بيبدأ بـ "[تجربة]"
 *   التصنيفات ← "تجربة: ..."   ·   الموظفين ← كود DEMO-xx (هواتف +2010999000xx)
 * المسح بيشيل كمان كل المرتبط بيهم (التكليفات، الوقت، التعليقات، الإشعارات)
 * عن طريق الـ cascade — ماتسمّيش مهمة حقيقية بـ "[تجربة]".
 */
class TaskDemoCommand extends Command
{
    protected $signature = 'tasks:demo {--fresh : مسح بيانات التجربة القديمة الأول} {--clean : مسح بيانات التجربة بس}';

    protected $description = 'بيانات تجربة كاملة لموديول المهام (البيئة المحلية فقط)';

    private const PREFIX = '[تجربة] ';
    private const LABEL_PREFIX = 'تجربة: ';
    private const CODE_PREFIX = 'DEMO-';
    private const PASSWORD = 'password';

    private Branch $branch;

    public function handle(
        TaskService $tasks,
        ProjectService $projects,
        TaskCollaborationService $collab,
        TaskTimeService $time,
        TaskRecurrenceService $recurrences,
        TaskReminderService $reminders,
    ): int {
        if (! app()->environment('local')) {
            $this->error('الأمر ده للبيئة المحلية بس (APP_ENV=local).');

            return self::FAILURE;
        }

        if ($this->option('clean') || $this->option('fresh')) {
            $this->clean();

            if ($this->option('clean')) {
                return self::SUCCESS;
            }
        } elseif (Employee::withoutGlobalScopes()->where('employee_code', 'like', self::CODE_PREFIX . '%')->exists()) {
            $this->warn('بيانات التجربة موجودة بالفعل — استخدم --fresh لإعادة إنشائها أو --clean لمسحها.');

            return self::FAILURE;
        }

        $admin = Admin::query()->where('role_id', 1)->first() ?? Admin::first();
        $branch = Branch::query()->first();
        $jobTitle = JobTitle::query()->first();

        if (! $admin || ! $branch || ! $jobTitle) {
            $this->error('محتاج مشرف وفرع ومسمى وظيفي على الأقل (php artisan db:seed).');

            return self::FAILURE;
        }

        // الخدمات بتفحص صلاحيات المشرف من الجلسة
        auth('admin')->setUser($admin);
        $this->branch = $branch;

        if (! admin_can('task_update') || ! admin_can('task_review')) {
            $this->error("المشرف «{$admin->name}» مالوش صلاحيات المهام — فعّلها لدوره الأول.");

            return self::FAILURE;
        }

        // ============ الموظفين: مدير + 3 في فريقه ============
        $m = $this->employee(1, 'مدير التجربة (منى)', null, $jobTitle->id);
        $a = $this->employee(2, 'أحمد التجربة', $m->id, $jobTitle->id);
        $b = $this->employee(3, 'بسمة التجربة', $m->id, $jobTitle->id);
        $c = $this->employee(4, 'كريم التجربة', $m->id, $jobTitle->id);

        // ============ التصنيفات ============
        $labels = collect([['واجهة', '#7367f0'], ['محتوى', '#00bad1'], ['عاجل للعميل', '#ff4c51']])
            ->map(fn ($l) => TaskLabel::create(['branch_id' => $branch->id, 'name' => self::LABEL_PREFIX . $l[0], 'color' => $l[1]]))
            ->pluck('id')->all();

        // ============ المشاريع ============
        $site = $projects->create([
            'name'        => self::PREFIX . 'إطلاق الموقع الجديد',
            'branch_id'   => $branch->id,
            'description' => 'مشروع تجربة: مدير المشروع منى، أحمد وبسمة أعضاء، كريم مشاهد.',
            'color'       => '#7367f0',
            'manager_id'  => $m->id,
            'status'      => 'active',
            'priority'    => 'high',
            'start_date'  => now()->subDays(10)->toDateString(),
            'due_date'    => now()->addDays(20)->toDateString(),
            'members'     => [$a->id => ProjectMemberRole::Member->value, $b->id => ProjectMemberRole::Member->value, $c->id => ProjectMemberRole::Viewer->value],
        ], $admin);

        $template = ProjectTemplate::create([
            'branch_id'    => $branch->id,
            'name'         => self::PREFIX . 'قالب حملة تسويقية',
            'description'  => 'قالب تجربة بمهام ومهام فرعية وخطوات.',
            'structure'    => [
                ['title' => self::PREFIX . 'تحديد الجمهور المستهدف', 'priority' => 'high', 'start_offset_days' => 0, 'due_offset_days' => 3, 'checklist' => ['تحليل العملاء الحاليين', 'تحديد الشرائح']],
                ['title' => self::PREFIX . 'تصميم الإعلانات', 'priority' => 'medium', 'start_offset_days' => 3, 'due_offset_days' => 8, 'requires_review' => true,
                    'subtasks' => [['title' => self::PREFIX . 'تصميم بوستات السوشيال', 'due_offset_days' => 6], ['title' => self::PREFIX . 'فيديو قصير', 'due_offset_days' => 8]]],
                ['title' => self::PREFIX . 'إطلاق الحملة وقياس النتائج', 'priority' => 'urgent', 'start_offset_days' => 8, 'due_offset_days' => 14],
            ],
            'is_active'    => true,
            'creator_type' => Admin::class,
            'creator_id'   => $admin->id,
        ]);

        $campaign = $projects->create([
            'name'        => self::PREFIX . 'حملة نهاية السنة (من قالب)',
            'branch_id'   => $branch->id,
            'color'       => '#ff9f43',
            'manager_id'  => $a->id,
            'status'      => 'planning',
            'priority'    => 'medium',
            'start_date'  => now()->toDateString(),
            'due_date'    => now()->addDays(14)->toDateString(),
            'members'     => [$b->id => ProjectMemberRole::Member->value],
            'template_id' => $template->id,
        ], $admin);

        // ============ المهام ============
        $new = fn (array $data, $actor = null) => $tasks->create($data + [
            'branch_id' => $branch->id, 'priority' => 'medium', 'progress_source' => 'manual',
            'progress_update_interval' => 'none', 'requires_review' => false,
        ], $actor ?? $admin);

        // 1) خطوات (نسبة تلقائية) + تعليقات وإشارة + وقت
        $design = $new([
            'title' => self::PREFIX . 'تصميم الصفحة الرئيسية', 'project_id' => $site->id, 'priority' => 'high',
            'description' => 'النسبة بتتحسب من الخطوات. علّم على خطوة وشوف النسبة بتتغير.',
            'starts_at' => now()->subDays(5)->toDateTimeString(), 'due_at' => now()->addDays(5)->toDateTimeString(),
            'estimated_minutes' => 600, 'requires_review' => true, 'progress_source' => 'checklist',
            'assignee_ids' => [$a->id], 'label_ids' => [$labels[0]],
            'checklist' => ['الهيكل العام (wireframe)', 'التصميم النهائي', 'نسخة الموبايل', 'مراجعة الألوان'],
        ]);
        $tasks->changeStatus($design, TaskStatus::InProgress, $a);
        foreach ($design->checklistItems()->orderBy('position')->take(2)->get() as $item) {
            $tasks->toggleChecklistItem($design->fresh(), $item, $a);
        }
        $first = $collab->addComment($design, 'ابدأ بالهيكل العام وابعتهولي قبل الألوان.', $admin);
        $collab->addComment($design, 'تمام، الهيكل خلص — بسمة محتاجين المحتوى النهائي للصفحة.', $a, $first->id, [$b->id]);
        $time->addManual($design, $a, 90, now()->subDay(), 'الهيكل العام');

        // 2) قرب التسليم + نسبة يدوي + تحديث يومي + متابع
        $content = $new([
            'title' => self::PREFIX . 'كتابة محتوى صفحة من نحن', 'project_id' => $site->id,
            'starts_at' => now()->subDays(3)->toDateTimeString(), 'due_at' => now()->addHours(10)->toDateTimeString(),
            'estimated_minutes' => 240, 'progress_update_interval' => 'daily',
            'assignee_ids' => [$b->id], 'label_ids' => [$labels[1]],
        ]);
        $tasks->updateProgress($content, 40, $b, 'خلصت المقدمة والرؤية.');
        $time->addManual($content, $b, 120, now()->subDays(2), 'مسودة أولى');
        TaskWatcher::create(['task_id' => $content->id, 'watcher_type' => Employee::class, 'watcher_id' => $c->id]);

        // 3) متأخرة ومتأخرة عن الخطة + عدّاد شغّال
        $security = $new([
            'title' => self::PREFIX . 'مراجعة أمان الموقع', 'project_id' => $site->id, 'priority' => 'urgent',
            'starts_at' => now()->subDays(10)->toDateTimeString(), 'due_at' => now()->subDays(2)->toDateTimeString(),
            'estimated_minutes' => 480, 'assignee_ids' => [$a->id, $b->id], 'label_ids' => [$labels[2]],
        ]);
        $tasks->updateProgress($security, 30, $a, 'فحص صفحات الدخول.');
        $time->start($security, $a);

        // 4) المدير المباشر عمل المهمة وأحمد سلّمها ← عند منى في "للمراجعة"
        $report = $new([
            'title' => self::PREFIX . 'تقرير المبيعات الأسبوعي', 'requires_review' => true,
            'due_at' => now()->addDays(2)->toDateTimeString(), 'assignee_ids' => [$a->id],
        ], $m);
        $tasks->updateProgress($report, 100, $a);
        $tasks->changeStatus($report, TaskStatus::InReview, $a, 'التقرير جاهز، الأرقام من النظام.');

        // 5) اتسلّمت ورجعت للتعديل بسبب
        $quote = $new([
            'title' => self::PREFIX . 'تجهيز عرض سعر لعميل', 'requires_review' => true, 'priority' => 'high',
            'due_at' => now()->addDays(3)->toDateTimeString(), 'assignee_ids' => [$c->id],
        ]);
        $tasks->changeStatus($quote, TaskStatus::InReview, $c, 'العرض المبدئي.');
        $tasks->changeStatus($quote->fresh(), TaskStatus::InProgress, $admin, 'الأسعار محتاجة تتحدّث بأسعار الشهر ده.');

        // 6) اتسلّمت واتعتمدت
        $crm = $new([
            'title' => self::PREFIX . 'تحديث بيانات العملاء', 'requires_review' => true,
            'starts_at' => now()->subDays(8)->toDateTimeString(), 'due_at' => now()->subDays(1)->toDateTimeString(),
            'estimated_minutes' => 180, 'assignee_ids' => [$b->id],
        ]);
        $time->addManual($crm, $b, 200, now()->subDays(3));
        $tasks->changeStatus($crm, TaskStatus::InReview, $b);
        $tasks->changeStatus($crm->fresh(), TaskStatus::Completed, $admin, 'ممتاز.');

        // 7) ملغية
        $cancelled = $new(['title' => self::PREFIX . 'طباعة بروشورات (اتلغت)', 'priority' => 'low', 'assignee_ids' => [$a->id]]);
        $tasks->changeStatus($cancelled, TaskStatus::Cancelled, $admin, 'اتلغت الميزانية.');

        // 8) أحمد عملها لنفسه ← المراجع مديره المباشر
        $self = $new([
            'title' => self::PREFIX . 'تنظيم ملفات المشروع (مهمة شخصية)', 'requires_review' => true,
            'assignee_ids' => [$a->id], 'checklist' => ['ترتيب الفولدرات', 'أرشفة القديم'],
        ], $a);
        $tasks->changeStatus($self, TaskStatus::InReview, $a, 'خلصت التنظيم.');

        // 9) مستنية مهمة تانية (مقفولة لحد ما التصميم يخلص)
        $qa = $new([
            'title' => self::PREFIX . 'اختبار الموقع قبل الإطلاق', 'project_id' => $site->id,
            'due_at' => now()->addDays(12)->toDateTimeString(), 'assignee_ids' => [$b->id],
        ]);
        $tasks->addDependency($qa, $design->id, $admin);

        // 10) نسبة من المهام الفرعية
        $ads = $new([
            'title' => self::PREFIX . 'تجهيز الحملة الإعلانية', 'progress_source' => 'subtasks',
            'due_at' => now()->addDays(7)->toDateTimeString(), 'assignee_ids' => [$m->id],
        ]);
        $subA = $new(['title' => self::PREFIX . 'كتابة نصوص الإعلانات', 'parent_id' => $ads->id, 'assignee_ids' => [$a->id]]);
        $tasks->changeStatus($subA, TaskStatus::Completed, $a);
        $subB = $new(['title' => self::PREFIX . 'تصميم البانرات', 'parent_id' => $ads->id, 'assignee_ids' => [$b->id]]);
        $tasks->updateProgress($subB, 50, $b);
        $new(['title' => self::PREFIX . 'حجز المساحات الإعلانية', 'parent_id' => $ads->id, 'assignee_ids' => [$c->id]]);

        // 11) جديدة بدون موعد لأكتر من موظف
        $new(['title' => self::PREFIX . 'أفكار لتحسين الأداء', 'priority' => 'low', 'assignee_ids' => [$a->id, $b->id, $c->id], 'label_ids' => [$labels[1]]]);

        // 12) كريم اعتذر
        $translate = $new(['title' => self::PREFIX . 'ترجمة الكتالوج', 'due_at' => now()->addDays(4)->toDateTimeString(), 'assignee_ids' => [$c->id]]);
        $tasks->decline($translate, $c, 'مش متاح الأسبوع ده — عندي إجازة.');

        // 13) من غير مكلّفين
        $new(['title' => self::PREFIX . 'مهمة لسه محدش اتكلّف بيها', 'project_id' => $site->id, 'due_at' => now()->addDays(6)->toDateTimeString()]);

        // 14) فات ميعاد تحديث النسبة ← تذكير
        $weekly = $new([
            'title' => self::PREFIX . 'متابعة طلبات الموردين', 'progress_update_interval' => 'daily',
            'starts_at' => now()->subDays(4)->toDateTimeString(), 'due_at' => now()->addDays(10)->toDateTimeString(),
            'assignee_ids' => [$b->id],
        ]);
        $weekly->forceFill(['next_progress_update_at' => now()->subHour()])->saveQuietly();

        // ============ المهام المتكررة ============
        // ميعادها الجاي بيتقدّم دقيقة لورا عشان runDue تحت يعمل منها مهمة دلوقتي
        $daily = $recurrences->save([
            'title' => self::PREFIX . 'تقرير الحضور اليومي', 'priority' => 'medium', 'assignee_ids' => [$c->id],
            'checklist' => ['مراجعة البصمة', 'إرسال التقرير'], 'requires_review' => false,
            'frequency' => 'daily', 'interval' => 1, 'duration_days' => 0, 'run_time' => '00:00',
            'starts_on' => now()->toDateString(), 'branch_id' => $branch->id,
        ], $admin);
        $daily->forceFill(['next_run_at' => now()->subMinute()])->saveQuietly();
        $recurrences->save([
            'title' => self::PREFIX . 'اجتماع متابعة الفريق', 'priority' => 'high', 'assignee_ids' => [$a->id, $b->id],
            'frequency' => 'weekly', 'interval' => 1, 'weekdays' => [0, 3], 'duration_days' => 1, 'run_time' => '09:00',
            'starts_on' => now()->toDateString(), 'branch_id' => $branch->id,
        ], $admin);
        $recurrences->save([
            'title' => self::PREFIX . 'جرد شهري (متوقفة)', 'priority' => 'low', 'assignee_ids' => [$m->id],
            'frequency' => 'monthly', 'interval' => 1, 'month_day' => 1, 'duration_days' => 3, 'run_time' => '08:00',
            'starts_on' => now()->toDateString(), 'branch_id' => $branch->id, 'is_active' => false,
        ], $admin);

        // ============ الأتمتة: المتكررة اللي ميعادها جه + التذكيرات ============
        $created = $recurrences->runDue();
        $sent = $reminders->sendDueSoon() + $reminders->sendOverdue() + $reminders->sendProgressDue();

        $this->summary($m, $a, $b, $c, $site, $campaign, $created, $sent);

        return self::SUCCESS;
    }

    private function employee(int $n, string $name, ?int $managerId, int $jobTitleId): Employee
    {
        $employee = new Employee;
        $employee->forceFill([
            'employee_code'        => self::CODE_PREFIX . str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            'name'                 => $name,
            'phone'                => '+2010999000' . str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            'branch_id'            => $this->branch->id,
            'job_title_id'         => $jobTitleId,
            'manager_id'           => $managerId,
            'hire_date'            => now()->subYear()->toDateString(),
            'status'               => 'active',
            'password'             => self::PASSWORD,
            'portal_enabled'       => true,
            'must_change_password' => false,
        ])->save();

        return $employee;
    }

    private function clean(): void
    {
        $like = self::PREFIX . '%';

        DB::transaction(function () use ($like) {
            $tasks = DB::table('tasks')->where('title', 'like', $like)->delete();
            $projects = DB::table('projects')->where('name', 'like', $like)->delete();
            DB::table('project_templates')->where('name', 'like', $like)->delete();
            DB::table('task_recurrences')->where('blueprint->title', 'like', $like)->delete();
            DB::table('task_labels')->where('name', 'like', self::LABEL_PREFIX . '%')->delete();
            DB::table('admin_notifications')->where('body', 'like', $like)->delete();
            $employees = DB::table('employees')->where('employee_code', 'like', self::CODE_PREFIX . '%')->delete();

            $this->info("اتمسح: {$tasks} مهمة، {$projects} مشروع، {$employees} موظف تجربة (والباقي المرتبط بيهم).");
        });
    }

    private function summary(Employee $m, Employee $a, Employee $b, Employee $c, Project $site, Project $campaign, int $created, int $sent): void
    {
        $count = Task::withoutGlobalScopes()->where('title', 'like', self::PREFIX . '%')->count();

        $this->newLine();
        $this->info("✓ {$count} مهمة تجربة · مشروعين ({$site->code}، {$campaign->code}) · قالب · 3 متكررة · {$created} مهمة من المتكررة · {$sent} تذكير");
        $this->newLine();
        $this->line('دخول البوابة (كلمة المرور لكلهم: ' . self::PASSWORD . '):');
        $this->table(['الموظف', 'الهاتف', 'هتلاقي إيه'], [
            [$m->name, $m->phone, 'مدير مباشر للباقيين + مدير المشروع: تبويب "مهام فريقي" و"للمراجعة" (مهمتين)، وأداء الفريق'],
            [$a->name, $a->phone, 'مهام بخطوات، متأخرة، عدّاد شغّال، مهمة شخصية مسلّمة، مدير مشروع الحملة'],
            [$b->name, $b->phone, 'قرب التسليم، تذكير تحديث النسبة، مهمة مستنية مهمة تانية، إشارة في تعليق'],
            [$c->name, $c->phone, 'مهمة رجعت للتعديل بسبب، اعتذار، مشاهد في المشروع، متابع لمهمة'],
        ]);
        $this->line('لوحة الإدارة: بنفس حساب المشرف — المهام، اللوحة، التقويم، المتكررة، التقارير، المشاريع والقوالب.');
        $this->line('للمسح: php artisan tasks:demo --clean   ·   لإعادة الإنشاء: php artisan tasks:demo --fresh');
    }
}
