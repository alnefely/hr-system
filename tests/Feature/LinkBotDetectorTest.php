<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\LinkClick;
use App\Services\Links\BotDetector;
use App\Services\Links\UserAgentParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkBotDetectorTest extends TestCase
{
    use RefreshDatabase;

    private function detector(): BotDetector
    {
        return app(BotDetector::class);
    }

    /** معاينات المحادثة — أهم مجموعة، بتضخّم العدّاد. */
    public function test_detects_chat_previews(): void
    {
        $cases = [
            'WhatsApp/2.23.20.0'                              => 'WhatsApp',
            'TelegramBot (like TwitterBot)'                   => 'Telegram',
            'facebookexternalhit/1.1'                         => 'Facebook',
            'Twitterbot/1.0'                                  => 'Twitter',
            'Slackbot-LinkExpanding 1.0'                      => 'Slack',
            'Mozilla/5.0 (compatible; Discordbot/2.0)'        => 'Discord',
            'LinkedInBot/1.0'                                 => 'LinkedIn',
        ];

        foreach ($cases as $ua => $expected) {
            $this->assertEquals($expected, $this->detector()->detect($ua), "فشل كشف: {$ua}");
        }
    }

    public function test_detects_crawlers(): void
    {
        $this->assertEquals('Google', $this->detector()->detect('Mozilla/5.0 (compatible; Googlebot/2.1)'));
        $this->assertEquals('Bing', $this->detector()->detect('Mozilla/5.0 (compatible; bingbot/2.0)'));
        $this->assertEquals('OpenAI', $this->detector()->detect('GPTBot/1.0'));
    }

    public function test_detects_tools(): void
    {
        $this->assertEquals('curl', $this->detector()->detect('curl/8.4.0'));
        $this->assertEquals('Python', $this->detector()->detect('python-requests/2.31.0'));
        $this->assertEquals('UptimeRobot', $this->detector()->detect('Mozilla/5.0 (compatible; UptimeRobot/2.0)'));
    }

    /** الـ UA الفاضي مش متصفح حقيقي. */
    public function test_treats_empty_user_agent_as_bot(): void
    {
        $this->assertNotNull($this->detector()->detect(''));
        $this->assertNotNull($this->detector()->detect(null));
    }

    /** والأهم: المتصفحات الحقيقية ماتتعلّمش بوتات. */
    public function test_real_browsers_are_not_flagged(): void
    {
        $browsers = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (Linux; Android 13; SM-S918B) AppleWebKit/537.36 Chrome/120.0.0.0 Mobile Safari/537.36',
        ];

        foreach ($browsers as $ua) {
            $this->assertNull($this->detector()->detect($ua), "متصفح حقيقي اتعلّم بوت: {$ua}");
        }
    }

    /** قراءة الجهاز والنظام والمتصفح. */
    public function test_parses_user_agents(): void
    {
        $parser = app(UserAgentParser::class);

        $cases = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36'
                => ['desktop', 'Windows', 'Chrome'],
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1 Version/17.0 Mobile Safari/604.1'
                => ['mobile', 'iOS', 'Safari'],
            'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1 Version/17.0 Mobile Safari/604.1'
                => ['tablet', 'iOS', 'Safari'],
            'Mozilla/5.0 (Linux; Android 13; SM-S918B) AppleWebKit/537.36 Chrome/120.0 Mobile Safari/537.36'
                => ['mobile', 'Android', 'Chrome'],
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36 Edg/120.0'
                => ['desktop', 'Windows', 'Edge'],
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0'
                => ['desktop', 'Windows', 'Firefox'],
        ];

        foreach ($cases as $ua => [$device, $os, $browser]) {
            $parsed = $parser->parse($ua);

            $this->assertEquals($device, $parsed['device_type'], "الجهاز غلط: {$ua}");
            $this->assertEquals($os, $parsed['os'], "النظام غلط: {$ua}");
            $this->assertEquals($browser, $parsed['browser'], "المتصفح غلط: {$ua}");
        }
    }

    /** التنظيف الدوري بيحترم مدة الاحتفاظ. */
    public function test_prune_respects_retention_period(): void
    {
        $link = Link::create([
            'short_code'   => 'Prn1234',
            'original_url' => 'https://example.com',
            'is_active'    => true,
        ]);

        $make = fn (int $daysAgo) => LinkClick::create([
            'link_id'    => $link->id,
            'clicked_at' => now()->subDays($daysAgo),
            'is_bot'     => false,
        ]);

        $make(120);
        $make(100);
        $make(30);
        $make(1);

        $this->artisan('links:prune --days=90')->assertSuccessful();

        $this->assertEquals(2, LinkClick::count(), 'الحذف شال نقرات لسه محتاجينها أو ساب قديمة');

        $oldest = LinkClick::orderBy('clicked_at')->first();
        $this->assertTrue(
            $oldest->clicked_at->greaterThanOrEqualTo(now()->subDays(90)->startOfDay()),
            'فضلت نقرة أقدم من مدة الاحتفاظ'
        );
    }
}
