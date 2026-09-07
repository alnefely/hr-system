<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * تنظيف سجل الحركات القديم.
 *
 *   php artisan activity:prune                  # المدة الافتراضية من config
 *   php artisan activity:prune --days=180       # آخر 180 يوم بس
 *   php artisan activity:prune --days=30 --dry-run
 *   php artisan activity:prune --subject="App\Models\Sale"
 *   php artisan activity:prune --days=90 --force   # من غير تأكيد (للـ scheduler)
 */
class PruneActivityLogsCommand extends Command
{
    protected $signature = 'activity:prune
        {--days= : عدد الأيام اللي نحتفظ بيها (الافتراضي من config/activity.php)}
        {--subject= : تنظيف موديول واحد بس (اسم الكلاس الكامل)}
        {--dry-run : اعرض اللي هيتحذف من غير ما تحذف فعلاً}
        {--force : نفّذ من غير سؤال تأكيد}';

    protected $description = 'حذف سجلات الحركات الأقدم من مدة الاحتفاظ المحددة';

    public function handle(): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) config('activity.retention_days', 90);

        if ($days < 1) {
            $this->error('عدد الأيام لازم يكون 1 على الأقل.');

            return self::FAILURE;
        }

        $cutoff = Carbon::now()->subDays($days)->startOfDay();
        $subject = $this->option('subject');

        if ($subject && !array_key_exists($subject, config('activity.subjects', []))) {
            $this->error("الموديول [{$subject}] مش مسجّل في config/activity.php");
            $this->line('الموديولات المتاحة:');
            foreach (array_keys(config('activity.subjects', [])) as $class) {
                $this->line("  - {$class}");
            }

            return self::FAILURE;
        }

        $query = ActivityLog::where('created_at', '<', $cutoff)
            ->when($subject, fn ($q) => $q->where('subject_type', $subject));

        $count = (clone $query)->count();

        $this->newLine();
        $this->line("مدة الاحتفاظ : <fg=cyan>{$days}</> يوم");
        $this->line('تاريخ القطع  : <fg=cyan>' . $cutoff->format('Y-m-d H:i') . '</>');
        $this->line('الموديول     : <fg=cyan>' . ($subject ?: 'كل الموديولات') . '</>');
        $this->line("المستهدف     : <fg=yellow>{$count}</> سجل");
        $this->newLine();

        if ($count === 0) {
            $this->info('مفيش سجلات قديمة تتحذف. ✔');

            return self::SUCCESS;
        }

        // نظرة سريعة على التوزيع قبل الحذف
        $breakdown = (clone $query)
            ->selectRaw('subject_type, COUNT(*) as total')
            ->groupBy('subject_type')
            ->orderByDesc('total')
            ->pluck('total', 'subject_type');

        if ($breakdown->isNotEmpty()) {
            $this->table(
                ['الموديول', 'عدد السجلات'],
                $breakdown->map(fn ($total, $class) => [
                    activity_subject_label($class),
                    $total,
                ])->values()->all()
            );
        }

        if ($this->option('dry-run')) {
            $this->warn('وضع المعاينة (--dry-run): مفيش حاجة اتحذفت فعلاً.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm("متأكد إنك عايز تحذف {$count} سجل نهائياً؟", false)) {
            $this->info('اتلغت العملية.');

            return self::SUCCESS;
        }

        // الحذف على دفعات عشان مانقفلش الجدول ولا نستهلك ذاكرة كبيرة
        $deleted = 0;
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        do {
            $batch = ActivityLog::where('created_at', '<', $cutoff)
                ->when($subject, fn ($q) => $q->where('subject_type', $subject))
                ->limit(1000)
                ->delete();

            $deleted += $batch;
            $bar->advance($batch);
        } while ($batch > 0);

        $bar->finish();
        $this->newLine(2);
        $this->info("تم حذف {$deleted} سجل من سجل الحركات. ✔");

        return self::SUCCESS;
    }
}
