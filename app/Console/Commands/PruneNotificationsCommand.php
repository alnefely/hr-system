<?php

namespace App\Console\Commands;

use App\Models\AdminNotification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * حذف الإشعارات القديمة.
 *
 * قاعدتين (المدد من config/notifications.php):
 *   - المقروء: بعد read_retention_days (افتراضي ٣٠ يوم)
 *   - أي إشعار حتى لو ماتقراش: بعد retention_days (افتراضي ٩٠ يوم)
 *
 *   php artisan notifications:prune
 *   php artisan notifications:prune --read-days=15 --days=60
 *   php artisan notifications:prune --dry-run
 */
class PruneNotificationsCommand extends Command
{
    protected $signature = 'notifications:prune
        {--read-days= : الاحتفاظ بالمقروء كام يوم (الافتراضي من config/notifications.php)}
        {--days= : الاحتفاظ بأي إشعار كام يوم (الافتراضي من config/notifications.php)}
        {--dry-run : اعرض العدد من غير ما تحذف}';

    protected $description = 'حذف الإشعارات المقروءة والقديمة حسب مدة الاحتفاظ';

    public function handle(): int
    {
        $readDays = (int) ($this->option('read-days') ?? config('notifications.read_retention_days', 30));
        $allDays  = (int) ($this->option('days') ?? config('notifications.retention_days', 90));

        if ($readDays < 1 || $allDays < 1) {
            $this->error('عدد الأيام لازم يكون 1 على الأقل.');

            return self::FAILURE;
        }

        $query = $this->prunable($readDays, $allDays);
        $count = (clone $query)->count();

        $this->line("المقروء أقدم من <fg=cyan>{$readDays}</> يوم · أي إشعار أقدم من <fg=cyan>{$allDays}</> يوم");

        if ($count === 0) {
            $this->info('مفيش إشعارات قديمة محتاجة حذف. ✔');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("هيتحذف {$count} إشعار (معاينة — ماتحذفش حاجة).");

            return self::SUCCESS;
        }

        // على دفعات عشان الجدول مايتقفلش وقت طويل
        $total = 0;

        do {
            $ids = $this->prunable($readDays, $allDays)->limit(1000)->pluck('id');
            $total += $ids->isEmpty() ? 0 : AdminNotification::whereIn('id', $ids)->delete();
        } while ($ids->isNotEmpty());

        $this->info("تم حذف {$total} إشعار. ✔");

        return self::SUCCESS;
    }

    private function prunable(int $readDays, int $allDays): Builder
    {
        $readCutoff = now()->subDays($readDays);
        $allCutoff  = now()->subDays($allDays);

        return AdminNotification::query()->where(function (Builder $q) use ($readCutoff, $allCutoff) {
            $q->where(fn (Builder $r) => $r->whereNotNull('read_at')->where('created_at', '<', $readCutoff))
              ->orWhere('created_at', '<', $allCutoff);
        });
    }
}
