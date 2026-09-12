# خطة تنفيذ: وحدة اختصار الروابط (Links)

> خطة مكتوبة على أساس كود SBBS الفعلي.
> كل تعارضات المواصفة الأصلية اتشالت، والأفكار الصحيحة بس اتاخدت.

---

## ملخّص القرارات

| القرار | الاختيار | السبب |
|---|---|---|
| الفاعل | `admins` مش `users` | مافيش جدول users في المشروع |
| الصلاحيات | `RoleService::map()` + `admin_can()` | نظام المشروع، مش spatie |
| العزل | `ScopedToBranch` + `branch_id` | بيحمي Route Model Binding كمان (IDOR) |
| سجل التدقيق | `LogsActivity` trait | جاهز وبيكتب عبر الطابور |
| مسار الريدايركت | `/s/{code}` | `web.php` نضيف، مافيش تعارض. مش محتاج دومين منفصل |
| نوع الريدايركت | 302 + `no-store` | 301 بيتكاش للأبد → العدّاد يقف والوجهة تتقفل |
| `short_code` | `utf8mb4_bin` | الافتراضي case-insensitive → تصادمات وفقد ثلثي الـ keyspace |
| العدّاد | Redis + طابور | الريدايركت مايستناش الكتابة |
| الإحصائيات | `link_daily_stats` تجميعي | مافيش `COUNT(*)` على جدول الكليكات أبداً |
| الـ IP | `sha256` مهشَّر | امتثال + كفاية للتفرقة بين الزوار |
| الجداول | yajra + select2 | زي كل الموديولات |
| الترجمة | `lang/ar` + `lang/en` | `TranslationParityTest` بيفشل لو مفتاح ناقص |

---

## المرحلة ١ — الأساس

**الهدف:** أقدر أعمل رابط مختصر، أفتحه، يتحوّل، والعدّاد يزيد.

### 1.1 الميجريشن `links`

```php
Schema::create('links', function (Blueprint $table) {
    $table->id();
    $table->string('short_code', 32);
    $table->text('original_url');
    $table->string('title')->nullable();
    $table->text('notes')->nullable();

    $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();

    $table->boolean('is_active')->default(true);
    $table->timestamp('expires_at')->nullable();
    $table->unsignedInteger('max_clicks')->nullable();

    $table->unsignedBigInteger('clicks_count')->default(0);
    $table->unsignedBigInteger('unique_clicks_count')->default(0);
    $table->timestamp('last_clicked_at')->nullable();

    $table->timestamps();
    $table->softDeletes();

    $table->index(['branch_id', 'created_at']);
    $table->index('is_active');
});

// إجباري: الـ collation الافتراضي case-insensitive
DB::statement('ALTER TABLE links MODIFY short_code VARCHAR(32)
               CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
DB::statement('ALTER TABLE links ADD UNIQUE INDEX links_short_code_unique (short_code)');
```

> `constrained('admins')` مش `constrained('users')` — الفاعل في المشروع هو `Admin`.
> و `nullable()->nullOnDelete()` علشان حذف المشرف مايحذفش الروابط.

### 1.2 الموديل `app/Models/Link.php`

```php
class Link extends Model
{
    use HasFactory, SoftDeletes, ScopedToBranch, LogsActivity, HasDateRangeScopes;

    protected string $dateRangeColumn = 'created_at';

    protected $fillable = [
        'short_code', 'original_url', 'title', 'notes',
        'branch_id', 'created_by', 'is_active',
        'expires_at', 'max_clicks',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'expires_at'      => 'datetime',
        'last_clicked_at' => 'datetime',
        'max_clicks'      => 'integer',
    ];

    // مسح الكاش عند أي تغيير — وإلا الريدايركت يفضل على الوجهة القديمة
    protected static function booted(): void
    {
        static::saved(fn (Link $l)   => Cache::forget("link:{$l->short_code}"));
        static::deleted(fn (Link $l) => Cache::forget("link:{$l->short_code}"));
    }

    public function getShortUrlAttribute(): string
    {
        return url('/s/' . $this->short_code);
    }

    public function getIsExpiredAttribute(): bool
    {
        return ($this->expires_at && now()->gt($this->expires_at))
            || ($this->max_clicks && $this->clicks_count >= $this->max_clicks);
    }

    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function creator(): BelongsTo  { return $this->belongsTo(Admin::class, 'created_by'); }
    public function clicks(): HasMany     { return $this->hasMany(LinkClick::class); }
    public function dailyStats(): HasMany { return $this->hasMany(LinkDailyStat::class); }
}
```

> مافيش `scopeVisibleTo` مكتوب بإيد — `ScopedToBranch` بيوفّر العزل تلقائياً
> على كل استعلام، بما فيها Route Model Binding.

### 1.3 `app/Services/Links/ShortCodeGenerator.php`

أبجدية من غير `0 O 1 l I` (لبس عند الكتابة اليدوية)، 7 خانات،
وقائمة محجوزات فيها مسارات المشروع الفعلية: `admin` و `filemanager` و `s`.

