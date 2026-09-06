<?php

namespace App\Services\Cache;

use App\Services\Cache\Keys\_CacheKeys;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * ============================================================
 *  CacheService — كاش بالمجموعات، شغال على أي Driver
 * ============================================================
 *
 *  بديل Cache::tags() (اللي بتشتغل على redis بس).
 *
 *  الفكرة: كل مجموعة ليها رقم إصدار جوّه المفتاح نفسه.
 *
 *      settings.v1756022400:grouped
 *      ^^^^^^^^^^^^^^^^^^^^ ^^^^^^^
 *        المجموعة + إصدارها   المفتاح
 *
 *  لما تنادي invalidate() بنغيّر رقم الإصدار بس — المفاتيح القديمة
 *  محدش يقراها تاني وبتموت لوحدها لما ينتهي الـ TTL.
 *
 *  ------------------------------------------------------------
 *  الاستخدام
 *  ------------------------------------------------------------
 *
 *      use App\Services\Cache\Keys\SettingsCache;
 *
 *      $cache->remember(SettingsCache::class, SettingsCache::GROUPED, fn () => ...);
 *      $cache->invalidate(SettingsCache::class);
 *
 *  المجموعات معرّفة في Keys/ — كل مجموعة في ملف واحد.
 *
 *  ------------------------------------------------------------
 *  أهم قاعدة: المجموعة مش المفتاح
 *  ------------------------------------------------------------
 *
 *      remember($groups, $key, $callback)
 *                 ↑        ↑
 *           وحدة الإلغاء  التمييز
 *
 *  "هل هعمل invalidate للحاجة دي لوحدها؟"  آه → مجموعة، لأ → مفتاح.
 */
class CacheService
{
    /**
     * المدة الافتراضية بالثواني (ساعة).
     *
     * ⚠️ مهم: لازم TTL على كل مفتاح، لأن المفاتيح القديمة
     * مش بتتمسح فعلياً — بتفضل واخدة مساحة لحد ما تنتهي مدتها.
     */
    protected int $defaultTtl = 3600;

    /**
     * ذاكرة مؤقتة للإصدارات داخل نفس الـ Request.
     *
     * من غيرها، لو الـ Request فيه 10 استدعاءات لنفس المجموعة
     * هنضرب Redis 10 مرات عشان نجيب نفس الرقم.
     *
     * (عشان كده الكلاس لازم يكون singleton)
     */
    protected array $versions = [];

    // ========================================================
    //  الدوال العامة
    // ========================================================

    /**
     * جيب من الكاش، ولو مش موجود نفّذ الـ callback واحفظ النتيجة.
     * دي اللي هتستخدمها في 90% من الحالات.
     *
     * @param  string|array  $groups    كلاس المفاتيح (SettingsCache::class) أو نص
     * @param  string        $key       اسم المفتاح داخل المجموعة
     * @param  Closure       $callback  الكود اللي هيجيب البيانات
     * @param  int|null      $ttl       المدة بالثواني
     */
    public function remember(string|array $groups, string $key, Closure $callback, ?int $ttl = null): mixed
    {
        return Cache::remember(
            $this->buildKey($groups, $key),
            $ttl ?? $this->defaultTtl,
            $callback
        );
    }

    /**
     * قراءة من الكاش وبس — من غير ما تحفظ أو تروح للداتابيز.
     *
     * ⚠️ لو المفتاح لسه ما اتكاشش، بترجّع $default (يعني null غالباً)،
     *    حتى لو الداتا موجودة في الداتابيز. دي مش بتعرف تجيبها.
     *
     *        $cache->get(SettingsCache::class, SettingsCache::value('site_name'));
     *        // → null لو لسه محدش كاش القيمة، رغم إنها في الداتابيز
     *
     * ✅ اللي إنت عايزه في ٩٩٪ من الحالات هو remember():
     *
     *        $cache->remember(
     *            SettingsCache::class,
     *            SettingsCache::value('site_name'),
     *            fn () => Settings::where('key', 'site_name')->value('value')
     *        );
     *
     * استخدم get() بس لما تكون فعلاً عايز تعرف "هل ده متكاش ولا لأ"
     * من غير ما تبني القيمة.
     */
    public function get(string|array $groups, string $key, mixed $default = null): mixed
    {
        return Cache::get($this->buildKey($groups, $key), $default);
    }

    /**
     * احفظ قيمة مباشرة (من غير callback).
     */
    public function put(string|array $groups, string $key, mixed $value, ?int $ttl = null): bool
    {
        return Cache::put(
            $this->buildKey($groups, $key),
            $value,
            $ttl ?? $this->defaultTtl
        );
    }

    /**
     * هل المفتاح موجود؟
     */
    public function has(string|array $groups, string $key): bool
    {
        return Cache::has($this->buildKey($groups, $key));
    }

    /**
     * امسح مفتاح واحد بعينه (من غير ما تأثر على باقي المجموعة).
     *
     * ⚠️ دي بتاخد المجموعة + المفتاح. لو عايز تلغي المجموعة
     *    كلها استخدم invalidate() بدلاً منها.
     */
    public function forget(string|array $groups, string $key): bool
    {
        return Cache::forget($this->buildKey($groups, $key));
    }

