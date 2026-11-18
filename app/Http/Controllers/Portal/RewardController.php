<?php

namespace App\Http\Controllers\Portal;

use App\Enums\RewardStatus;
use Illuminate\Http\Request;

/**
 * مكافآتي — للعرض بس.
 *
 * المكافأة بتتسجّل وتتعتمد من لوحة الإدارة، والموظف بيشوف المعتمد والمصروف
 * بس: المعلّق ممكن يترفض، والمرفوض قرار داخلي مالوش لازمة يظهر له.
 * الكروت الملخّصة بتتبع الفترة المختارة.
 */
class RewardController extends PortalController
{
    private const VISIBLE = [RewardStatus::Approved->value, RewardStatus::Paid->value];

    public function index(Request $request)
    {
        $employee = $this->employee();
        $employee->loadMissing('branch:id,currency');
        $period = $this->period();

        $visible = fn () => $this->inPeriod($employee->rewards()->reorder(), $period)->whereIn('status', self::VISIBLE);

        if ($request->ajax()) {
            $currency = e($employee->branch?->currency ?? '');

            return datatables($visible())
                ->editColumn('reward_date', fn ($r) => $r->reward_date?->format('Y-m-d'))
                ->editColumn('reward_type', fn ($r) => e(\Illuminate\Support\Facades\Lang::has('reward.type_enums.' . $r->reward_type)
                    ? __('reward.type_enums.' . $r->reward_type) : $r->reward_type))
                ->editColumn('title', fn ($r) => '<div class="fw-medium">' . e($r->title) . '</div>'
                    . ($r->reason ? '<small class="text-muted">' . e($r->reason) . '</small>' : ''))
                ->editColumn('amount', fn ($r) => '<span dir="ltr" class="text-nowrap fw-bold text-success">+' . number_format((float) $r->amount, 2) . ' ' . $currency . '</span>')
                ->addColumn('status_badge', fn ($r) => RewardStatus::badgeFor($r->status, 'portal.rewards.status'))
                ->rawColumns(['title', 'amount', 'status_badge'])
                ->make(true);
        }

        // ملخص بالجمع في قاعدة البيانات (مش تحميل كل السجلات)
        $totals = $visible()
            ->selectRaw('status, SUM(amount) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $paid = (float) ($totals[RewardStatus::Paid->value] ?? 0);
        $upcoming = (float) ($totals[RewardStatus::Approved->value] ?? 0);

        return view('portal.rewards.index', [
            'employee'  => $employee,
            'period'    => $period,
            'paidTotal' => $paid,
            'upcoming'  => $upcoming,
            'total'     => $paid + $upcoming,
        ]);
    }
}