```php
public function isAvailable(string $code): bool
{
    if (in_array(strtolower($code), self::RESERVED, true)) {
        return false;
    }

    // مافيش BINARY: العمود نفسه utf8mb4_bin فالمقارنة case-sensitive أصلاً،
    // و BINARY كان هيمنع MySQL من استخدام الـ index — full scan على أكتر
    // استعلام تكراراً في النظام.
    return ! Link::withTrashed()->withoutGlobalScopes()
        ->where('short_code', $code)->exists();
}
```

> `withoutGlobalScopes()` ضروري هنا: تفرّد الكود عام على مستوى النظام،
> مش لكل فرع — من غيرها ممكن يتولّد نفس الكود في فرعين.

### 1.4 `app/Rules/SafeUrl.php`

http/https بس · منع الشبكة الداخلية (`127.0.0.1`, `.local`, `.internal`, نطاقات خاصة)
· منع اختصار رابط من نفس النظام (لوب لا نهائي).

### 1.5 الراوتات

في `routes/web.php` **آخر الملف** (بعد مجموعة admin):

```php
Route::get('/s/{code}', [RedirectController::class, 'handle'])
    ->where('code', '[A-Za-z0-9_-]{3,32}')
    ->middleware('throttle:WebRateLimiter')
    ->name('link.redirect');
```

وفي `routes/admin.php` بنفس نمط باقي الموديولات:

```php
Route::get('/links/trash', [LinkController::class, 'trash'])
    ->name('links.trash')->middleware('AdminPermission:link_trash');
Route::post('/links/{id}/restore', [LinkController::class, 'restore'])
    ->name('links.restore')->middleware('AdminPermission:link_restore');
Route::delete('/links/{id}/force-delete', [LinkController::class, 'forceDelete'])
    ->name('links.forceDelete')->middleware('AdminPermission:link_force_delete');

Route::get('/links/{link}/qr', [LinkController::class, 'qr'])
    ->name('links.qr')->middleware('AdminPermission:link_show');
Route::get('/links/{link}/stats', [LinkStatsController::class, 'show'])
    ->name('links.stats')->middleware('AdminPermission:link_stats');

Route::resource('/links', LinkController::class);
```

### 1.6 الصلاحيات — في `RoleService::map()`

```php
'link' => [
    'label' => 'link.title',
    'items' => [
        'link_show', 'link_create', 'link_update', 'link_delete',
        'link_trash', 'link_restore', 'link_force_delete',
        'link_stats',
    ],
],
```

### 1.7 الكنترولر — نفس نمط `ExpenseController`

- `->select('links.*')` **إلزامي** — yajra بيعمل join تلقائي للترتيب بأعمدة
  العلاقات، و `select *` ساعتها بيخلي أعمدة الجدول المنضم تدهس `links.id`
- `addColumn` + `filterColumn` للأعمدة المحسوبة (الحالة، الفرع، الرابط المختصر)
- فلاتر select2: الفرع / الحالة / نطاق تاريخ (افتراضي الشهر الحالي)
- زر نسخ الرابط المختصر في الجدول

### 1.8 الترجمة

`lang/ar/link.php` + `lang/en/link.php` — **الاتنين**، وإلا `TranslationParityTest` يفشل.
مافيش أي نص عربي مكتوب مباشرة في الكود.

### 1.9 القائمة الجانبية

عنصر في `aside.blade.php` محكوم بـ `@if (admin_can('link_show'))`.

**مخرجات المرحلة:** ميجريشن + موديل + مولّد + SafeUrl + ريدايركت شغّال
+ CRUD كامل + سلة محذوفات + عدّاد بسيط (`increment` مباشر).

---

## المرحلة ٢ — التتبّع

**الهدف:** أعرف مين فتح الرابط وإمتى ومن فين، من غير ما الريدايركت يبطّأ.

### 2.1 ميجريشن `link_clicks`

```php
$table->foreignId('link_id')->constrained()->cascadeOnDelete();
$table->string('ip_hash', 64)->nullable();        // مفيش IP خام
$table->string('visitor_hash', 64)->nullable();
$table->string('device_type', 20)->nullable();    // desktop|mobile|tablet
$table->string('os', 40)->nullable();
$table->string('browser', 40)->nullable();
$table->string('country_code', 2)->nullable();
$table->string('referrer_domain', 190)->nullable();
$table->boolean('is_bot')->default(false);        // تتعلّم ولا تتحذف
$table->timestamp('clicked_at')->useCurrent();

$table->index(['link_id', 'clicked_at']);
$table->index(['link_id', 'visitor_hash']);
$table->index(['is_bot', 'clicked_at']);
```

### 2.2 `BotDetector`

من غيره: ٤٠ زيارة وهمية بمجرد إرسال الرابط في واتساب (معاينة الروابط).
البوتات تتعلّم `is_bot = true` ولا تتحذف — تفضل قابلة للمراجعة.

### 2.3 `ClickRecorder` + `RecordLinkClick` job

