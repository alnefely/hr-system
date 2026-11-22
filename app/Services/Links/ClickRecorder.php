<?php

namespace App\Services\Links;

use App\Models\LinkClick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
 * قاعدة أساسية: التسجيل مايوقّعش التحويل أبداً. لو Redis مش متاح،
 * النقرة بتتكتب في قاعدة البيانات مباشرة (أبطأ لكن مابتضيعش)، ولو
 * ده كمان فشل المستخدم بيوصل لوجهته برضه.
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
            $payload = $this->buildPayload($linkId, $request);
        } catch (\Throwable $e) {
            Log::warning('ClickRecorder failed: ' . $e->getMessage(), ['link_id' => $linkId]);

            return false;
        }

        try {
            return $this->pushToRedis($payload);
        } catch (\Throwable $e) {
            // Redis مش متاح (مش متسطّب على السيرفر أو واقع): بدل ما النقرة
            // تضيع بصمت، بنكتبها في قاعدة البيانات مباشرة. أبطأ شوية، لكن
            // الإحصائيات بتفضل صحيحة.
            Log::warning('ClickRecorder: Redis unavailable, writing click directly: ' . $e->getMessage(), [
                'link_id' => $linkId,
            ]);
        }

        try {
            return $this->recordDirectly($payload);
        } catch (\Throwable $e) {
            // التحويل أهم من الإحصائية: بنبلع الخطأ ونسجّله في اللوج.
            Log::warning('ClickRecorder failed: ' . $e->getMessage(), ['link_id' => $linkId]);

            return false;
        }
    }

    /** بيانات النقرة — نفس الشكل اللي links:flush بيدخّله في link_clicks. */
    private function buildPayload(int $linkId, Request $request): array
    {
        $userAgent = (string) $request->userAgent();
        $botName   = $this->bots->detect($userAgent);
        $agent     = $this->parser->parse($userAgent);

        return [
            'link_id'         => $linkId,
            'ip_hash'         => $this->hash((string) $request->ip()),
            'visitor_hash'    => $this->visitorHash($linkId, $request),
            'device_type'     => $agent['device_type'],
            'os'              => $agent['os'],
            'browser'         => $agent['browser'],
            'country_code'    => $this->countryCode($request),
            'language'        => $this->language($request),
            'referrer_domain' => $this->referrerDomain($request),
            'is_bot'          => $botName !== null,
            'bot_name'        => $botName,
            'clicked_at'      => now()->toDateTimeString(),
        ];
    }

    /**
     * المسار السريع: دفعة مؤقتة في Redis، و links:flush بيفرّغها.
     *
     * كل الأوامر في pipeline واحد (رحلة واحدة لـ Redis بدل ٥).
     * عدّاد الفريد بس هو اللي محتاج نتيجة SADD، فبيروح في رحلة تانية
     * لأول زيارة في اليوم بس.
     */
    private function pushToRedis(array $payload): bool
    {
        $linkId = $payload['link_id'];
        $isBot  = $payload['is_bot'];
        $visitorsKey = self::VISITORS_PREFIX . $linkId . ':' . now()->toDateString();

        $results = $this->redis()->pipeline(function ($pipe) use ($payload, $linkId, $isBot, $visitorsKey) {
            if (! $isBot) {
                // SADD بترجع 1 لو الزائر جديد النهارده، 0 لو مسجّل قبل كده.
                // المفتاح بيموت بعد يومين — العد اليومي خلص شغله.
                $pipe->sadd($visitorsKey, $payload['visitor_hash']);
                $pipe->expire($visitorsKey, 172800);
            }

            $pipe->rpush(self::QUEUE_KEY, json_encode($payload, JSON_UNESCAPED_UNICODE));

            // البوتات بتتسجّل بس مابتزوّدش العدّاد الظاهر — وإلا
            // معاينة واتساب لوحدها بتضخّم الرقم عشرات المرات.
            if (! $isBot) {
                $pipe->hincrby(self::COUNTER_KEY, $linkId . ':clicks', 1);
            }
        });

        $isUnique = ! $isBot && (int) ($results[0] ?? 0) === 1;

        if ($isUnique) {
            $this->redis()->hincrby(self::COUNTER_KEY, $linkId . ':unique', 1);
        }

        return ! $isBot;
    }

    /**
     * المسار البديل من غير Redis: النقرة والعدّادات في قاعدة البيانات مباشرة.
     *
     * الزائر الفريد = أول نقرة بشرية بنفس البصمة. البصمة فيها تاريخ اليوم،
     * فالتفرّد يومي زي مسار Redis بالظبط.
     */
    private function recordDirectly(array $payload): bool
    {
        $isBot = $payload['is_bot'];

        $isUnique = ! $isBot && ! LinkClick::where('link_id', $payload['link_id'])
            ->where('visitor_hash', $payload['visitor_hash'])
            ->where('is_bot', false)
            ->exists();

        LinkClick::insert($payload);

        if (! $isBot) {
            DB::table('links')->where('id', $payload['link_id'])->update([
                'clicks_count'        => DB::raw('clicks_count + 1'),
                'unique_clicks_count' => DB::raw('unique_clicks_count + ' . ($isUnique ? 1 : 0)),
                'last_clicked_at'     => now(),
            ]);
        }

        return ! $isBot;
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
