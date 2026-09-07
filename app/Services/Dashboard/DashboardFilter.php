<?php

namespace App\Services\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * فلاتر لوحة التحكم.
 *
 * اللوحة كانت مربوطة بالشهر الحالي بشكل ثابت. الكائن ده بيجمّع
 * اختيارات المستخدم (الفترة، الفرع، العملة) في مكان واحد عشان كل
 * استعلامات اللوحة تشتغل بنفس النطاق من غير تكرار.
 */
class DashboardFilter
{
    public function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly ?int $branchId = null,
        public readonly ?string $currency = null,
        public readonly string $preset = 'this_month',
    ) {
    }

    /** الفترات الجاهزة المتاحة في الواجهة. */
    public const PRESETS = [
        'this_month',
        'last_month',
        'this_quarter',
        'this_year',
        'last_12_months',
        'custom',
    ];

    /** بناء الفلتر من الطلب مع تحقّق دفاعي من المدخلات. */
    public static function fromRequest(Request $request, ?CarbonImmutable $now = null): self
    {
        $now    = $now ?? CarbonImmutable::now();
        $preset = (string) $request->input('preset', 'this_month');

        if (! in_array($preset, self::PRESETS, true)) {
            $preset = 'this_month';
        }

        [$from, $to] = self::resolveRange($preset, $request, $now);

        // لو المستخدم عكس التواريخ بنصلّحها بدل ما نرجّع نتيجة فاضية
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $branchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
        $currency = $request->filled('currency') ? (string) $request->input('currency') : null;

        return new self($from, $to, $branchId ?: null, $currency, $preset);
    }

    /** الفلتر الافتراضي: الشهر الحالي. */
    public static function default(?CarbonImmutable $now = null): self
    {
        $now = $now ?? CarbonImmutable::now();

        return new self($now->startOfMonth(), $now->endOfMonth(), null, null, 'this_month');
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private static function resolveRange(string $preset, Request $request, CarbonImmutable $now): array
    {
        return match ($preset) {
            'last_month'     => [$now->subMonth()->startOfMonth(), $now->subMonth()->endOfMonth()],
            'this_quarter'   => [$now->startOfQuarter(), $now->endOfQuarter()],
            'this_year'      => [$now->startOfYear(), $now->endOfYear()],
            'last_12_months' => [$now->subMonths(11)->startOfMonth(), $now->endOfMonth()],
            'custom'         => [
                self::parse($request->input('date_from'), $now->startOfMonth()),
                self::parse($request->input('date_to'), $now->endOfMonth())->endOfDay(),
            ],
            default          => [$now->startOfMonth(), $now->endOfMonth()],
        };
    }

    /** تاريخ غير صالح مايكسرش اللوحة — بنرجع للقيمة الافتراضية. */
    private static function parse(mixed $value, CarbonImmutable $fallback): CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return $fallback;
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    public function bounds(): array
    {
        return [$this->from, $this->to];
    }

    /** عدد الشهور في النطاق — بيحدّد دقة رسم الاتجاه. */
    public function monthsInRange(): int
    {
        return max(1, $this->from->startOfMonth()->diffInMonths($this->to->startOfMonth()) + 1);
    }

    public function hasBranch(): bool
    {
        return $this->branchId !== null;
    }

    public function hasCurrency(): bool
    {
        return $this->currency !== null && $this->currency !== '';
    }

    /** وصف الفترة للعرض على الشاشة. */
    public function label(): string
    {
        if ($this->preset !== 'custom') {
            return __('dashboard.presets.' . $this->preset);
        }

        return __('dashboard.filters.range', [
            'from' => $this->from->format('Y-m-d'),
            'to'   => $this->to->format('Y-m-d'),
        ]);
    }

    /** الحالة الحالية للواجهة عشان تعيد بناء الفورم. */
    public function toArray(): array
    {
        return [
            'preset'    => $this->preset,
            'date_from' => $this->from->format('Y-m-d'),
            'date_to'   => $this->to->format('Y-m-d'),
            'branch_id' => $this->branchId,
            'currency'  => $this->currency,
            'label'     => $this->label(),
        ];
    }
}
