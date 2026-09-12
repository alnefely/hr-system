<?php

namespace App\Console\Commands;

use App\Models\LinkClick;
use Illuminate\Console\Command;

/**
 * حذف نقرات الروابط القديمة.
 *
 * جدول النقرات بيكبر بسرعة (صف لكل زيارة)، والتفاصيل الخام
 * مالهاش قيمة بعد فترة — الأرشيف الدائم بيبقى في الجدول التجميعي
 * link_daily_stats (المرحلة ٣).
 *
 *   php artisan links:prune
 *   php artisan links:prune --days=30
 */
class PruneLinkClicksCommand extends Command
{
    protected $signature = 'links:prune
        {--days= : عدد أيام الاحتفاظ (الافتراضي من config/links.php)}';

    protected $description = 'حذف نقرات الروابط الأقدم من مدة الاحتفاظ';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('links.clicks_retention', 90));

        if ($days < 1) {
            $this->error('مدة الاحتفاظ لازم تكون يوم على الأقل.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days)->startOfDay();

        $this->line('حذف النقرات الأقدم من <fg=cyan>' . $cutoff->toDateString() . '</>...');

        // على دفعات: DELETE على ملايين الصفوف مرة واحدة بيقفل
        // الجدول ويكبّر ملف الـ transaction.
        $total = 0;

        do {
            $deleted = LinkClick::where('clicked_at', '<', $cutoff)->limit(5000)->delete();
            $total += $deleted;
        } while ($deleted > 0);

        if ($total === 0) {
            $this->info('مفيش نقرات قديمة محتاجة حذف. ✔');

            return self::SUCCESS;
        }

        $this->info("تم حذف {$total} نقرة قديمة. ✔");

        return self::SUCCESS;
    }
}
