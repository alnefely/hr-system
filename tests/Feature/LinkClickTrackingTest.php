<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\LinkClick;
use App\Services\Links\ClickRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * تتبّع النقرات — Redis وقت التحويل، وأمر مجدول للتفريغ.
 *
 * مفيش Jobs: الطابور قائمة في Redis و links:flush بيستهلكها.
 */
class LinkClickTrackingTest extends TestCase
{
    use RefreshDatabase;

    private const CHROME = 'Mozilla/5.0 (Windows NT 10.0; Win64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36';
    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) AppleWebKit/605.1 Version/17.0 Mobile Safari/604.1';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->flushRedis();
    }

    protected function tearDown(): void
    {
        $this->flushRedis();
        parent::tearDown();
    }

    private function flushRedis(): void
    {
        $redis = Redis::connection('cache');

        $redis->del(ClickRecorder::QUEUE_KEY);
        $redis->del(ClickRecorder::COUNTER_KEY);
        $redis->del(ClickRecorder::COUNTER_KEY . ':flushing');

        $prefix = (string) config('database.redis.options.prefix', '');

        foreach ($redis->keys(ClickRecorder::VISITORS_PREFIX . '*') as $key) {
            // keys() بترجع المفاتيح بالبادئة، والحذف بيضيفها تاني
            $redis->del($prefix !== '' ? str_replace($prefix, '', $key) : $key);
        }
    }

    private function link(array $attributes = []): Link
    {
        return Link::create(array_merge([
            'short_code'   => 'Trk1234',
            'original_url' => 'https://example.com/dest',
            'is_active'    => true,
        ], $attributes));
    }

    private function visit(string $code, string $userAgent = self::CHROME, string $ip = '1.1.1.1'): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeaders(['User-Agent' => $userAgent])
            ->get('/s/' . $code);
    }

    /**
     * مسار التحويل مايكتبش في قاعدة البيانات.
     *
     * ده سبب وجود Redis هنا أصلاً — لو رجعت الكتابة السنكرونس،
     * كل نقرة هتستنى قاعدة البيانات.
     */
    public function test_redirect_does_not_write_to_database(): void
    {
        $link = $this->link();

        $writes = 0;
        DB::listen(function ($query) use (&$writes) {
            if (preg_match('/^\s*(insert|update|delete)/i', $query->sql)) {
                $writes++;
            }
        });

        $this->visit($link->short_code);

        $this->assertEquals(0, $writes, 'التحويل كتب في قاعدة البيانات');
        $this->assertEquals(0, $link->fresh()->clicks_count, 'العدّاد اتحدّث فوراً');
    }

    /** النقرة بتروح لـ Redis. */
    public function test_click_is_queued_in_redis(): void
    {
        $link = $this->link();

        $this->visit($link->short_code);

        $redis = Redis::connection('cache');

        $this->assertEquals(1, $redis->llen(ClickRecorder::QUEUE_KEY), 'النقرة مادخلتش الطابور');
        $this->assertEquals(1, (int) $redis->hget(ClickRecorder::COUNTER_KEY, $link->id . ':clicks'));
    }

    /** التفريغ بينقل النقرات ويحدّث العدّادات. */
    public function test_flush_moves_clicks_and_updates_counters(): void
    {
        $link = $this->link();

        $this->visit($link->short_code);
        $this->visit($link->short_code, self::IPHONE, '2.2.2.2');

        $this->artisan('links:flush')->assertSuccessful();

        $this->assertEquals(2, LinkClick::count(), 'النقرات ماوصلتش لقاعدة البيانات');

        $fresh = $link->fresh();
        $this->assertEquals(2, $fresh->clicks_count, 'العدّاد غلط');
        $this->assertEquals(2, $fresh->unique_clicks_count, 'عدّاد الزوار الفريدين غلط');
        $this->assertNotNull($fresh->last_clicked_at);
    }

    /** التشغيل تاني مابيكررش النقرات. */
    public function test_flush_is_idempotent(): void
    {
        $link = $this->link();

        $this->visit($link->short_code);

        $this->artisan('links:flush')->assertSuccessful();
        $this->artisan('links:flush')->assertSuccessful();

        $this->assertEquals(1, LinkClick::count(), 'النقرة اتكررت');
        $this->assertEquals(1, $link->fresh()->clicks_count, 'العدّاد اتزوّد مرتين');
    }

    /** العدّاد بيتزوّد مش بيتمسح — التفريغ التاني بيضيف للأول. */
    public function test_flush_accumulates_across_runs(): void
    {
        $link = $this->link();

        $this->visit($link->short_code);
        $this->artisan('links:flush');

        $this->visit($link->short_code, self::IPHONE, '2.2.2.2');
        $this->artisan('links:flush');

        $this->assertEquals(2, $link->fresh()->clicks_count, 'التفريغ التاني مسح الأول');
    }

    /** نفس الزائر مايتحسبش فريد مرتين. */
    public function test_repeat_visitor_counted_once_as_unique(): void
    {
        $link = $this->link();

        $this->visit($link->short_code);
        $this->visit($link->short_code);
        $this->visit($link->short_code);

        $this->artisan('links:flush');

        $fresh = $link->fresh();
        $this->assertEquals(3, $fresh->clicks_count, 'عدد النقرات غلط');
        $this->assertEquals(1, $fresh->unique_clicks_count, 'الزائر المتكرر اتحسب أكتر من مرة');
    }

    /**
     * البوتات بتتعلّم ومابتزوّدش العدّاد.
     *
     * رمي الرابط في جروب واتساب بيدي عشرات المعاينات — من غير
     * الفلترة دي الإحصائيات بتبقى كذب.
     */
    public function test_bot_clicks_are_flagged_and_excluded(): void
    {
        $link = $this->link();

        $this->visit($link->short_code, 'WhatsApp/2.23.20.0', '3.3.3.3');
        $this->visit($link->short_code, 'Mozilla/5.0 (compatible; Googlebot/2.1)', '4.4.4.4');
        $this->visit($link->short_code);

        $this->artisan('links:flush');

        $this->assertEquals(3, LinkClick::count(), 'البوتات اتحذفت بدل ما تتعلّم');
        $this->assertEquals(2, LinkClick::bots()->count(), 'البوتات ماتعلّمتش');
        $this->assertEquals(1, LinkClick::humans()->count());

        $this->assertEquals(1, $link->fresh()->clicks_count, 'البوتات زوّدت العدّاد');

        $this->assertEquals(
            ['Google', 'WhatsApp'],
            LinkClick::bots()->pluck('bot_name')->sort()->values()->all()
        );
    }

    /** بيانات الجهاز بتتقري صح. */
    public function test_user_agent_is_parsed(): void
    {
        $link = $this->link();

        $this->visit($link->short_code, self::IPHONE, '5.5.5.5');
        $this->artisan('links:flush');

        $click = LinkClick::first();

        $this->assertEquals('mobile', $click->device_type);
        $this->assertEquals('iOS', $click->os);
        $this->assertEquals('Safari', $click->browser);
    }

    /** مفيش IP خام في قاعدة البيانات. */
    public function test_ip_is_stored_hashed(): void
    {
        $link = $this->link();

        $this->visit($link->short_code, self::CHROME, '203.0.113.45');
        $this->artisan('links:flush');

        $click = LinkClick::first();

        $this->assertNotNull($click->ip_hash);
        $this->assertEquals(64, strlen($click->ip_hash), 'مش sha256');
        $this->assertStringNotContainsString(
            '203.0.113.45',
            json_encode($click->toArray()),
            'الـ IP اتخزّن خام'
        );
    }

    /** حذف الرابط بيحذف نقراته. */
    public function test_deleting_link_cascades_to_clicks(): void
    {
        $link = $this->link();

        $this->visit($link->short_code);
        $this->artisan('links:flush');

        $this->assertEquals(1, LinkClick::count());

        $link->forceDelete();

        $this->assertEquals(0, LinkClick::count(), 'النقرات فضلت بعد حذف الرابط');
    }

    /**
     * سقوط Redis مايوقّعش التحويل.
     *
     * التسجيل مجرد إحصائية — لو وقع، النقرة تضيع لكن المستخدم
     * لازم يوصل لوجهته عادي.
     */
    public function test_redirect_survives_recorder_failure(): void
    {
        $link = $this->link();

        // مسجّل بيرمي استثناء بدل الحقيقي
        $this->app->bind(ClickRecorder::class, function () {
            return new class (
                app(\App\Services\Links\BotDetector::class),
                app(\App\Services\Links\UserAgentParser::class)
            ) extends ClickRecorder {
                public function record(int $linkId, \Illuminate\Http\Request $request): bool
                {
                    throw new \RuntimeException('Redis is down');
                }
            };
        });

        $response = $this->get('/s/' . $link->short_code);

        $response->assertStatus(302);
        $response->assertRedirect('https://example.com/dest');
    }

    /** السقف بيحسب النقرات اللي لسه في Redis. */
    public function test_max_clicks_accounts_for_pending_redis_clicks(): void
    {
        $link = $this->link(['max_clicks' => 2]);

        $this->visit($link->short_code);
        $this->visit($link->short_code);

        // من غير أي تفريغ: العمود لسه صفر، والسقف لازم يقف بالرغم من كده
        $this->assertEquals(0, $link->fresh()->clicks_count);

        $this->get('/s/' . $link->short_code)->assertStatus(410);
    }
}
