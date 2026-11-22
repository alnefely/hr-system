<?php

namespace App\Http\Controllers\Admin\HR;

use App\Enums\RewardStatus;
use App\Http\Controllers\Concerns\RespondsToStateActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reward\StoreRewardRequest;
use App\Http\Requests\Admin\StrictRejectionRequest;
use App\Models\Employee;
use App\Models\EmployeeReward;
use App\Services\Export\ExcelExportService;
use App\Services\HR\ApprovalService;
use Illuminate\Http\Request;

/**
 * المكافآت. الاعتماد والرفض عبر ApprovalService (مع فصل المهام).
 */
class EmployeeRewardController extends Controller
{
    use RespondsToStateActions;

    public function __construct(
        private ApprovalService $approvals,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            // select صريح: yajra بيعمل join تلقائي للترتيب بأعمدة
            // العلاقات، و select * ساعتها بيخلي أعمدة الجدول المنضم
            // تدهس أعمدة الجدول الأصلي (id بالذات).
            $query = EmployeeReward::select('employee_rewards.*')
                ->with(['employee:id,name,employee_code,branch_id', 'employee.branch:id,currency', 'creator:id,name']);

            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->filled('reward_type')) {
                $query->where('reward_type', $request->reward_type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            return datatables($query)
                ->addColumn('employee_name', fn($row) => $row->employee?->name ?? '-')
                ->addColumn('employee_code', fn($row) => $row->employee?->employee_code ?? '-')
                ->addColumn('type_badge', function ($row) {
                    $types = [
                        'bonus'        => 'bg-label-success',
                        'incentive'    => 'bg-label-primary',
                        'allowance'    => 'bg-label-info',
                        'appreciation' => 'bg-label-warning',
                    ];
                    $badge = $types[$row->reward_type] ?? 'bg-label-secondary';
                    $lbl = __('reward.type_enums.' . $row->reward_type) ?? $row->reward_type;
                    return '<span class="badge ' . $badge . '">' . $lbl . '</span>';
                })
                ->addColumn('formatted_amount', function ($row) {
                    $curr = $row->employee?->branch?->currency ?? 'EGP';
                    return '<span class="text-success fw-bold"><span dir="ltr">+' . number_format($row->amount, 2) . '</span> ' . $curr . '</span>';
                })
                ->addColumn('formatted_date', fn ($row) => $row->reward_date?->format('Y-m-d') ?? '-')
                ->addColumn('creator_name', fn ($row) => $row->creator?->name ?? '-')
                ->addColumn('status_badge', fn ($row) => view('admin.hr.partials.approval-status', ['row' => $row])->render())
                ->addColumn('actions', fn ($row) => view('admin.hr.partials.approval-actions', [
                    'row'          => $row,
                    'module'       => 'reward',
                    'routes'       => 'admin.rewards',
                    'approveTitle' => 'اعتماد المكافأة',
                ])->render())
                ->rawColumns(['type_badge', 'formatted_amount', 'status_badge', 'actions'])
                ->make(true);
        }

        if ($request->get('export') === 'excel') {
            $query = EmployeeReward::with(['employee:id,name,employee_code,branch_id', 'employee.branch:id,currency']);
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }
            if ($request->filled('reward_type')) {
                $query->where('reward_type', $request->reward_type);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // سقف للتصدير عشان الملف مايبلعش الذاكرة (الأحدث الأول)
            $rewards = $query->latest('reward_date')->limit(ExcelExportService::MAX_ROWS)->get();
            $headers = [
                __('global.export.row_no'),
                __('global.export.employee_code'),
                __('global.export.employee_name'),
                __('reward.export.reward_type'),
                __('reward.export.reward_title'),
                __('global.export.amount'),
                __('global.export.currency'),
                __('reward.export.reward_date'),
                __('global.export.status'),
                __('global.export.reason'),
            ];
            $rows = [];
            $i = 1;
            $totalAmount = 0;
            foreach ($rewards as $rew) {
                $totalAmount += (float) $rew->amount;
                $curr = $rew->employee?->branch?->currency ?? 'EGP';
                $rows[] = [
                    $i++,
                    $rew->employee?->employee_code ?? '-',
                    $rew->employee?->name ?? '-',
                    __('reward.type_enums.' . $rew->reward_type) ?? $rew->reward_type,
                    $rew->title,
                    number_format($rew->amount, 2),
                    $curr,
                    $rew->reward_date ? $rew->reward_date->format('Y-m-d') : '-',
                    __('reward.status_enums.' . $rew->status) ?? $rew->status,
                    $rew->reason ?? '-',
                ];
            }

            $totals = [
                'الإجمالي',
                '',
                count($rewards) . ' مكافأة/بدل',
                '',
                '',
                number_format($totalAmount, 2),
                '',
                '',
                '',
                'تم التصدير بنجاح',
            ];

            return ExcelExportService::download('سجل المكافآت والبدلات الاستثنائية', $headers, $rows, $totals, 'المكافآت_' . date('Ymd_His'));
        }

        $employees = Employee::active()->with('branch:id,name,currency')->get(['id', 'name', 'employee_code', 'branch_id']);

        $selectedEmployeeId = $request->get('employee_id');
        $selectedEmployee   = null;
        $rewardStats        = null;

        if ($selectedEmployeeId) {
            $selectedEmployee = Employee::with('branch:id,name,currency')->find($selectedEmployeeId);
            if ($selectedEmployee) {
                $totalRewards = EmployeeReward::where('employee_id', $selectedEmployeeId)->count();
                $totalAmount  = (float) EmployeeReward::where('employee_id', $selectedEmployeeId)
                    ->whereIn('status', [RewardStatus::Approved->value, RewardStatus::Paid->value])
                    ->sum('amount');

                $rewardStats = [
                    'total_rewards' => $totalRewards,
                    'total_amount'  => $totalAmount,
                    'currency'      => $selectedEmployee->branch?->currency ?? 'EGP',
                ];
            }
        }
        $trashCount = EmployeeReward::onlyTrashed()->count();

        return view('admin.hr.rewards.index', compact(
            'employees',
            'selectedEmployee',
            'selectedEmployeeId',
            'rewardStats',
            'trashCount'
        ));
    }