    /**
     * ⭐ إلغاء مجموعة كاملة — بديلة Cache::tags()->flush()
     *
     * مش بتمسح حاجة فعلياً، بس بتغيّر رقم الإصدار، فكل
     * المفاتيح القديمة بتاعة المجموعة دي محدش يقراها تاني.
     *
     * ⚠️ بتاخد أسماء مجموعات بس — عمرها ما بتاخد اسم مفتاح.
     *
     * شغالة على أي Driver.
     */
    public function invalidate(string|array $groups): void
    {
        foreach ($this->toArray($groups) as $group) {
            $group = $this->normalize($group);

            // ⚠️ لازم الإصدار الجديد يبقى أكبر من القديم *دايماً*.
            //
            // الوقت بالثواني لوحده مش كفاية: لو حصل تعديلين في نفس
            // الثانية (زي شاشة الإعدادات وهي بتحفظ الحقول واحد واحد
            // في لوب) الرقم بيطلع نفسه، فالمفتاح القديم يفضل عايش
            // والمستخدم يشوف بيانات قديمة من غير أي رسالة خطأ.
            //
            // فلو الوقت مش أكبر من الإصدار الحالي، بنزوّد واحد.
            $current = $this->version($group);
            $stamp   = max(now()->timestamp, $current + 1);

            // نكتب الرقم الجديد على "الورقة"
            Cache::forever($this->versionKey($group), $stamp);

            // ونحدّث الذاكرة المؤقتة عشان باقي الـ Request
            // يشوف الرقم الجديد فوراً
            $this->versions[$group] = $stamp;
        }
    }

    /**
     * تغيير المدة الافتراضية لاستدعاء واحد بس.
     *
     *   $cache->ttl(300)->remember(...)
     *
     * بترجّع نسخة جديدة مش نفس الـ instance، لأن الكلاس singleton
     * والتعديل المباشر كان بيسرّب الـ TTL على كل حد تاني في نفس الـ Request.
     */
    public function ttl(int $seconds): static
    {
        $clone = clone $this;
        $clone->defaultTtl = $seconds;

        return $clone;
    }

    // ========================================================
    //  الدوال الداخلية
    // ========================================================

    /**
     * تركيب المفتاح النهائي: المجموعات + إصداراتها + اسم المفتاح.
     *
     *   buildKey([LessonCache::class, LessonCache::for(5)], 'list')
     *   → "lessons.v1756022400:lessons.5.v1756009200:list"
     */
    protected function buildKey(string|array $groups, string $key): string
    {
        $prefix = collect($this->toArray($groups))
            ->map(fn ($group) => $this->normalize($group))
            ->map(fn (string $group) => "{$group}.v" . $this->version($group))
            ->implode(':');

        return "{$prefix}:{$key}";
    }

    /**
     * تغليف المدخل في array.
     *
     * ⚠️ مينفعش نستخدم (array) هنا: التحويل ده على enum بيرجّع
     *    خصائصه (['name' => ..., 'value' => ...]) مش [$enum]،
     *    فالمجموعة كانت بتتكرر في المفتاح مرتين.
     */
    protected function toArray(string|array $groups): array
    {
        return is_array($groups) ? $groups : [$groups];
    }

    /**
     * تحويل أي شكل من أشكال "المجموعة" لنص.
     *
     * بيقبل تلات أشكال:
     *
     *   SettingsCache::class     ← الشكل المفضّل (كلاس المفاتيح)
     *   SettingsCache::for(5)    ← مجموعة فرعية لسجل معيّن (نص)
     *   'اسم يدوي'                ← نص عادي، بيعدي زي ما هو
     */
    protected function normalize(string $group): string
    {
        // كلاس وارث من _CacheKeys؟ خد اسم المجموعة اللي جوّاه
        if (is_subclass_of($group, _CacheKeys::class)) {
            return $group::name();
        }

        return $group;
    }

    /**
     * قراءة رقم إصدار مجموعة معيّنة.
     *
     * ⚠️ انتبه: دي بتـ«ـقرا» الرقم المخزّن، مش بتحسب الوقت الحالي!
     *
     * rememberForever معناها: "لو المفتاح موجود هاتلي قيمته،
     * ولو مش موجود — وساعتها بس — نفّذ الدالة واحفظ نتيجتها".
     *
     * فالوقت بيتكتب مرة واحدة بس، ويفضل ثابت لحد ما تنادي invalidate().
     */
    protected function version(string $group): int
    {
        // ??= يعني: لو موجود في الذاكرة المؤقتة استخدمه،
        //           لو لأ اقراه من الكاش واحفظه هنا
        return $this->versions[$group] ??= (int) Cache::rememberForever(
            $this->versionKey($group),
            fn () => now()->timestamp
        );
    }

    /**
     * اسم مفتاح "الورقة" اللي متخزّن فيها رقم الإصدار.
     * cv = cache version
     *
     * ⚠️ البادئة cv: مش شكليات — من غيرها مفتاح الإصدار بيبقى
     *    اسمه زي أي مفتاح بيانات عادي، فـ Cache::put('Roles', ...)
     *    من أي مكان تاني هيدهس الإصدار ويضيّع الكاش كله،
     *    والعكس: invalidate('Roles') هيدهس بياناته هو.
     */
    protected function versionKey(string $group): string
    {
        return "cv:{$group}";
    }
}

/*
|==============================================================================
| ملاحظات
|==============================================================================
|
| • حط TTL على كل مفتاح — المفاتيح القديمة بتفضل واخدة مساحة لحد
|   ما تنتهي مدتها.
|
| • مفاتيح الإصدار اسمها cv:* ومالهاش TTL عن قصد — لازم تعيش أطول
|   من البيانات اللي بتحميها.
|
| • على البرودكشن في redis.conf:  maxmemory-policy allkeys-lru
|
| • أحسن مكان للإلغاء هو الموديل نفسه، عشان مستحيل تنسى:
|
|       protected static function booted(): void
|       {
|           static::saved(fn () => static::clearCache());
|           static::deleted(fn () => static::clearCache());
|       }
|
| • باجات الكاش صامتة — مفيش أخطاء، بس بيانات قديمة. فبعد أي تعديل
|   جرّب يدوي: عدّل سجل → Refresh → اتأكد إنه اتغيّر.
|
*/
