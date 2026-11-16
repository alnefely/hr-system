<?php

namespace App\Console\Commands;

use App\Models\LinkClick;
use App\Models\LinkDailyStat;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * تلخيص نقرات اليوم في صف واحد لكل رابط.
 *
 * الشاشات بتقرا من link_daily_stats بس — العدّ المباشر على
 * link_clicks بينهار بعد ملايين الصفوف.
 *
 * مفيش Jobs: أمر مجدول زي باقي المرحلة.
 *
 *   php artisan links:aggregate                # امبارح
 *   php artisan links:aggregate --date=2026-09-01
 *   php artisan links:aggregate --days=7       # آخر ٧ أيام
 */
class AggregateLinkStatsCommand extends Command
{
    protected $signature = 'links:aggregate
        {--date= : يوم محدد (Y-m-d)}
        {--days= : عدد الأيام للخلف من امبارح}';

    protected $description = 'تلخيص نقرات الروابط في الجدول التجميعي اليومي';

    public function handle(): int
    {
        $dates = $this->resolveDates();

        if ($dates === []) {
            $this->error('تاريخ غير صالح.');

            return self::FAILURE;
        }

        $totalRows = 0;

        foreach ($dates as $date) {
            $rows = $this->aggregateDay($date);
            $totalRows += $rows;

            $this->line(sprintf(
                '<fg=cyan>%s</> — %d رابط',
                $date->toDateString(),
                $rows
            ));
        }

        $this->info("تم تلخيص {$totalRows} صف. ✔");

        return self::SUCCESS;
    }

    /**
     * الأيام المطلوب تلخيصها.
     *
     * الافتراضي: النهارده وامبارح.
     *
     * اليوم الجاري بيتلخّص عادي لأن الأمر بيشتغل كل ٥ دقايق،
     * و updateOrCreate بيصحّح صف النهارده في كل تشغيلة بدل ما
     * يكرره — فالرقم بيتحدّث باستمرار لحد ما اليوم يخلص.
     *
     * وامبارح معاه لأن نقرة الساعة 11:59 ممكن تتفرّغ من Redis بعد
     * منتصف الليل، فتشغيلة أول النهار الجديد لازم تصحّح صف امبارح.
     */
    private function resolveDates(): array
    {
        if ($date = $this->option('date')) {
            try {
                return [Carbon::parse($date)->startOfDay()];
            } catch (\Throwable $e) {
                return [];
            }
        }

        $days = (int) ($this->option('days') ?: 2);

        if ($days < 1) {
            return [];
        }

        $dates = [];

        // من النهارده ورا: --days=1 يعني النهارده بس
        for ($i = 0; $i < $days; $i++) {
            $dates[] = now()->subDays($i)->startOfDay();
        }

        return array_reverse($dates);
    }

    /**
     * تلخيص يوم واحد.
     *
     * بيرجع عدد الروابط اللي اتلخّصت.
     */
    private function aggregateDay(Carbon $date): int
    {
        $start = $date->copy()->startOfDay();
        $end   = $date->copy()->endOfDay();

        // الأرقام الأساسية لكل رابط في استعلام واحد
        $totals = LinkClick::query()
            ->whereBetween('clicked_at', [$start, $end])
            ->groupBy('link_id')
            ->select([
                'link_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) as bots'),
                DB::raw('COUNT(DISTINCT CASE WHEN is_bot = 0 THEN visitor_hash END) as uniques'),
            ])
            ->get();

        if ($totals->isEmpty()) {
            return 0;
        }

        // التوزيعات: استعلام واحد لكل بُعد بدل استعلام لكل رابط
        $distributions = [
            'by_country'  => $this->distribution($start, $end, 'country_code'),
            'by_device'   => $this->distribution($start, $end, 'device_type'),
            'by_browser'  => $this->distribution($start, $end, 'browser'),
            'by_referrer' => $this->distribution($start, $end, 'referrer_domain'),
        ];

        // upsert واحد لكل دفعة بدل updateOrCreate لكل رابط (SELECT + INSERT/UPDATE).
        // upsert مابيطبّقش الـ casts: التاريخ بيتكتب Y-m-d (نفس اللي كان
        // بيتخزّن قبل كده ونفس اللي الشاشات بتدوّر بيه)، والتوزيعات JSON يدوياً.
        $date  = $start->toDateString();
        $json  = fn (?array $v) => $v === null ? null : json_encode($v, JSON_UNESCAPED_UNICODE);

        $rows = $totals->map(function ($row) use ($distributions, $date, $json) {
            $linkId = (int) $row->link_id;

            return [
                'link_id'       => $linkId,
                'date'          => $date,
                // النقرات البشرية بس — البوتات في عمودها
                'clicks'        => (int) $row->total - (int) $row->bots,
                'unique_clicks' => (int) $row->uniques,
                'bot_clicks'    => (int) $row->bots,
                'by_country'    => $json($distributions['by_country'][$linkId] ?? null),
                'by_device'     => $json($distributions['by_device'][$linkId] ?? null),
                'by_browser'    => $json($distributions['by_browser'][$linkId] ?? null),
                'by_referrer'   => $json($distributions['by_referrer'][$linkId] ?? null),
            ];
        })->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            LinkDailyStat::upsert(
                $chunk,
                ['link_id', 'date'],
                ['clicks', 'unique_clicks', 'bot_clicks', 'by_country', 'by_device', 'by_browser', 'by_referrer']
            );
        }

        return $totals->count();
    }

    /**
     * توزيع بُعد واحد على كل الروابط.
     *
     * بيرجع: [linkId => ['SA' => 12, 'EG' => 3], ...]
     *
     * البوتات مستبعدة — التوزيعات بتوصف الزوار الحقيقيين.
     */
    private function distribution(Carbon $start, Carbon $end, string $column): array
    {
        $rows = LinkClick::query()
            ->whereBetween('clicked_at', [$start, $end])
            ->where('is_bot', false)
            ->whereNotNull($column)
            ->groupBy('link_id', $column)
            ->select(['link_id', $column, DB::raw('COUNT(*) as total')])
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $result[(int) $row->link_id][(string) $row->{$column}] = (int) $row->total;
        }

        // الأكتر أولاً، وبحد أقصى ٢٠ قيمة لكل بُعد عشان الصف
        // مايكبرش بلا داعي (الذيل الطويل مالوش قيمة تحليلية).
        foreach ($result as $linkId => $values) {
            arsort($values);
            $result[$linkId] = array_slice($values, 0, 20, true);
        }

        return $result;
    }
}
