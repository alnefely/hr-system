<?php

namespace App\Services\Links;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * تسجيل النقرة.
 *
 * مسار التحويل مابيلمسش قاعدة البيانات خالص: كل نقرة بتتكتب في
 * Redis (دفعة مؤقتة + عدّادات)، والأمر المجدول links:flush بيفرّغها
 * لجدول link_clicks كل ٥ دقايق.
 *
 * مفيش Jobs ولا طوابير — قرار صريح. الطابور هنا مجرد قائمة في
 * Redis، والأمر المجدول هو اللي بيستهلكها.
 *
 * قاعدة أساسية: التسجيل مايوقّعش التحويل أبداً. لو Redis وقع،
 * النقرة تضيع لكن المستخدم يوصل لوجهته.
 */
class ClickRecorder
{
    /** قائمة النقرات المستنية التفريغ. */
    public const QUEUE_KEY = 'links:clicks:pending';

    /** عدّادات مؤقتة لكل رابط. */
    public const COUNTER_KEY = 'links:counters';

    /** الزوار الفريدين لكل رابط في اليوم. */
    public const VISITORS_PREFIX = 'links:visitors:';

    public function __construct(
        protected BotDetector $bots,
        protected UserAgentParser $parser
    ) {}

    /**
     * تسجيل نقرة.
     *
     * بيرجع true لو اتسجّلت بشري (يعني تتحسب في العدّاد الظاهر).
     */
    public function record(int $linkId, Request $request): bool
    {
        try {
            $userAgent = (string) $request->userAgent();
            $botName   = $this->bots->detect($userAgent);
            $isBot     = $botName !== null;

            $visitorHash = $this->visitorHash($linkId, $request);
            $isUnique    = ! $isBot && $this->markVisitor($linkId, $visitorHash);

            $agent = $this->parser->parse($userAgent);

            $payload = [
                'link_id'         => $linkId,
                'ip_hash'         => $this->hash((string) $request->ip()),
                'visitor_hash'    => $visitorHash,
                'device_type'     => $agent['device_type'],
                'os'              => $agent['os'],
                'browser'         => $agent['browser'],
                'country_code'    => $this->countryCode($request),
                'language'        => $this->language($request),
                'referrer_domain' => $this->referrerDomain($request),
                'is_bot'          => $isBot,
                'bot_name'        => $botName,
                'clicked_at'      => now()->toDateTimeString(),
            ];

            $redis = $this->redis();
            $redis->rpush(self::QUEUE_KEY, json_encode($payload, JSON_UNESCAPED_UNICODE));

            // البوتات بتتسجّل بس مابتزوّدش العدّاد الظاهر — وإلا
            // معاينة واتساب لوحدها بتضخّم الرقم عشرات المرات.
            if (! $isBot) {
                $redis->hincrby(self::COUNTER_KEY, $linkId . ':clicks', 1);

                if ($isUnique) {
                    $redis->hincrby(self::COUNTER_KEY, $linkId . ':unique', 1);
                }
            }

            return ! $isBot;
        } catch (\Throwable $e) {
            // التحويل أهم من الإحصائية: بنبلع الخطأ ونسجّله في اللوج.
            Log::warning('ClickRecorder failed: ' . $e->getMessage(), ['link_id' => $linkId]);

            return false;
        }
    }

    /**
     * بصمة الزائر.
     *
     * الملح بيتغيّر كل يوم عشان البصمة ماتبقاش تتبّع دائم عبر الأيام —
     * كفاية للتفرقة بين زوار نفس اليوم وخلاص.
     */
    private function visitorHash(int $linkId, Request $request): string
    {
        return $this->hash(implode('|', [
            $request->ip(),
            $request->userAgent(),
            $linkId,
            now()->toDateString(),
        ]));
    }

    /**
     * تعليم الزائر كفريد لليوم.
     *
     * SADD بترجع 1 لو الزائر جديد، 0 لو مسجّل قبل كده.
     */
    private function markVisitor(int $linkId, string $visitorHash): bool
    {
        $key = self::VISITORS_PREFIX . $linkId . ':' . now()->toDateString();

        $added = (int) $this->redis()->sadd($key, $visitorHash);

        // المفتاح بيموت بعد يومين — العد اليومي خلص شغله
        $this->redis()->expire($key, 172800);

        return $added === 1;
    }

    private function hash(string $value): string
    {
        return hash('sha256', $value . config('app.key'));
    }

    /** الدولة من هيدر البروكسي لو موجود — من غير خدمة خارجية. */
    private function countryCode(Request $request): ?string
    {
        $code = $request->header('CF-IPCountry')
            ?? $request->header('X-Country-Code');

        if (! $code || strlen($code) !== 2 || $code === 'XX') {
            return null;
        }

        return strtoupper($code);
    }

    private function language(Request $request): ?string
    {
        $lang = $request->getPreferredLanguage();

        return $lang ? substr($lang, 0, 10) : null;
    }

    private function referrerDomain(Request $request): ?string
    {
        $referer = $request->headers->get('referer');

        if (! $referer) {
            return null;
        }

        $host = parse_url($referer, PHP_URL_HOST);

        return $host ? mb_substr($host, 0, 190) : null;
    }

    /** اتصال الكاش — هو اللي مضبوط على Redis في المشروع. */
    private function redis()
    {
        return Redis::connection('cache');
    }
}
