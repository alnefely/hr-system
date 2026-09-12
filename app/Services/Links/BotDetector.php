<?php

namespace App\Services\Links;

/**
 * كشف البوتات ومعاينات الروابط.
 *
 * من غير الكشف ده الإحصائيات بتبقى كذب: بمجرد ما ترمي الرابط في
 * جروب واتساب، واتساب بيفتحه علشان يعمل معاينة — ولو الجروب فيه
 * ٤٠ واحد بتتسجّل عشرات الزيارات قبل ما حد يضغط أصلاً.
 *
 * مكتوب يدوي بدل حزمة خارجية: القايمة صغيرة ومحددة، والحزم
 * (زي jenssegers/agent) بتجيب معاها اعتماديات مش محتاجينها.
 *
 * البوت بيتسجّل ومابيتحذفش — بيتعلّم بـ is_bot عشان يفضل
 * قابل للمراجعة، ويتشال من الإحصائيات بس.
 */
class BotDetector
{
    /**
     * معاينات الروابط في تطبيقات المحادثة.
     *
     * دي أهم مجموعة: بتضرب العدّاد بعشرات الزيارات الوهمية.
     */
    private const PREVIEW_BOTS = [
        'whatsapp'          => 'WhatsApp',
        'telegrambot'       => 'Telegram',
        'facebookexternalhit' => 'Facebook',
        'facebookcatalog'   => 'Facebook',
        'twitterbot'        => 'Twitter',
        'linkedinbot'       => 'LinkedIn',
        'slackbot'          => 'Slack',
        'slack-imgproxy'    => 'Slack',
        'discordbot'        => 'Discord',
        'skypeuripreview'   => 'Skype',
        'viber'             => 'Viber',
        'snapchat'          => 'Snapchat',
        'pinterest'         => 'Pinterest',
        'redditbot'         => 'Reddit',
        'embedly'           => 'Embedly',
        'quora link preview' => 'Quora',
        'vkshare'           => 'VK',
        'tiktok'            => 'TikTok',
        'bitlybot'          => 'Bitly',
    ];

    /** محركات البحث والزواحف. */
    private const CRAWLERS = [
        'googlebot'      => 'Google',
        'bingbot'        => 'Bing',
        'yandexbot'      => 'Yandex',
        'duckduckbot'    => 'DuckDuckGo',
        'baiduspider'    => 'Baidu',
        'applebot'       => 'Apple',
        'ahrefsbot'      => 'Ahrefs',
        'semrushbot'     => 'SEMrush',
        'mj12bot'        => 'Majestic',
        'dotbot'         => 'Moz',
        'petalbot'       => 'Petal',
        'gptbot'         => 'OpenAI',
        'ccbot'          => 'CommonCrawl',
        'claudebot'      => 'Anthropic',
        'perplexitybot'  => 'Perplexity',
    ];

    /** أدوات المراقبة وسطر الأوامر. */
    private const TOOLS = [
        'uptimerobot'  => 'UptimeRobot',
        'pingdom'      => 'Pingdom',
        'statuscake'   => 'StatusCake',
        'curl'         => 'curl',
        'wget'         => 'wget',
        'python-requests' => 'Python',
        'go-http-client'  => 'Go',
        'okhttp'       => 'OkHttp',
        'postmanruntime' => 'Postman',
        'headlesschrome' => 'Headless Chrome',
        'phantomjs'    => 'PhantomJS',
        'puppeteer'    => 'Puppeteer',
        'playwright'   => 'Playwright',
    ];

    /**
     * الكلمات العامة — آخر خط دفاع.
     *
     * بتتفحص بعد القوايم المحددة عشان الاسم الدقيق يفضل له أولوية.
     */
    private const GENERIC = ['bot', 'crawler', 'spider', 'scraper', 'preview', 'fetcher', 'monitor'];

    /**
     * اسم البوت، أو null لو زائر حقيقي.
     */
    public function detect(?string $userAgent): ?string
    {
        $ua = trim(mb_strtolower((string) $userAgent));

        // User-Agent فاضي = مش متصفح حقيقي
        if ($ua === '') {
            return 'Unknown';
        }

        foreach ([self::PREVIEW_BOTS, self::CRAWLERS, self::TOOLS] as $list) {
            foreach ($list as $needle => $name) {
                if (str_contains($ua, $needle)) {
                    return $name;
                }
            }
        }

        foreach (self::GENERIC as $needle) {
            if (str_contains($ua, $needle)) {
                return 'Bot';
            }
        }

        return null;
    }

    public function isBot(?string $userAgent): bool
    {
        return $this->detect($userAgent) !== null;
    }
}
