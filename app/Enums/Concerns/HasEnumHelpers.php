<?php

namespace App\Enums\Concerns;

/**
 * سلوك مشترك لكل الـ enums في المشروع.
 *
 * كل enum بيعرّف label() و badge() الخاصين بيه، والـ trait ده بيوفر
 * الدوال اللي بتتكرر: القيم، الخيارات للـ select، والتحقق من الصلاحية.
 */
trait HasEnumHelpers
{
    /**
     * كل القيم كنصوص — للاستخدام في قواعد التحقق.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * قاعدة تحقق جاهزة: 'required|in:draft,approved,...'
     */
    public static function rule(): string
    {
        return 'in:' . implode(',', self::values());
    }

    /**
     * خيارات جاهزة للقوائم المنسدلة: [القيمة => الاسم المترجم]
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /** تحويل آمن من نص — بيرجع null بدل ما يرمي استثناء. */
    public static function fromValue(?string $value): ?static
    {
        return $value === null ? null : self::tryFrom($value);
    }

    /** HTML جاهز للبادج في الجداول. */
    public function badgeHtml(): string
    {
        return '<span class="badge ' . $this->badge() . '">' . e($this->label()) . '</span>';
    }

    public function is(self ...$cases): bool
    {
        return in_array($this, $cases, true);
    }

    public function isNot(self ...$cases): bool
    {
        return !$this->is(...$cases);
    }
}
