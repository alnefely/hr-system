# مهام تنفيذ وحدة الروابط — المرحلة ١

> ملف تتبّع العمل. كل مهمة ليها معيار قبول واضح.
> الحالة: ⬜ لسه · 🔄 شغال · ✅ خلصت · ⛔ متوقفة

المرجع: [LINKS_MODULE_PLAN.md](LINKS_MODULE_PLAN.md)

---

## قواعد ملزمة في كل مهمة

دي مش نصايح — دي قواعد الكود القائم، كسرها بيكسر اختبارات موجودة:

1. **`->select('links.*')`** في أي استعلام DataTables — yajra بيعمل join تلقائي
   للترتيب بأعمدة العلاقات، و `select *` بيخلي أعمدة الجدول المنضم تدهس `links.id`
2. **مفيش نص عربي في الكود** — كله `lang/ar` + `lang/en`، والاتنين مع بعض
3. **`admin_can()` مش `$user->can()`** — والراوت محمي بـ `AdminPermission:`
4. **`Admin` مش `User`** — مافيش جدول users في المشروع
5. **`ScopedToBranch`** للعزل — مش `scopeVisibleTo` مكتوب بإيد
6. **كل اختبار لازم يقع لما الباج يترجع** — أثبت ده قبل ما تقول خلصت

---

## المجموعة أ — قاعدة البيانات والموديل

### أ١ ✅ ميجريشن `links`
- [x] `2026_09_12_100001_create_links_table.php`
- [x] `constrained('admins')` للـ `created_by` — **مش** `users`
- [x] `branch_id` nullable + `nullOnDelete`
- [x] `DB::statement` لفرض `utf8mb4_bin` + unique index
- [x] `down()` بيرجّع الجدول

**القبول:** `php artisan migrate` يعدّي، و `SHOW FULL COLUMNS` يقول `utf8mb4_bin`

### أ٢ ✅ موديل `Link`
- [x] `use HasFactory, SoftDeletes, ScopedToBranch, LogsActivity, HasDateRangeScopes`
- [x] `booted()` بيمسح الكاش على `saved` و `deleted`
- [x] `getShortUrlAttribute` · `getIsExpiredAttribute`
- [x] علاقات: `branch` · `creator` (Admin) · `clicks` · `dailyStats`

**القبول:** إنشاء رابط من tinker وقراءة `short_url` بيطلع `/s/CODE`

### أ٣ ⛔ Factory — **اتلغت**
المشروع مافيهوش factories غير `UserFactory` الافتراضية؛ الاختبارات
بتبني بياناتها مباشرة. اتمشينا على نفس النمط.

---

## المجموعة ب — الخدمات

### ب١ ✅ `ShortCodeGenerator`
- [x] أبجدية من غير `0 O 1 l I`
- [x] `RESERVED` فيها مسارات المشروع: `admin` · `filemanager` · `s` · `login`
- [x] `isAvailable()` بـ **`withTrashed()->withoutGlobalScopes()`** — التفرّد عام مش لكل فرع،
      والكود المحذوف يفضل محجوز
- [x] **مفيش `whereRaw('BINARY ...')`** — بيعطّل الـ index والعمود `utf8mb4_bin` أصلاً
- [x] `validateCustom()` للكود المخصص

**القبول:** اختبار بيولّد ١٠٠٠ كود من غير تكرار

### ب٢ ✅ `SafeUrl` rule
- [x] http/https بس
- [x] منع `localhost` · `127.0.0.1` · `.local` · `.internal` · النطاقات الخاصة
- [x] منع اختصار رابط من نفس النظام (لوب)
- [x] رسايل من `lang/` مش عربي في الكود

**القبول:** اختبار SSRF واختبار اللوب الاتنين بيقعوا لما الحماية تتشال

### ب٣ ✅ `LinkService`
- [x] `create()` — كود مخصص أو مولّد
- [x] `update()` — تغيير الوجهة/الكود + مسح الكاش (القديم والجديد)

---

## المجموعة ج — الريدايركت

### ج١ ✅ `RedirectController`
- [x] `resolve()` بكاش (`link:{code}`)
- [x] **302** + `Cache-Control: no-store` — مش 301
- [x] رابط غير موجود → 404 · منتهي/موقوف → 410
- [x] زيادة العدّاد (المرحلة ١: `increment` مباشر)

### ج٢ ✅ الراوت
- [x] `/s/{code}` **آخر `web.php`** + regex constraint
- [x] `throttle:WebRateLimiter`

### ج٣ ✅ صفحات الخطأ
- [x] `links.not-found` (404) · `links.unavailable` (410) بتصميم Vuexy

**القبول:** فتح `/s/CODE` بيحوّل بـ 302 والعدّاد زاد

---

## المجموعة د — الصلاحيات واللغة

### د١ ✅ الصلاحيات
- [x] مجموعة `link` في `RoleService::map()`
- [x] `link_show` · `_create` · `_update` · `_delete` · `_trash` · `_restore` · `_force_delete` · `_stats`

