<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\EmployeePenalty;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * قواعد حساب الراتب — مصدر واحد للمسير والتقارير.
 *
 * قبل كده كل حتة كانت بتحسب بطريقتها: المسير بياخد الجزاءات المعتمدة
 * بس ومبلغها المخزّن، والتقارير كانت بتاخد كل الجزاءات (حتى المرفوضة)
 * وتعيد حسابها من سعر اليوم الحالي. والنتيجة إن "تدقيق الراتب" كان
 * بيطلّع رقم غير اللي اتصرف فعلاً.
 */
final class PayrollRules
{
    /** الحد الافتراضي لساعات الاستئذان الشهرية. */
    public const DEFAULT_PERMISSION_LIMIT = 4.00;

    /** ساعات اليوم الافتراضية لو مش متسجّلة للموظف. */
    public const DEFAULT_DAILY_HOURS = 8.00;

    /** حالات الجزاء اللي بتتخصم من الراتب. */
    public const DEDUCTIBLE_PENALTY_STATUSES = ['approved'];

    /** أجر اليوم: السعر الخاص لو موجود، وإلا الأساسي ÷ 30. */
    public static function dailyRate(Employee $employee): float
    {
        return $employee->daily_rate;
    }

    /**
     * أجر الساعة: السعر الخاص لو موجود، وإلا أجر اليوم ÷ ساعات اليوم.
     *
     * بيحترم custom_daily_rate (اللي effective_hourly_rate القديمة كانت
     * بتتجاهله وتحسب من الأساسي ÷ 30 مباشرة).
     */
    public static function hourlyRate(Employee $employee): float
    {
        return $employee->hourly_rate;
    }

    public static function permissionLimit(Employee $employee): float
    {
        return (float) ($employee->monthly_permission_hours_limit ?? self::DEFAULT_PERMISSION_LIMIT);
    }

    /**
     * تكلفة جزاء واحد.
     *
     * خصم الأيام بيستخدم المبلغ المخزّن وقت التسجيل (اللي اتعرض واتعمد)،
     * مش سعر اليوم الحالي — الراتب ممكن يكون اتغيّر بعدها. الحساب من سعر
     * اليوم بيفضل للسجلات القديمة اللي مالهاش مبلغ.
     */
    public static function penaltyCost(EmployeePenalty $penalty, float $dailyRate): float
    {
        return match ($penalty->penalty_type) {
            'days_deduction' => (float) $penalty->deduction_amount > 0
                ? round((float) $penalty->deduction_amount, 2)
                : round((float) $penalty->days_count * $dailyRate, 2),
            'amount_deduction' => round((float) $penalty->deduction_amount, 2),
            default => 0.0,
        };
    }

    public static function isDeductible(EmployeePenalty $penalty): bool
    {
        return in_array($penalty->status, self::DEDUCTIBLE_PENALTY_STATUSES, true);
    }

    /**
     * الإجازات اللي بتتقاطع مع فترة — مش اللي بتبدأ فيها بس.
     *
     * إجازة من 28/10 لـ 5/11 لازم تظهر في أكتوبر ونوفمبر الاتنين.
     */
    public static function overlapping(Builder $query, CarbonInterface|string $from, CarbonInterface|string $to): Builder
    {
        $from = CarbonImmutable::parse($from)->startOfDay();
        $to   = CarbonImmutable::parse($to)->endOfDay();

        return $query
            ->where('start_date', '<=', $to)
            ->where('end_date', '>=', $from);
    }

    /**
     * نصيب فترة معيّنة من أيام الإجازة.
     *
     * بيتوزّع بنسبة الأيام التقويمية: إجازة 9 أيام منها 4 في أكتوبر و5 في
     * نوفمبر → 4 على أكتوبر و5 على نوفمبر. لو days_count مختلف عن الأيام
     * التقويمية (إجازة بتستثني الجمعة مثلاً) بيتوزّع بنفس النسبة.
     */
    public static function leaveDaysWithin(EmployeeLeave $leave, CarbonInterface|string $from, CarbonInterface|string $to): float
    {
        $days = (float) $leave->days_count;

        if (! $leave->start_date || ! $leave->end_date) {
            return $days;
        }

        $start = CarbonImmutable::parse($leave->start_date)->startOfDay();
        $end   = CarbonImmutable::parse($leave->end_date)->startOfDay();
        $from  = CarbonImmutable::parse($from)->startOfDay();
        $to    = CarbonImmutable::parse($to)->startOfDay();

        $total = (int) $start->diffInDays($end) + 1;

        $overlapStart = $start->max($from);
        $overlapEnd   = $end->min($to);

        if ($overlapEnd->lt($overlapStart)) {
            return 0.0;
        }

        $overlap = (int) $overlapStart->diffInDays($overlapEnd) + 1;

        if ($overlap >= $total) {
            return $days;
        }

        return round($days * $overlap / $total, 2);
    }
}
