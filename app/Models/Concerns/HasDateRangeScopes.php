<?php

namespace App\Models\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * فلترة بالتاريخ من غير ما نعطّل الفهارس.
 *
 * whereYear()/whereMonth() بتحط دالة على العمود، وده بيمنع MySQL من
 * استخدام الفهرس تماماً — الاستعلام بيتحول لـ full table scan:
 *
 *   WHERE YEAR(expense_date)=2026 AND MONTH(expense_date)=9
 *     → type=ALL   key=NULL   (بيقرأ الجدول كله)
 *
 *   WHERE expense_date BETWEEN '2026-09-01' AND '2026-09-30'
 *     → type=range key=expenses_expense_date_branch_id_idx
 *
 * الموديل بيحدد عمود التاريخ الافتراضي عبر $dateRangeColumn،
 * وإلا بنستخدم created_at.
 */
trait HasDateRangeScopes
{
    protected function dateRangeColumn(): string
    {
        return property_exists($this, 'dateRangeColumn')
            ? $this->dateRangeColumn
            : $this->getCreatedAtColumn();
    }

    /** كل سجلات شهر معيّن. */
    public function scopeInMonth(Builder $query, int|string $year, int|string $month, ?string $column = null): Builder
    {
        $column = $column ?: $this->dateRangeColumn();
        $start = CarbonImmutable::create((int) $year, (int) $month, 1)->startOfDay();

        return $query->whereBetween(
            $query->getModel()->getTable() . '.' . $column,
            [$start, $start->endOfMonth()->endOfDay()]
        );
    }

    /** كل سجلات سنة معيّنة. */
    public function scopeInYear(Builder $query, int|string $year, ?string $column = null): Builder
    {
        $column = $column ?: $this->dateRangeColumn();
        $start = CarbonImmutable::create((int) $year, 1, 1)->startOfDay();

        return $query->whereBetween(
            $query->getModel()->getTable() . '.' . $column,
            [$start, $start->endOfYear()->endOfDay()]
        );
    }

    /**
     * فترة بين تاريخين — أي طرف يقبل null.
     */
    public function scopeInPeriod(Builder $query, ?string $from, ?string $to, ?string $column = null): Builder
    {
        $column = $query->getModel()->getTable() . '.' . ($column ?: $this->dateRangeColumn());

        return $query
            ->when($from, fn ($q) => $q->where($column, '>=', CarbonImmutable::parse($from)->startOfDay()))
            ->when($to, fn ($q) => $q->where($column, '<=', CarbonImmutable::parse($to)->endOfDay()));
    }

    /**
     * فلتر "من يوم — لحد يوم" (شامل الطرفين) بمقارنة نطاق بدل whereDate().
     *
     *   col >= 'from'  AND  col < 'to + 1 day'
     *
     * بنقارن بنص التاريخ بس (Y-m-d) عشان يشتغل صح مع أعمدة DATE و DATETIME
     * على MySQL و SQLite (اللي ممكن تخزّن الـ DATE كـ '2026-09-01 00:00:00').
     * القيمة الغلط بتتجاهل بدل ما توقع الصفحة — زي whereDate زمان.
     */
    public function scopeBetweenDays(Builder $query, ?string $from, ?string $to, ?string $column = null): Builder
    {
        $column = $query->getModel()->getTable() . '.' . ($column ?: $this->dateRangeColumn());
        $from = static::parseRangeDay($from);
        $to   = static::parseRangeDay($to);

        return $query
            ->when($from, fn ($q) => $q->where($column, '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->where($column, '<', $to->addDay()->toDateString()));
    }

    protected static function parseRangeDay(?string $value): ?CarbonImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * حدود شهر كنطاق جاهز — للاستعلامات المكتوبة بـ DB::table
     * اللي مش بتعدّي على Eloquent scopes.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function monthBounds(int|string $year, int|string $month): array
    {
        $start = CarbonImmutable::create((int) $year, (int) $month, 1)->startOfDay();

        return [$start, $start->endOfMonth()->endOfDay()];
    }
}