### د٢ ✅ الترجمة
- [x] `lang/ar/link.php` + `lang/en/link.php` — **الاتنين**

**القبول:** `TranslationParityTest` عدّى

---

## المجموعة هـ — الشاشات

### هـ١ ✅ `LinkController`
- [x] `index` بـ yajra + **`->select('links.*')`**
- [x] `addColumn` + `filterColumn` للأعمدة المحسوبة
- [x] فلاتر select2: الفرع / الحالة / نطاق تاريخ (افتراضي الشهر الحالي)
- [x] `store` · `update` · `destroy` · `trash` · `restore` · `forceDelete`

### هـ٢ ✅ FormRequests
- [x] `StoreLinkRequest` + `UpdateLinkRequest` مع `SafeUrl`

### هـ٣ ✅ الفيوهات
- [x] `index` · `create` · `edit` · `trash`
- [x] زر نسخ الرابط المختصر

### هـ٤ ✅ الراوتات + القائمة الجانبية
- [x] `routes/admin.php` بنمط باقي الموديولات
- [x] عنصر `aside.blade.php` محكوم بـ `admin_can('link_show')`

---

## المجموعة و — الاختبارات

### و١ ✅ `LinkRedirectTest` — ٩ اختبارات
- [x] `test_redirect_is_302_not_301`
- [x] `test_click_increments_counter`
- [x] `test_unknown_code_returns_404`
- [x] `test_inactive_link_returns_410`
- [x] `test_expired_link_returns_410`
- [x] `test_max_clicks_blocks_further_redirects`
- [x] `test_cache_is_cleared_when_destination_changes`
- [x] `test_old_code_stops_working_after_rename`
- [x] `test_deleted_link_does_not_redirect` ← مسك ثغرة حقيقية

### و٢ ✅ `ShortCodeGeneratorTest` — ٧ اختبارات
- [x] `test_generates_unique_codes`
- [x] `test_avoids_ambiguous_characters`
- [x] `test_uniqueness_is_case_sensitive`
- [x] `test_reserved_codes_are_rejected`
- [x] `test_trashed_codes_stay_reserved`
- [x] `test_migration_enforces_binary_collation`
- [x] `test_custom_code_validation`

### و٣ ✅ `SafeUrlTest` — ٧ اختبارات
- [x] `test_accepts_normal_public_urls`
- [x] `test_rejects_non_http_schemes`
- [x] `test_rejects_internal_hosts`
- [x] `test_rejects_private_ip_ranges`
- [x] `test_rejects_own_short_links`
- [x] `test_allows_other_pages_on_same_domain`
- [x] `test_rejects_malformed_urls`

### و٤ ✅ `LinkPermissionTest` — ٧ اختبارات
- [x] `test_index_requires_permission`
- [x] `test_index_allowed_with_permission`
- [x] `test_store_requires_create_permission`
- [x] `test_store_works_with_create_permission`
- [x] `test_store_rejects_internal_url`
- [x] `test_links_are_scoped_to_branch` (شامل IDOR)
- [x] `test_redirect_ignores_branch_scope`

### و٥ ✅ اختبارات الكاش
مدمجة في `LinkRedirectTest` بدل ملف مستقل.

**القبول النهائي:** ✅ السويت كله عدّى — **٣٣٤ ناجح** (٣٠٤ قديم + ٣٠ جديد).

اتأكدنا إن الاختبارات مش فاضية بإرجاع ٦ باجات والتحقق إن كل واحد بيوقع اختباره:
301 بدل 302 · شيل مسح الكاش · شيل حماية الشبكة الداخلية ·
شيل `ScopedToBranch` · شيل الـ collation · شيل حماية الحذف.

---

## سجل التنفيذ

| التاريخ | المهمة | الحالة | ملاحظة |
|---|---|---|---|
| 2026-09-12 | إنشاء ملف المهام | ✅ | |
| 2026-09-12 | المجموعة أ — قاعدة البيانات والموديل | ✅ | `utf8mb4_bin` متأكَّد منه على MySQL الفعلي |
| 2026-09-12 | المجموعة ب — الخدمات | ✅ | من غير `BINARY` (بيعطّل الـ index) |
| 2026-09-12 | المجموعة ج — الريدايركت | ✅ | 302 + `no-store`، و404/410 متأكَّد منهم |
| 2026-09-12 | المجموعة د — الصلاحيات واللغة | ✅ | `TranslationParityTest` عدّى |
| 2026-09-12 | المجموعة هـ — الشاشات | ✅ | الفلتر اتصلح: `whereColumn` مع NULL |
| 2026-09-12 | المجموعة و — الاختبارات | ✅ | 30 اختبار، كلهم بيقعوا لما الباج يرجع |
| 2026-09-12 | **المرحلة ١ خلصت** | ✅ | السويت: 334 ناجح |