    public function store(StoreRewardRequest $request)
    {
        // المكافأة بتبدأ قيد الاعتماد: صرف مبلغ للموظف لازم يراجعه
        // شخص تاني قبل ما يدخل المسير.
        EmployeeReward::create([
            'created_by' => admin()?->id,
            'status'     => RewardStatus::Pending->value,
        ] + $request->validated());

        return redirect()->route('admin.rewards.index')->with('success', __('reward.messages.created'));
    }

    public function destroy(EmployeeReward $reward)
    {
        // بعد الاعتماد أو الصرف المكافأة بقت جزء من المسير، فالحذف بيكسر التطابق.
        if (! $reward->isEditable()) {
            return redirect()->back()->with('error', __('reward.messages.cannot_modify_approved'));
        }

        $reward->delete();

        return redirect()->back()->with('success', __('reward.messages.deleted'));
    }

    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = EmployeeReward::onlyTrashed()->with(['employee:id,name,employee_code,branch_id', 'employee.branch:id,currency']);
            return datatables($query)
                ->addColumn('employee_name', fn($row) => $row->employee?->name ?? '-')
                ->addColumn('employee_code', fn($row) => $row->employee?->employee_code ?? '-')
                ->addColumn('formatted_amount', function ($row) {
                    $curr = $row->employee?->branch?->currency ?? 'EGP';
                    return '<span class="fw-bold text-success" dir="ltr">' . number_format($row->amount, 2) . ' ' . $curr . '</span>';
                })
                // الأنواع والأسماء من ملف الترجمة — الخريطة القديمة كانت فيها
                // أنواع مش موجودة (commission, overtime) وناقصها incentive.
                ->addColumn('reward_type_badge', fn ($row) => '<span class="badge bg-label-secondary">'
                    . e(__('reward.type_enums.' . $row->reward_type)) . '</span>')
                ->addColumn('deleted_at', fn($row) => $row->deleted_at ? $row->deleted_at->format('Y-m-d H:i') : '-')
                ->rawColumns(['formatted_amount', 'reward_type_badge'])
                ->make(true);
        }
        return view('admin.hr.rewards.trash');
    }

    public function restore(int $id)
    {
        $rew = EmployeeReward::onlyTrashed()->findOrFail($id);
        $rew->restore();
        return redirect()->back()->with('success', __('global.restore'));
    }

    public function forceDelete(int $id)
    {
        $rew = EmployeeReward::onlyTrashed()->findOrFail($id);
        $rew->forceDelete();
        return redirect()->back()->with('success', __('global.force_delete'));
    }

    // ========================================================
    //  دورة الاعتماد
    // ========================================================

    /**
     * اعتماد الحركة — فصل المهام: اللي سجّلها مايعتمدهاش.
     *
     * المبدأ محاسبياً: أي حركة بتأثر على راتب الموظف لازم يراجعها
     * شخص تاني، عشان مايبقاش نفس الشخص هو اللي يسجّل ويصرف.
     */
    public function approve(EmployeeReward $reward)
    {
        return $this->attempt(
            fn () => $this->approvals->approve($reward, 'reward'),
            'reward.messages.approved'
        );
    }

    /** رفض الحركة مع توثيق السبب — المعلّق بس. */
    public function reject(StrictRejectionRequest $request, EmployeeReward $reward)
    {
        return $this->attempt(
            fn () => $this->approvals->reject($reward, $request->reason(), 'reward'),
            'reward.messages.rejected'
        );
    }
}
