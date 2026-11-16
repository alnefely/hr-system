<?php

namespace App\Http\Controllers\Admin\HR;

use App\Rules\AccessibleEmployee;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeReward;
use App\Services\Export\ExcelExportService;
use Illuminate\Http\Request;

class EmployeeRewardController extends Controller
{
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
                ->addColumn('status_badge', function ($row) {
                    $statuses = [
                        'approved' => 'bg-label-success',
                        'pending'  => 'bg-label-warning',
                        'rejected' => 'bg-label-danger',
                        'paid'     => 'bg-label-info',
                    ];
                    $badge = $statuses[$row->status] ?? 'bg-label-secondary';
                    $lbl = __('reward.status_enums.' . $row->status) ?? $row->status;
                    return '<span class="badge ' . $badge . '">' . $lbl . '</span>';
                })
                ->addColumn('formatted_date', fn($row) => $row->reward_date ? $row->reward_date->format('Y-m-d') : '-')
                ->addColumn('creator_name', fn($row) => $row->creator?->name ?? '-')
                ->addColumn('status_badge', function ($row) {
                    $status = $row->statusEnum();

                    if (!$status) {
                        return '<span class="badge bg-label-secondary">' . e($row->status) . '</span>';
                    }

                    $html = '<span class="badge ' . $status->badge() . '">'
                          . '<i class="' . $status->icon() . ' me-1"></i>'
                          . e($status->label()) . '</span>';

                    if ($row->rejection_reason) {
                        $html .= '<i class="fa-solid fa-circle-info text-danger ms-1" data-bs-toggle="tooltip" title="'
                              . e($row->rejection_reason) . '"></i>';
                    }

                    return $html;
                })
                ->addColumn('actions', function ($row) {
                    $html = '<div class="d-inline-flex gap-1">';

                    // الاعتماد والرفض للمعلق بس
                    if ($row->isPending()) {
                        if (admin_can('reward_approve')) {
                            $html .= '<form action="' . route('admin.rewards.approve', $row->id) . '" method="POST" class="d-inline">
                                ' . csrf_field() . '
                                <button type="button" class="btn btn-sm btn-icon btn-label-success btn-swal-confirm"
                                    title="' . __('global.approve') . '"
                                    data-title="اعتماد المكافأة"
                                    data-text="' . __('reward.messages.confirm_approve') . '"
                                    data-icon="question"
                                    data-btn-text="' . __('global.approve') . '"
                                    data-btn-class="btn-success">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>';
                        }

                        if (admin_can('reward_reject')) {
                            $html .= '<button type="button" class="btn btn-sm btn-icon btn-label-danger reject-btn"
                                data-id="' . $row->id . '" title="' . __('global.reject') . '">
                                <i class="fa-solid fa-xmark"></i>
                            </button>';
                        }
                    }

                    // الحذف ممنوع بعد الاعتماد — الحركة بقت في المسير
                    if (admin_can('reward_delete') && $row->isEditable()) {
                        $html .= '<form action="' . route('admin.rewards.destroy', $row->id) . '" method="POST" class="d-inline">
                            ' . csrf_field() . method_field('DELETE') . '
                            <button type="button" class="btn btn-sm btn-icon btn-label-secondary btn-swal-confirm" title="' . __('global.delete') . '"
                                data-title="' . __('global.confirm_delete') . '"
                                data-text="' . __('global.confirm_delete') . '"
                                data-icon="warning"
                                data-btn-text="' . __('global.delete') . '"
                                data-btn-class="btn-danger">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>';
                    }

                    $html .= '</div>';
                    return $html;
                })
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

            $rewards = $query->latest('reward_date')->get();
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
                    ->whereIn('status', ['approved', 'paid'])
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', new AccessibleEmployee],
            'reward_type' => ['required', 'in:bonus,incentive,allowance,appreciation'],
            'title'       => ['required', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'reward_date' => ['required', 'date'],
            'reason'      => ['nullable', 'string', 'max:1000'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ]);

        $data['created_by'] = admin()?->id;

        // المكافأة بتبدأ قيد الاعتماد: صرف مبلغ للموظف لازم يراجعه
        // شخص تاني قبل ما يدخل المسير — كان بيتعمد ذاتياً قبل كده.
        $data['status'] = 'pending';

        EmployeeReward::create($data);

        return redirect()->route('admin.rewards.index')->with('success', __('reward.messages.created'));
    }

    public function destroy(EmployeeReward $reward)
    {
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
                ->addColumn('reward_type_badge', function ($row) {
                    $types = [
                        'bonus'      => ['label' => 'مكافأة تميز', 'class' => 'bg-label-success'],
                        'allowance'  => ['label' => 'بدل إضافي', 'class' => 'bg-label-info'],
                        'commission' => ['label' => 'عمولة مبيعات', 'class' => 'bg-label-primary'],
                        'overtime'   => ['label' => 'عمل إضافي', 'class' => 'bg-label-warning'],
                    ];
                    $t = $types[$row->reward_type] ?? ['label' => $row->reward_type, 'class' => 'bg-label-secondary'];
                    return '<span class="badge ' . $t['class'] . '">' . $t['label'] . '</span>';
                })
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
        if (!$reward->isPending()) {
            return redirect()->back()->with('error', __('reward.messages.not_pending'));
        }

        if ($reward->created_by && $reward->created_by === admin()?->id && !admin()?->main) {
            return redirect()->back()->with('error', __('reward.messages.cannot_self_approve'));
        }

        $reward->update([
            'status'      => 'approved',
            'approved_by' => admin()?->id,
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', __('reward.messages.approved'));
    }

    /** رفض الحركة مع توثيق السبب. */
    public function reject(Request $request, EmployeeReward $reward)
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        if (!$reward->isPending()) {
            return redirect()->back()->with('error', __('reward.messages.not_pending'));
        }

        $reward->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by'      => admin()?->id,
            'approved_at'      => now(),
        ]);

        return redirect()->back()->with('success', __('reward.messages.rejected'));
    }
}
