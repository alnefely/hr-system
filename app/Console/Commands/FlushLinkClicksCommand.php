<?php

namespace App\Console\Commands;

use App\Models\LinkClick;
use App\Services\Links\ClickRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * تفريغ نقرات الروابط من Redis لقاعدة البيانات.
 *
 * مسار التحويل مابيكتبش في قاعدة البيانات خالص — بيرمي النقرة في
 * Redis ويمشي. الأمر ده هو اللي بيفرّغ، وبيتجدول كل ٥ دقايق.
 *
 * بديل الـ Job: نفس النتيجة من غير طابور ولا worker شغّال طول الوقت.
 *
 *   php artisan links:flush
 *   php artisan links:flush --limit=5000
 */
class FlushLinkClicksCommand extends Command
{
    protected $signature = 'links:flush
        {--limit=10000 : أقصى عدد نقرات تتفرّغ في التشغيلة الواحدة}';

    protected $description = 'تفريغ نقرات الروابط المؤقتة من Redis لجدول link_clicks وتحديث العدّادات';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        try {
            $redis = Redis::connection('cache');
        } catch (\Throwable $e) {
            $this->error('تعذّر الاتصال بـ Redis: ' . $e->getMessage());

            return self::FAILURE;
        }

        $clicks = $this->pullClicks($redis, $limit);
        $counters = $this->pullCounters($redis);

        if ($clicks === [] && $counters === []) {
            $this->info('مفيش نقرات مستنية. ✔');

            return self::SUCCESS;
        }

        $inserted = $this->storeClicks($clicks);
        $updated  = $this->applyCounters($counters);

        $this->info("تم تفريغ {$inserted} نقرة وتحديث عدّادات {$updated} رابط. ✔");

        return self::SUCCESS;
    }

    /**
     * سحب النقرات من الطابور.
     *
     * LRANGE + LTRIM جوه MULTI بدل LPOP بعدد: الأخيرة محتاجة
     * Redis 6.2 والسيرفر هنا 5.0. المعاملة بتدّي نفس الذرّية —
     * تشغيلتين متوازيتين ماتقراش نفس النقرة مرتين.
     */
    private function pullClicks($redis, int $limit): array
    {
        $result = $redis->transaction(function ($tx) use ($limit) {
            $tx->lrange(ClickRecorder::QUEUE_KEY, 0, $limit - 1);
            $tx->ltrim(ClickRecorder::QUEUE_KEY, $limit, -1);
        });

        $raw = $result[0] ?? [];

        if (! $raw) {
            return [];
        }

        $raw = is_array($raw) ? $raw : [$raw];

        $rows = [];

        foreach ($raw as $item) {
            $decoded = json_decode($item, true);

            // صف تالف مايوقّفش الباقي
            if (is_array($decoded) && isset($decoded['link_id'])) {
                $rows[] = $decoded;
            }
        }

        return $rows;
    }

    /**
     * سحب العدّادات.
     *
     * بنعزل المفتاح بـ RENAME الأول بدل ما نقرا وبعدين نمسح: أي نقرة
     * بتوصل بين القراءة والمسح كانت هتضيع. بعد الـ RENAME النقرات
     * الجديدة بتروح لمفتاح جديد فوراً، واحنا بنقرا النسخة المعزولة.
     */
    private function pullCounters($redis): array
    {
        $snapshot = ClickRecorder::COUNTER_KEY . ':flushing';

        try {
            // بيرمي استثناء لو المفتاح مش موجود — يعني مفيش عدّادات
            $redis->rename(ClickRecorder::COUNTER_KEY, $snapshot);
        } catch (\Throwable $e) {
            // ممكن تكون نسخة سابقة وقعت قبل ما تخلص — بنكمّلها
            if (! $redis->exists($snapshot)) {
                return [];
            }
        }

        $counters = $redis->hgetall($snapshot);

        if (! $counters) {
            $redis->del($snapshot);

            return [];
        }

        $byLink = [];

        foreach ($counters as $field => $value) {
            // الشكل: "{linkId}:clicks" أو "{linkId}:unique"
            [$linkId, $type] = array_pad(explode(':', $field, 2), 2, null);

            if (! is_numeric($linkId) || ! in_array($type, ['clicks', 'unique'], true)) {
                continue;
            }

            $byLink[(int) $linkId][$type] = (int) $value;
        }

        $redis->del($snapshot);

        return $byLink;
    }

    private function storeClicks(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $inserted = 0;

        // على دفعات: إدخال عشرات الآلاف في استعلام واحد بيضرب
        // حد حجم الحزمة في MySQL.
        foreach (array_chunk($rows, 500) as $chunk) {
            LinkClick::insert($chunk);
            $inserted += count($chunk);
        }

        return $inserted;
    }

    /**
     * تطبيق العدّادات على جدول الروابط.
     *
     * زيادة نسبية (‎+=‎) مش إسناد: الرقم في Redis هو الفرق من آخر
     * تفريغ، والإسناد كان هيمسح كل التاريخ.
     */
    private function applyCounters(array $byLink): int
    {
        $updated = 0;

        foreach ($byLink as $linkId => $values) {
            $clicks = $values['clicks'] ?? 0;
            $unique = $values['unique'] ?? 0;

            if ($clicks === 0 && $unique === 0) {
                continue;
            }

            $affected = DB::table('links')->where('id', $linkId)->update([
                'clicks_count'        => DB::raw('clicks_count + ' . $clicks),
                'unique_clicks_count' => DB::raw('unique_clicks_count + ' . $unique),
                'last_clicked_at'     => now(),
            ]);

            if ($affected) {
                $updated++;
            }
        }

        return $updated;
    }
}
