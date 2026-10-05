<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\PettyCashBalance;
use Illuminate\Support\Facades\DB;

/**
 * ترحيل رصيد العهدة النقدية من شهر لشهر.
 *
 * العهدة لكل فرع لوحده — دايماً. مفيش "عهدة عامة" بـ branch_id فاضي:
 * المصروف لازم يتسجّل على فرع، والتخصيص لازم يتعمل لفرع، فأي رصيد
 * مالوش فرع مستحيل يتصرف منه، وكان بيفضل يترحّل لوحده للأبد.
 *
 * الترحيل بيتحسب كسلسلة من أول شهر للفرع:
 *
 *     مرحّل(شهر) = إجمالي(الشهر اللي قبله) − مصروف(الشهر اللي قبله)
 *     إجمالي(شهر) = مخصص(شهر) + مرحّل(شهر)
 *
 * إعادة الحساب من الأول (مش شهر واحد بس) هي اللي بتخلي تعديل مصروف
 * قديم يصحّح كل الشهور اللي بعده — قبل كده المرحّل كان بيتحسب مرة
 * واحدة ويفضل قديم لو حد عدّل مصروف الشهر اللي فات.
 *
 * المصروف المحسوب = كل مصروفات الفرع في الشهر بكل طرق الدفع،
 * نفس اللي بتعرضه بطاقة "المتبقي الفعلي" في شاشة المصاريف.
 */
class PettyCashService
{
    /**
     * إعادة حساب الترحيل لكل الفروع لحد الشهر المحدد.
     *
     * بيرجع عدد الصفوف اللي اتغيّرت فعلاً.
     */
    public function carryOverAll(int $year, int $month): int
    {
        $branchIds = PettyCashBalance::whereNotNull('branch_id')
            ->distinct()
            ->pluck('branch_id');

        $changed = 0;

        foreach ($branchIds as $branchId) {
            $changed += $this->recalculateBranch((int) $branchId, $year, $month);
        }

        return $changed;
    }

    /**
     * إعادة حساب سلسلة الترحيل لفرع واحد من أول شهر ليه لحد الشهر المحدد.
     *
     * أول شهر للفرع مرحّله بيفضل زي ما هو (مفيش شهر قبله يتحسب منه).
     * الشهر اللي مالوش صف بيتعمله صف بس لو فيه متبقي يترحّل.
     *
     * بيرجع عدد الصفوف اللي اتغيّرت فعلاً.
     */
    public function recalculateBranch(int $branchId, int $toYear, int $toMonth): int
    {
        $rows = PettyCashBalance::where('branch_id', $branchId)
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->keyBy(fn ($row) => $this->periodKey($row->year, $row->month));

        if ($rows->isEmpty()) {
            return 0;
        }

        $first = $rows->first();
        $last  = $rows->last();
        [$year, $month] = [$first->year, $first->month];

        // لو فيه شهر متخصص بعد الهدف (تخصيص الشهر الجاي مقدماً)،
        // بنكمّل لحد عنده — وإلا مرحّله يفضل قديم.
        if ($this->periodKey($last->year, $last->month) > $this->periodKey($toYear, $toMonth)) {
            [$toYear, $toMonth] = [$last->year, $last->month];
        }

        // شهر الهدف قبل أول شهر للفرع: مفيش حاجة تتحسب
        if ($this->periodKey($toYear, $toMonth) < $this->periodKey($year, $month)) {
            return 0;
        }

        $previousTotal = (float) $first->allocated_amount + (float) $first->carried_over_amount;
        $changed = 0;

        DB::transaction(function () use ($branchId, $rows, &$year, &$month, $toYear, $toMonth, &$previousTotal, &$changed) {
            while ($this->periodKey($year, $month) < $this->periodKey($toYear, $toMonth)) {
                $spent = (float) Expense::acrossAllBranches()
                    ->where('branch_id', $branchId)
                    ->inMonth($year, $month)
                    ->sum('amount');

                $remaining = round(max(0, $previousTotal - $spent), 2);

                [$year, $month] = $this->nextPeriod($year, $month);

                $row = $rows->get($this->periodKey($year, $month));

                if ($row) {
                    if (round((float) $row->carried_over_amount, 2) !== $remaining) {
                        $row->carried_over_amount = $remaining;
                        $row->save();   // booted() بيحدّث total_balance
                        $changed++;
                    }

                    $previousTotal = (float) $row->allocated_amount + $remaining;

                    continue;
                }

                // مفيش صف للشهر ده: بنعمله بس لو فيه حاجة تترحّل
                if ($remaining > 0) {
                    PettyCashBalance::create([
                        'branch_id'           => $branchId,
                        'year'                => $year,
                        'month'               => $month,
                        'allocated_amount'    => 0,
                        'carried_over_amount' => $remaining,
                        'created_by'          => auth('admin')->id(),
                    ]);
                    $changed++;
                }

                $previousTotal = $remaining;
            }
        });

        return $changed;
    }

    /** مفتاح قابل للمقارنة: 2026-10 → 202610. */
    private function periodKey(int $year, int $month): int
    {
        return $year * 100 + $month;
    }

    /** @return array{0:int,1:int} */
    private function nextPeriod(int $year, int $month): array
    {
        return $month === 12 ? [$year + 1, 1] : [$year, $month + 1];
    }
}