- العدّاد يزيد في **Redis** فوراً (`CACHE_STORE=redis` متاح ومضبوط ✅)
- التفاصيل تتدفع للطابور — الريدايركت مايستناش الكتابة

> ⚠️ ملاحظة على البيئة: `QUEUE_CONNECTION=database` في المشروع.
> العدّاد في Redis فالريدايركت سريع، بس صف الـ job بيتكتب في جدول `jobs`.
> مقبول لترافيك متوسط. لو الترافيك زاد: حوّل لـ `QUEUE_CONNECTION=redis`
> (Redis متاح ومضبوط أصلاً — DB 10/11/12).

### 2.4 `FlushLinkCounters` — أمر مجدول

بيفرّغ عدّادات Redis لأعمدة `clicks_count` / `unique_clicks_count` كل ٥ دقايق.
يتسجّل في `routes/console.php` بنفس نمط `petty-cash:carry-over`.

---

## المرحلة ٣ — الإحصائيات

### 3.1 ميجريشن `link_daily_stats`

```php
$table->foreignId('link_id')->constrained()->cascadeOnDelete();
$table->date('date');
$table->unsignedInteger('clicks')->default(0);
$table->unsignedInteger('unique_clicks')->default(0);
$table->unsignedInteger('bot_clicks')->default(0);
$table->json('by_country')->nullable();   // {"SA": 120, "EG": 45}
$table->json('by_device')->nullable();
$table->json('by_referrer')->nullable();
$table->unique(['link_id', 'date']);
$table->index('date');
```

### 3.2 أمر التجميع اليومي

بيلخّص `link_clicks` في `link_daily_stats` كل ليلة.

### 3.3 شاشة الإحصائيات

- كل الشارتات من الجدول التجميعي — **مفيش مساس بـ `link_clicks`** ولا `COUNT(*)`
- ApexCharts بنفس إعدادات `home/index.blade.php`: متغيّرات الثيم
  (`textMuted` / `textStrong` / `surfaceColor`) علشان تشتغل دارك ولايت
- فلتر نطاق تاريخ زي باقي التقارير
- التصدير عبر `ExcelExportService` الموجود

---

## المرحلة ٤ — إضافات

- **QR**: `simplesoftwareio/simple-qrcode` — SVG من `$link->short_url`
- **حماية بكلمة مرور** (اختياري)
- **UTM** على الوجهة
- صفحات `links.not-found` (404) و `links.unavailable` (410) بتصميم Vuexy

---

## الحزم

**محتاجين:**

```bash
composer require simplesoftwareio/simple-qrcode   # مرحلة ٤
composer require jenssegers/agent                 # مرحلة ٢ (أو كاشف بسيط بدالها)
```

**مش محتاجين** — عندك بدائل أفضل:

- ❌ `spatie/laravel-permission` → `RoleService` + `admin_can()`
- ❌ `spatie/laravel-activitylog` → `LogsActivity` + `ActivityLogger` (عبر الطابور)
- ❌ `geoip2/geoip2` → اختياري، بس لو عايز تفصيل الدول

---

## الاختبارات

على نفس منهج المشروع — وكل اختبار لازم **يقع لما الباج يترجع**:

| الاختبار | بيمنع |
|---|---|
| `test_short_code_is_case_sensitive` | فقد ثلثي الـ keyspace وتصادمات وهمية |
| `test_redirect_is_302_not_301` | توقّف العدّاد وقفل الوجهة للأبد |
| `test_cache_is_cleared_on_update` | التحويل للوجهة القديمة بعد التعديل |
| `test_cannot_shorten_internal_url` | SSRF |
| `test_cannot_shorten_own_short_url` | لوب لا نهائي |
| `test_links_are_scoped_to_branch` | تسريب بيانات بين الفروع |
| `test_expired_link_returns_410` | رابط منتهي لسه شغّال |
| `test_reserved_codes_are_rejected` | ابتلاع راوتات النظام |
| `test_bot_clicks_do_not_inflate_counter` | إحصائيات وهمية |
| `test_permission_guards_every_route` | تجاوز الصلاحيات |
| `TranslationParityTest` (قائم) | مفاتيح ترجمة ناقصة |

---

## أخطاء لازم نتجنّبها

| الخطأ | النتيجة |
|---|---|
| نسيان `->select('links.*')` | الترتيب بعمود علاقة يكسر الـ id والأكشنز |
| راوت `/{code}` في أول `web.php` | يبتلع كل راوتات النظام |
| `COUNT(*)` على `link_clicks` في الشاشة | الصفحة تنهار بعد ملايين الصفوف |
| 301 بدل 302 | العدّاد يقف بعد أول زيارة |
| نسيان `withoutGlobalScopes()` في فحص التفرّد | كودين متطابقين في فرعين |
| رسايل عربي في الكود بدل `lang/` | كسر تكافؤ الترجمة |
| تخزين الـ IP خام | مسؤولية قانونية بلا فائدة إضافية |
