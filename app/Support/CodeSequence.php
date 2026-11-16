<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * الرقم التالي في تسلسل أكواد زي EMP-0007 أو INV-202611-0042.
 *
 * ليه مش count() + 1؟
 *  - لو سجل اتنقل لفرع تاني أو اتحذف نهائياً، العدد بيقل والكود اللي
 *    بيطلع بيبقى موجود فعلاً → خطأ unique (500).
 *  - مع مشرف فرع، count() بيعدّ سجلات فرعه بس (Global Scope)، والكود
 *    unique على مستوى النظام كله → تكرار مضمون.
 *
 * الحل: أكبر رقم مستخدم فعلاً للبادئة دي + 1، من غير أي Global Scope
 * (لا عزل فروع ولا SoftDeletes) عشان نشوف كل الأكواد المحجوزة.
 */
class CodeSequence
{
    /**
     * @param  class-string<Model>  $model
     * @param  string  $prefix  البادئة شاملة الفاصل، مثلاً 'EMP-'
     */
    public static function next(string $model, string $column, string $prefix, int $pad = 4): string
    {
        return $prefix . str_pad((string) (static::max($model, $column, $prefix) + 1), $pad, '0', STR_PAD_LEFT);
    }

    /** أكبر رقم مستخدم بعد البادئة، أو 0. */
    public static function max(string $model, string $column, string $prefix): int
    {
        /** @var Model $instance */
        $instance = new $model;
        $start = mb_strlen($prefix) + 1;

        // CAST بيختلف بين MySQL و SQLite (بيئة الاختبار)
        $cast = DB::connection($instance->getConnectionName())->getDriverName() === 'sqlite'
            ? "CAST(SUBSTR({$column}, ?) AS INTEGER)"
            : "CAST(SUBSTRING({$column}, ?) AS UNSIGNED)";

        $max = $model::query()
            ->withoutGlobalScopes()
            // من غير escape لـ _ و %: SQLite مالهاش escape افتراضي، والتطابق
            // الزيادة (لو البادئة فيها _) أقصى ضرره إن رقم يتنط — مش تكرار.
            ->where($column, 'like', $prefix . '%')
            ->selectRaw("MAX({$cast}) as seq", [$start])
            ->value('seq');

        return (int) $max;
    }
}
