<?php

namespace App\Models;

use App\Services\Cache\Keys\SettingsCache;
use App\Services\Cache\CacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Settings extends Model
{
    protected $fillable = [
        'key',
        'group',
        'value',
        'options',
        'type',
        'is_required',
        'col_size',
        'order',
        'dir',
    ];

    protected $casts = [
        'options'     => 'array',
        'is_required' => 'boolean',
        'col_size'    => 'integer',
        'order'       => 'integer',
        'dir'         => 'string',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }

    // =========================================
    // Cache
    // =========================================

    /**
     * الـ CacheService (singleton من الـ container).
     */
    protected static function cache(): CacheService
    {
        return app(CacheService::class);
    }

    /**
     * ⭐ مسح كاش الإعدادات كله.
     *
     * سطر واحد بيلغي كل مفاتيح مجموعة settings دفعة واحدة —
     * مفيش لف على الـ keys ولا على الـ groups، ومفيش استعلامات
     * زيادة على الداتابيز.
     *
     * بيتنادى أوتوماتيك مع أي saved أو deleted.
     */
    public static function clearCache(): void
    {
        static::cache()->invalidate(SettingsCache::class);
    }

    // =========================================
    // Accessors
    // =========================================

    // جلب الـ label من ملف الترجمة
    public function getLabelAttribute(): string
    {
        return trans("setting.{$this->key}.label");
    }

    // جلب الـ hint من ملف الترجمة
    public function getHintAttribute(): string
    {
        return trans("setting.{$this->key}.hint");
    }

    // جلب الـ placeholder من ملف الترجمة
    public function getPlaceholderAttribute(): string
    {
        return trans("setting.{$this->key}.placeholder");
    }

    // =========================================
    // Static Methods
    // =========================================

    // جلب قيمة setting معينة بالـ key
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::allValues()[$key] ?? null;

        return $value ?? $default;
    }

    /**
     * كل الإعدادات (key => value) في مفتاح كاش واحد.
     *
     * قبل كده كل key كان ليه مفتاح واستعلام لوحده، فالصفحة اللي بتقرا
     * ١٥ إعداد كانت بتضرب ١٥ استعلام أول مرة. دلوقتي استعلام واحد بس،
     * وبيتلغي مع باقي المجموعة في clearCache().
     */
    public static function allValues(): array
    {
        return static::cache()->remember(
            SettingsCache::class,
            SettingsCache::ALL_VALUES,
            fn () => static::whereNotNull('key')->pluck('value', 'key')->all()
        );
    }

    // تعديل قيمة setting معينة
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key'   => $key],
            ['value' => $value]
        );

        // مش محتاجين نمسح حاجة هنا — الـ saved event بينادي
        // clearCache() لوحده.
    }

    // جلب group كامل
    public static function getGroup(string $group): Collection
    {
        $data = static::cache()->remember(
            SettingsCache::class,
            SettingsCache::group($group),
            fn () => static::where('group', $group)
                ->orderBy('order')
                ->get()
                ->toArray()
        );

        return static::hydrateCached($data);
    }

    // جلب كل الـ settings مجمعة حسب group
    public static function getGrouped(): Collection
    {
        $data = static::cache()->remember(
            SettingsCache::class,
            SettingsCache::GROUPED,
            fn () => static::where('type', '!=', 'section')
                ->orderBy('group')
                ->orderBy('order')
                ->get()
                ->toArray()
        );

        return static::hydrateCached($data)->groupBy('group');
    }

    // كل أسماء الـ groups الموجودة
    public static function getAllGroups(): Collection
    {
        return collect(static::cache()->remember(
            SettingsCache::class,
            SettingsCache::ALL_GROUPS,
            fn () => static::distinct()->pluck('group')->toArray()
        ));
    }

    /**
     * تحويل الـ arrays المتخزّنة في الكاش لـ models تاني.
     *
     * بنخزّن arrays مش models عشان الـ serialization يبقى أخف
     * وميعتمدش على شكل الكلاس.
     */
    protected static function hydrateCached(array $data): Collection
    {
        return collect($data)->map(fn (array $item) => (new static)->forceFill($item));
    }

    // =========================================
    // Scopes
    // =========================================

    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeSections($query)
    {
        return $query->where('type', 'section');
    }

    public function scopeInputs($query)
    {
        return $query->where('type', '!=', 'section');
    }
}


// =========================================
// جلب قيمة واحدة بالـ key
// =========================================

// // جلب قيمة بدون default
// Settings::get('site_name');
// // ← ترجع: 'My App' أو null لو مش موجودة

// // جلب قيمة مع default لو مش موجودة
// Settings::get('site_name', 'My App');
// // ← ترجع: القيمة المخزنة أو 'My App'


// // =========================================
// // تعديل قيمة
// // =========================================

// // تعديل قيمة موجودة أو إنشاء جديدة
// Settings::set('site_name', 'New Name');
// // ← بيعمل updateOrCreate ويمسح كاش الـ key ده


// // =========================================
// // جلب group كامل (مرتب بالـ order)
// // =========================================

// // جلب كل settings الـ general group
// $generalSettings = Settings::getGroup('general');
// // ← ترجع: Collection من Settings models

// // جلب كل settings الـ mail group
// $mailSettings = Settings::getGroup('mail');

// // الاستخدام في الـ view
// @foreach ($mailSettings as $setting)
//     {{ $setting->label }}       // من ملف الترجمة
//     {{ $setting->hint }}        // من ملف الترجمة
//     {{ $setting->placeholder }} // من ملف الترجمة
//     {{ $setting->value }}       // القيمة المخزنة
//     {{ $setting->type }}        // نوع الـ input
//     {{ $setting->col_size }}    // حجم العمود (1-12)
//     {{ $setting->is_required }} // مطلوب أم لا (boolean)
//     {{ $setting->options }}     // الخيارات (array - للـ select والـ checkbox)
// @endforeach


// // =========================================
// // جلب كل الـ settings مجمعة بالـ group
// // =========================================

// // جلب كل الـ settings مجمعة
// $settings = Settings::getGrouped();
// // ← ترجع: Collection مقسمة بالـ group
// // [
// //   'general' => Collection([...]),
// //   'mail'    => Collection([...]),
// //   'seo'     => Collection([...]),
// // ]

// // الاستخدام في الـ view
// @foreach ($settings as $group => $fields)
//     <h5>{{ $group }}</h5>
//     @foreach ($fields as $setting)
//         {{ $setting->label }}
//         {{ $setting->value }}
//     @endforeach
// @endforeach


// // =========================================
// // مسح كل الـ cache
// // =========================================

// // مسح كاش كل الـ settings (keys + groups + grouped) دفعة واحدة
// Settings::clearCache();
// // ← بيتعمل أوتوماتيك عند أي saved أو deleted
// // ← تحت الغطا: $cache->invalidate(SettingsCache::class)

// // نفس الحاجة من أي مكان تاني في المشروع:
// app(\App\Services\Cache\CacheService::class)->invalidate(\App\Services\Cache\Keys\SettingsCache::class);


// // =========================================
// // الـ Scopes (للـ Query مباشرة بدون كاش)
// // =========================================

// // جلب settings group معين
// Settings::group('mail')->orderBy('order')->get();

// // جلب inputs فقط (بدون الـ sections)
// Settings::inputs()->where('group', 'general')->get();

// // جلب sections فقط (الفواصل)
// Settings::sections()->get();

// // تركيب أكتر من scope
// Settings::inputs()->group('mail')->orderBy('order')->get();