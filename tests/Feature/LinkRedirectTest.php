<?php

namespace Tests\Feature;

use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * سلوك التحويل نفسه — الجزء اللي بيشوفه الزائر.
 */
class LinkRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        // الطابور في Redis مابيتصفّرش مع قاعدة البيانات، فلازم
        // ننضّفه يدوي وإلا نقرات اختبار بتعدّي للي بعده.
        $redis = \Illuminate\Support\Facades\Redis::connection('cache');
        $redis->del(\App\Services\Links\ClickRecorder::QUEUE_KEY);
        $redis->del(\App\Services\Links\ClickRecorder::COUNTER_KEY);
    }

    private function link(array $attributes = []): Link
    {
        return Link::create(array_merge([
            'short_code'   => 'Abc1234',
            'original_url' => 'https://example.com/destination',
            'is_active'    => true,
        ], $attributes));
    }

    /**
     * 302 مش 301.
     *
     * الـ 301 بيتكاش في المتصفح للأبد: العدّاد بيقف بعد أول زيارة،
     * والوجهة بتبقى غير قابلة للتغيير لأي حد فتح الرابط قبل كده.
     */
    public function test_redirect_is_302_not_301(): void
    {
        $link = $this->link();

        $response = $this->get('/s/' . $link->short_code);

        $response->assertStatus(302);
        $response->assertRedirect('https://example.com/destination');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    /**
     * العدّاد بيتحدّث بعد التفريغ مش وقت التحويل.
     *
     * التحويل بيكتب في Redis بس (المرحلة ٢) عشان مايستناش قاعدة
     * البيانات — فالتحقق لازم يعدّي على links:flush الأول.
     */
    public function test_click_increments_counter(): void
    {
        $link = $this->link();

        $this->get('/s/' . $link->short_code);
        $this->get('/s/' . $link->short_code);

        $this->artisan('links:flush');

        $this->assertEquals(2, $link->fresh()->clicks_count);
        $this->assertNotNull($link->fresh()->last_clicked_at);
    }

    public function test_unknown_code_returns_404(): void
    {
        $this->get('/s/NoSuchX')->assertStatus(404);
    }

    public function test_inactive_link_returns_410(): void
    {
        $link = $this->link(['is_active' => false]);

        $this->get('/s/' . $link->short_code)->assertStatus(410);
    }

    public function test_expired_link_returns_410(): void
    {
        $link = $this->link(['expires_at' => now()->subDay()]);

        $this->get('/s/' . $link->short_code)->assertStatus(410);
    }

    /** السقف لازم يتحسب على العدّاد الحقيقي مش على نسخة الكاش. */
    public function test_max_clicks_blocks_further_redirects(): void
    {
        $link = $this->link(['max_clicks' => 2]);

        $this->get('/s/' . $link->short_code)->assertStatus(302);
        $this->get('/s/' . $link->short_code)->assertStatus(302);
        $this->get('/s/' . $link->short_code)->assertStatus(410);

        $this->artisan('links:flush');

        $this->assertEquals(2, $link->fresh()->clicks_count);
    }

    /** الكاش مايخليش الرابط يفضل على وجهته القديمة بعد التعديل. */
    public function test_cache_is_cleared_when_destination_changes(): void
    {
        $link = $this->link();

        $this->get('/s/' . $link->short_code)
            ->assertRedirect('https://example.com/destination');

        $link->update(['original_url' => 'https://example.com/changed']);

        $this->get('/s/' . $link->short_code)
            ->assertRedirect('https://example.com/changed');
    }

    /** وتغيير الكود نفسه بيبطّل القديم. */
    public function test_old_code_stops_working_after_rename(): void
    {
        $link = $this->link();

        $this->get('/s/Abc1234')->assertStatus(302);

        $link->update(['short_code' => 'Xyz9876']);

        $this->get('/s/Abc1234')->assertStatus(404);
        $this->get('/s/Xyz9876')->assertStatus(302);
    }

    /** الرابط المحذوف مايحوّلش. */
    public function test_deleted_link_does_not_redirect(): void
    {
        $link = $this->link();
        $this->get('/s/' . $link->short_code)->assertStatus(302);

        $link->delete();

        $this->get('/s/' . $link->short_code)->assertStatus(404);
    }
}
