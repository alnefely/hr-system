# مهام تنفيذ وحدة الروابط — المرحلة ٢ (التتبّع)

> الحالة: ⬜ لسه · 🔄 شغال · ✅ خلصت · ⛔ متوقفة

المرجع: [LINKS_MODULE_PLAN.md](LINKS_MODULE_PLAN.md) · المرحلة ١: [LINKS_TASKS.md](LINKS_TASKS.md)

---

## قرار معماري: مفيش Jobs ولا طوابير

**طلب صريح من المستخدم.** كل العمل المؤجَّل بيتم عبر `Command` + `Schedule`.

ده بيغيّر تصميم التتبّع عن المواصفة الأصلية:

| المواصفة | اللي هنعمله | ليه |
|---|---|---|
| `RecordLinkClick` job لكل نقرة | كتابة مؤقتة في Redis | مفيش طوابير + `QUEUE_CONNECTION=database` كان هيضغط على MySQL |
| `FlushLinkCounters` job | `links:flush` أمر مجدول | نفس النتيجة من غير طابور |

**إزاي التحويل يفضل سريع:**
كل نقرة بتتكتب في Redis بس (`RPUSH` + `INCR`) — مفيش أي لمسة
لقاعدة البيانات في مسار التحويل. أمر مجدول بيفرّغ الطابور المؤقت
لجدول `link_clicks` والعدّادات كل ٥ دقايق.

**لو Redis وقع:** التحويل لازم يفضل شغّال. التسجيل بيتبلع في
`try/catch` — النقرة تضيع لكن المستخدم يوصل لوجهته.

---

## المجموعة ز — جدول النقرات

### ز١ ✅ ميجريشن `link_clicks`
- [x] `link_id` + `cascadeOnDelete`
- [x] `ip_hash` · `visitor_hash` (sha256) — **مفيش IP خام**
- [x] `device_type` · `os` · `browser` · `country_code`
- [x] `referrer_domain`
- [x] `is_bot` + `bot_name` — تتعلّم ولا تتحذف
- [x] `clicked_at` + فهارس

### ز٢ ✅ موديل `LinkClick`
- [x] `$timestamps = false` (عندنا `clicked_at`)
- [x] علاقة `link()`
- [x] scopes: `humans()` · `bots()`
- [x] ربط `clicks()` في موديل `Link`

---

## المجموعة ح — الكاشف والمسجّل

### ح١ ✅ `BotDetector`
- [x] كشف بالـ User-Agent (من غير حزم خارجية)
- [x] معاينات واتساب/تليجرام/فيسبوك/تويتر/سلاك
- [x] محركات البحث + أدوات المراقبة
- [x] بيرجع اسم البوت مش true/false بس

**ليه:** إرسال الرابط في جروب واتساب بيدي ٤٠ زيارة وهمية.

### ح٢ ✅ `ClickRecorder`
- [x] كتابة في Redis بس — **مفيش أي DB في مسار التحويل**
- [x] `visitor_hash` = sha256(ip + ua + link + salt يومي)
- [x] الزائر الفريد بـ Redis SET لليوم
- [x] `try/catch` شامل — التحويل مايقعش لو Redis وقع

### ح٣ ✅ ربطه في `RedirectController`
- [x] استبدال `DB::table()->update()` بالمسجّل
- [x] البوتات تتسجّل بس من غير ما تزوّد العدّاد الظاهر

---

## المجموعة ط — الأمر المجدول

### ط١ ✅ `links:flush`
- [x] يفرّغ طابور Redis لـ `link_clicks` (chunked insert)
- [x] يحدّث `clicks_count` · `unique_clicks_count` · `last_clicked_at`
- [x] `--limit` للتحكم في الدفعة
- [x] آمن لو Redis فاضي أو واقع

### ط٢ ✅ الجدولة في `routes/console.php`
- [x] `everyFiveMinutes()` + `withoutOverlapping()` + `onOneServer()`

### ط٣ ✅ `links:prune`
- [x] حذف نقرات أقدم من مدة الاحتفاظ (افتراضي ٩٠ يوم)
- [x] يومي — الأرشيف الدائم في `link_daily_stats` (مرحلة ٣)

---

## المجموعة ي — الاختبارات

| الاختبار | بيمنع |
|---|---|
| `test_redirect_does_not_touch_database` | رجوع الكتابة السنكرونس لمسار التحويل |
| `test_redirect_survives_redis_failure` | سقوط التحويل مع سقوط Redis |
| `test_bot_clicks_are_flagged` | إحصائيات وهمية |
| `test_flush_moves_clicks_to_database` | ضياع النقرات |
| `test_flush_updates_counters` | عدّاد مايتحدّثش |
| `test_flush_is_idempotent` | تكرار النقرات مع كل تشغيل |
| `test_unique_visitors_counted_once` | تضخيم الزوار الفريدين |
| `test_prune_respects_retention` | حذف بيانات لسه محتاجينها |

**القبول:** ✅ **٣٥٣ ناجح** (٣٣٤ + ١٩ جديد).

اتأكدنا إن الاختبارات مش فاضية برجوع ٥ باجات:
البوتات تزوّد العدّاد · الزائر المتكرر يتحسب فريد · إسناد بدل زيادة ·
السقف يتجاهل نقرات Redis · رجوع الكتابة السنكرونس. الخمسة وقعوا.

**الملفات:** `LinkClickTrackingTest` (١٢) · `LinkBotDetectorTest` (٧)

---

## سجل التنفيذ

| التاريخ | المهمة | الحالة | ملاحظة |
|---|---|---|---|
| 2026-09-12 | إنشاء ملف مهام المرحلة ٢ | ✅ | بدون jobs — أوامر مجدولة |
| 2026-09-12 | المجموعة ز — جدول النقرات | ✅ | IP مهشَّر، البوتات تتعلّم |
| 2026-09-12 | المجموعة ح — الكاشف والمسجّل | ✅ | صلحنا `sadd` بمصفوفة (العد الفريد كان بايظ) |
| 2026-09-12 | المجموعة ط — الأوامر المجدولة | ✅ | `LPOP count` محتاج Redis 6.2 والسيرفر 5.0 → `LRANGE`+`LTRIM` |
| 2026-09-12 | المجموعة ي — الاختبارات | ✅ | ١٨ اختبار + عزل Redis للاختبارات (DB 15) |
| 2026-09-12 | **المرحلة ٢ خلصت** | ✅ | السويت: ٣٥٣ ناجح · اختبار الصمود مسك 500 عند سقوط Redis |
