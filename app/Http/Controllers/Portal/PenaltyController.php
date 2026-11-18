<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ApprovalStatus;
use App\Services\HR\PayrollRules;
use Illuminate\Http\Request;

/**
 * جزاءاتي — للعرض بس، المعتمد فقط.
 *
 * المعتمد هو اللي بيتخصم في القسيمة، فلما الموظف يشوف سببه بيقل السؤال
 * للموارد البشرية. المعلّق ممكن يترفض، والمرفوض مالوش أثر — الاتنين مش
 * بيظهروا. المبلغ بنفس حساب المسير (PayrollRules::penaltyCost).
 * الكروت الملخّصة بتتبع الفترة المختارة.
 */
class PenaltyController extends PortalController
{
    public function index(Request $request)
    {
        $employee = $this->employee();
        $employee->loadMissing('branch:id,currency');
        $period = $this->period();
        $dailyRate = PayrollRules::dailyRate($employee);

        $approved = fn () => $this->inPeriod($employee->penalties()->reorder(), $period)
            ->where('status', ApprovalStatus::Approved->value);

        if ($request->ajax()) {
            $currency = e($employee->branch?->currency ?? '');

            return datatables($approved())
                ->editColumn('penalty_date', fn ($p) => $p->penalty_date?->format('Y-m-d'))
                ->editColumn('penalty_type', fn ($p) => e(__('penalty.type_enums.' . $p->penalty_type))
                    . ($p->penalty_type === 'days_deduction'
                        ? '<small class="text-muted d-block">' . e(__('portal.penalties.days', ['days' => (float) $p->days_count + 0])) . '</small>'
                        : ''))
                ->editColumn('reason', fn ($p) => e($p->reason ?: '—'))
                ->addColumn('deduction', function ($p) use ($dailyRate, $currency) {
                    $cost = PayrollRules::penaltyCost($p, $dailyRate);

                    return $cost > 0
                        ? '<span dir="ltr" class="text-nowrap fw-bold text-danger">-' . number_format($cost, 2) . ' ' . $currency . '</span>'
                        : '<span class="text-muted">' . e(__('portal.penalties.no_deduction')) . '</span>';
                })
                ->rawColumns(['penalty_type', 'deduction'])
                ->make(true);
        }

        $rows = $approved()->get(['id', 'penalty_type', 'days_count', 'deduction_amount']);

        return view('portal.penalties.index', [
            'employee' => $employee,
            'period'   => $period,
            'count'    => $rows->count(),
            'total'    => $rows->sum(fn ($p) => PayrollRules::penaltyCost($p, $dailyRate)),
        ]);
    }
}
