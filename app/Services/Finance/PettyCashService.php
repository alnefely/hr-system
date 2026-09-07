<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\PettyCashBalance;
use Illuminate\Support\Facades\DB;

/**
 * ترحيل رصيد العهدة النقدية من شهر لشهر.
 *
 * ملاحظة مهمة على الأداء: carryOverAll() كانت بتلف على الفروع وتعمل
 * 3 استعلامات لكل فرع — يعني 20 فرع = 60 استعلام على كل زيارة لصفحة
 * المصاريف. دلوقتي بتجمع كل البيانات في 3 استعلامات ثابتة مهما كان
 * عدد الفروع، والكتابة بتتم بـ upsert واحد.
 *
 * الترحيل المفروض يتنفذ من الأمر المجدول (petty-cash:carry-over)
 * مش من داخل صفحة العرض — الكتابة في DB جوّه GET ممارسة سيئة.
 */
class PettyCashService
{
    /**
     * رحّل المتبقي من الشهر السابق لكل الفروع دفعة واحدة.
     *
     * بيرجع عدد السجلات اللي اتحدّثت فعلاً.
     */
    public function carryOverAll(int $year, int $month): int
    {
        [$prevYear, $prevMonth] = $this->previousPeriod($year, $month);

        // (1) أرصدة الشهر السابق لكل الفروع
        $previousBalances = PettyCashBalance::where('year', $prevYear)
            ->where('month', $prevMonth)
            ->get()
            ->keyBy(fn ($row) => $this->branchKey($row->branch_id));

        if ($previousBalances->isEmpty()) {
            return 0;
        }

        // (2) المصروف في الشهر السابق مجمّعاً بالفرع — استعلام واحد
        $spentByBranch = Expense::acrossAllBranches()
            ->inMonth($prevYear, $prevMonth)
            ->groupBy('branch_id')
            ->selectRaw('branch_id, SUM(amount) as total')
            ->get()
            ->mapWithKeys(fn ($row) => [$this->branchKey($row->branch_id) => (float) $row->total]);

        // (3) أرصدة الشهر الحالي — نعرف منها إيه اللي محتاج تحديث فعلاً
        $currentBalances = PettyCashBalance::where('year', $year)
            ->where('month', $month)
            ->get()
            ->keyBy(fn ($row) => $this->branchKey($row->branch_id));

        $adminId = auth('admin')->id();
        $now = now();
        $toUpsert = [];

        foreach ($previousBalances as $key => $previous) {
            $previousTotal = (float) $previous->total_balance;

            if ($previousTotal <= 0) {
                continue;
            }

            $spent = (float) ($spentByBranch[$key] ?? 0);
            $remaining = round(max(0, $previousTotal - $spent), 2);

            if ($remaining <= 0) {
                continue;
            }

            $current = $currentBalances->get($key);

            // idempotent: مفيش كتابة لو الرصيد المرحّل مضبوط أصلاً
            if ($current && round((float) $current->carried_over_amount, 2) === $remaining) {
                continue;
            }

            $allocated = (float) ($current->allocated_amount ?? 0);

            $toUpsert[] = [
                'branch_id'           => $previous->branch_id,
                'year'                => $year,
                'month'               => $month,
                'carried_over_amount' => $remaining,
                'allocated_amount'    => $allocated,
                'total_balance'       => round($allocated + $remaining, 2),
                'created_by'          => $current->created_by ?? $adminId,
                'created_at'          => $current->created_at ?? $now,
                'updated_at'          => $now,
            ];
        }

        if (empty($toUpsert)) {
            return 0;
        }

        DB::transaction(function () use ($toUpsert) {
            PettyCashBalance::upsert(
                $toUpsert,
                ['branch_id', 'year', 'month'],
                ['carried_over_amount', 'total_balance', 'updated_at']
            );
        });

        return count($toUpsert);
    }

    /**
     * ترحيل فرع واحد — للاستدعاءات المحددة (شاشة فرع بعينه).
     */
    public function carryOverForBranch(?int $branchId, int $year, int $month): ?PettyCashBalance
    {
        [$prevYear, $prevMonth] = $this->previousPeriod($year, $month);

        $previous = PettyCashBalance::where('year', $prevYear)
            ->where('month', $prevMonth)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId), fn ($q) => $q->whereNull('branch_id'))
            ->first();

        if (!$previous || (float) $previous->total_balance <= 0) {
            return null;
        }

        // acrossAllBranches بيوقف الـ Global Scope: الاستعلام بيفلتر بـ
        // $branchId الصريح، ولو سيبنا العزل التلقائي يضيف فلتر تاني كان
        // هيصفّر ترحيل العهدة العامة (branch_id = null).
        $spent = (float) Expense::acrossAllBranches()
            ->inMonth($prevYear, $prevMonth)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId), fn ($q) => $q->whereNull('branch_id'))
            ->sum('amount');

        $remaining = round(max(0, (float) $previous->total_balance - $spent), 2);

        if ($remaining <= 0) {
            return null;
        }

        return DB::transaction(function () use ($branchId, $year, $month, $remaining) {
            $current = PettyCashBalance::firstOrNew([
                'branch_id' => $branchId,
                'year'      => $year,
                'month'     => $month,
            ]);

            if (round((float) $current->carried_over_amount, 2) === $remaining) {
                return $current->exists ? $current : null;
            }

            $current->carried_over_amount = $remaining;
            $current->created_by ??= auth('admin')->id();
            $current->save();

            return $current;
        });
    }

    /** مفتاح موحّد للفرع — null بيبقى مفتاح ثابت عشان الـ keyBy يشتغل. */
    private function branchKey(?int $branchId): string
    {
        return $branchId === null ? '_general' : (string) $branchId;
    }

    /** @return array{0:int,1:int} */
    private function previousPeriod(int $year, int $month): array
    {
        return $month === 1 ? [$year - 1, 12] : [$year, $month - 1];
    }
}
