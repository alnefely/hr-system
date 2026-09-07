<?php

namespace App\Http\Controllers\Admin\HR;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\NotificationType;
use App\Services\Notification\NotificationService;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveBalance;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeLeaveController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // select صريح: yajra بيعمل join تلقائي للترتيب بأعمدة
            // العلاقات، و select * ساعتها بيخلي أعمدة الجدول المنضم
            // تدهس أعمدة الجدول الأصلي (id بالذات).
            $query = EmployeeLeave::visibleTo()
                ->select('employee_leaves.*')->with(['employee:id,name,employee_code', 'approver:id,name']);

            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->filled('leave_type')) {
                $query->where('leave_type', $request->leave_type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            return datatables($query)
                ->addColumn('employee_name', fn($row) => $row->employee?->name ?? '-')
                ->addColumn('employee_code', fn($row) => $row->employee?->employee_code ?? '-')
                ->addColumn('leave_type_badge', fn($row) => LeaveType::fromValue($row->leave_type)?->badgeHtml()
                    ?? '<span class="badge bg-label-secondary">' . e($row->leave_type) . '</span>')
                ->addColumn('formatted_start', fn($row) => $row->start_date ? $row->start_date->format('Y-m-d') : '-')
                ->addColumn('formatted_end', fn($row) => $row->end_date ? $row->end_date->format('Y-m-d') : '-')
                ->addColumn('status_badge', fn($row) => LeaveStatus::fromValue($row->status)?->badgeHtml()
                    ?? '<span class="badge bg-label-secondary">' . e($row->status) . '</span>')
                ->addColumn('approver_name', fn($row) => $row->approver?->name ?? '-')
                ->addColumn('actions', function ($row) {
                    $html = '<div class="d-inline-flex gap-1">';
                    if ($row->status === 'pending') {
                        if (admin_can('leave_approve')) {
                            $html .= '<form action="' . route('admin.leaves.approve', $row->id) . '" method="POST" class="d-inline">
                                ' . csrf_field() . '
                                <button type="button" class="btn btn-sm btn-icon btn-label-success btn-swal-confirm" title="' . __('leave.actions.approve') . '" data-title="اعتماد الإجازة" data-text="هل أنت متأكد من اعتماد طلب الإجازة لهذا الموظف؟" data-icon="question" data-btn-text="نعم، اعتمد" data-btn-class="btn-success">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>';
                        }
                        if (admin_can('leave_reject')) {
                            $html .= '<button type="button" class="btn btn-sm btn-icon btn-label-danger reject-btn" data-id="' . $row->id . '" title="' . __('leave.actions.reject') . '">
                                <i class="fa-solid fa-xmark"></i>
                            </button>';
                        }
                    }
                    if (admin_can('leave_delete')) {
                        $html .= '<form action="' . route('admin.leaves.destroy', $row->id) . '" method="POST" class="d-inline">
                            ' . csrf_field() . method_field('DELETE') . '
                            <button type="button" class="btn btn-sm btn-icon btn-label-secondary btn-swal-confirm" title="' . __('global.delete') . '" data-title="تأكيد الحذف" data-text="هل أنت متأكد من نقل الإجازة إلى سلة المحذوفات؟" data-icon="warning" data-btn-text="نعم، احذف" data-btn-class="btn-danger">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->rawColumns(['leave_type_badge', 'status_badge', 'actions'])
                ->make(true);
        }

        $employees = Employee::visibleTo()->active()->get(['id', 'name', 'employee_code']);

        $trashCount = EmployeeLeave::onlyTrashed()->count();
        return view('admin.hr.leaves.index', compact('employees', 'trashCount'));
    }

    public function balances()
    {
        $year = (int) request('year', date('Y'));
        $employees = Employee::visibleTo()->active()->get(['id', 'annual_leave_entitlement']);

        $existingEmpIds = EmployeeLeaveBalance::where('year', $year)->pluck('employee_id')->all();
        $missingEmps = $employees->whereNotIn('id', $existingEmpIds);

        if ($missingEmps->isNotEmpty()) {
            $rows = [];
            $now = now();
            foreach ($missingEmps as $emp) {
                $rows[] = [
                    'employee_id'    => $emp->id,
                    'year'           => $year,
                    'annual_balance' => $emp->annual_leave_entitlement ?? 21.00,
                    'casual_balance' => 7.00,
                    'sick_balance'   => 14.00,
                    'used_annual'    => 0,
                    'used_casual'    => 0,
                    'used_sick'      => 0,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }
            EmployeeLeaveBalance::insert($rows);
        }

        $balances = EmployeeLeaveBalance::whereHas('employee', fn($q) => $q->visibleTo())
            ->with('employee:id,name,employee_code,branch_id')
            ->where('year', $year)
            ->get();

        return view('admin.hr.leaves.balances', compact('balances', 'year'));
    }

    public function updateBalance(Request $request, EmployeeLeaveBalance $balance)
    {
        $data = $request->validate([
            'annual_balance' => ['required', 'numeric', 'min:0'],
            'casual_balance' => ['required', 'numeric', 'min:0'],
            'sick_balance'   => ['required', 'numeric', 'min:0'],
        ]);

        $balance->update($data);

        return redirect()->back()->with('success', __('leave.messages.balance_updated'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type'  => ['required', 'in:annual,casual,sick,unpaid'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
            'days_count'  => ['required', 'numeric', 'min:0.5'],
            'reason'      => ['nullable', 'string', 'max:1000'],
            'attachment'  => ['nullable', 'string', 'max:1000'],
        ]);

        $start = \Carbon\Carbon::parse($data['start_date']);
        $end = \Carbon\Carbon::parse($data['end_date']);
        $maxDays = $start->diffInDays($end) + 1;

        if ((float) $data['days_count'] > $maxDays) {
            return redirect()->back()->withInput()->with('error', __('leave.errors.invalid_days_count', [
                'days' => $data['days_count'],
                'max'  => $maxDays,
            ]));
        }

        $overlap = EmployeeLeave::where('employee_id', $data['employee_id'])
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_date', '<=', $data['end_date'])
            ->where('end_date', '>=', $data['start_date'])
            ->exists();

        if ($overlap) {
            return redirect()->back()->withInput()->with('error', __('leave.errors.overlap'));
        }

        $data['status'] = 'pending';
        $leave = EmployeeLeave::create($data);

        $this->notifyLeaveRequested($leave);

        return redirect()->route('admin.leaves.index')->with('success', __('leave.messages.created'));
    }

    public function approve(Request $request, EmployeeLeave $leave)
    {
        if ($leave->status !== 'pending') {
            return redirect()->back()->with('error', __('leave.errors.not_pending'));
        }

        $year = $leave->start_date ? $leave->start_date->year : date('Y');

        try {
            DB::transaction(function () use ($leave, $year) {
                $balance = EmployeeLeaveBalance::where('employee_id', $leave->employee_id)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();

                if (!$balance) {
                    $balance = EmployeeLeaveBalance::create([
                        'employee_id'    => $leave->employee_id,
                        'year'           => $year,
                        'annual_balance' => $leave->employee?->annual_leave_entitlement ?? 21.00,
                        'casual_balance' => 7.00,
                        'sick_balance'   => 14.00,
                        'used_annual'    => 0,
                        'used_casual'    => 0,
                        'used_sick'      => 0,
                        'used_unpaid'    => 0,
                    ]);
                }

                // التحقق من كفاية الرصيد قبل الاعتماد
                if ($leave->leave_type === 'annual' && $balance->remaining_annual < $leave->days_count) {
                    throw new Exception(__('leave.errors.insufficient_annual', [
                        'remaining' => $balance->remaining_annual,
                        'requested' => $leave->days_count,
                    ]));
                }
                if ($leave->leave_type === 'casual' && $balance->remaining_casual < $leave->days_count) {
                    throw new Exception(__('leave.errors.insufficient_casual', [
                        'remaining' => $balance->remaining_casual,
                        'requested' => $leave->days_count,
                    ]));
                }
                if ($leave->leave_type === 'sick' && $balance->remaining_sick < $leave->days_count) {
                    throw new Exception(__('leave.errors.insufficient_sick', [
                        'remaining' => $balance->remaining_sick,
                        'requested' => $leave->days_count,
                    ]));
                }

                $leave->update([
                    'status'      => 'approved',
                    'approved_by' => admin()?->id,
                    'approved_at' => now(),
                ]);

                // إعادة احتساب الرصيد المستهلك مباشرة من الإجازات المعتمدة
                $balance->recalculateUsed();
            });

            $this->notifyLeaveDecided($leave->fresh(), 'approved');

            return redirect()->back()->with('success', __('leave.messages.approved'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, EmployeeLeave $leave)
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            DB::transaction(function () use ($request, $leave) {
                $wasApproved = $leave->status === 'approved';
                $year = $leave->start_date ? $leave->start_date->year : date('Y');

                $leave->update([
                    'status'           => 'rejected',
                    'rejection_reason' => $request->rejection_reason,
                    'approved_by'      => admin()?->id,
                    'approved_at'      => now(),
                ]);

                if ($wasApproved) {
                    $balance = EmployeeLeaveBalance::where('employee_id', $leave->employee_id)
                        ->where('year', $year)
                        ->lockForUpdate()
                        ->first();
                    $balance?->recalculateUsed();
                }
            });

            $this->notifyLeaveDecided($leave->fresh(), 'rejected');

            return redirect()->back()->with('success', __('leave.messages.rejected'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy($leave)
    {
        if (!$leave instanceof EmployeeLeave) {
            $leave = EmployeeLeave::findOrFail($leave);
        }

        try {
            DB::transaction(function () use ($leave) {
                $wasApproved = $leave->status === 'approved';
                $year = $leave->start_date ? $leave->start_date->year : date('Y');

                $leave->delete();

                if ($wasApproved) {
                    $balance = EmployeeLeaveBalance::where('employee_id', $leave->employee_id)
                        ->where('year', $year)
                        ->lockForUpdate()
                        ->first();
                    $balance?->recalculateUsed();
                }
            });

            return redirect()->back()->with('success', __('leave.messages.deleted'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = EmployeeLeave::visibleTo()->onlyTrashed()->with(['employee:id,name,employee_code']);
            return datatables($query)
                ->addColumn('employee_name', fn($row) => $row->employee?->name ?? '-')
                ->addColumn('employee_code', fn($row) => $row->employee?->employee_code ?? '-')
                ->addColumn('leave_type_badge', function ($row) {
                    $lbl = __('leave.type_enums.' . $row->leave_type) ?? $row->leave_type;
                    return '<span class="badge bg-label-secondary">' . $lbl . '</span>';
                })
                ->addColumn('formatted_start', fn($row) => $row->start_date ? $row->start_date->format('Y-m-d') : '-')
                ->addColumn('formatted_end', fn($row) => $row->end_date ? $row->end_date->format('Y-m-d') : '-')
                ->addColumn('deleted_at', fn($row) => $row->deleted_at ? $row->deleted_at->format('Y-m-d H:i') : '-')
                ->addColumn('action', fn() => '')
                ->rawColumns(['leave_type_badge', 'action'])
                ->make(true);
        }
        return view('admin.hr.leaves.trash');
    }

    public function restore(int $id)
    {
        try {
            DB::transaction(function () use ($id) {
                $leave = EmployeeLeave::onlyTrashed()->findOrFail($id);
                $leave->restore();

                if ($leave->status === 'approved') {
                    $year = $leave->start_date ? $leave->start_date->year : date('Y');
                    $balance = EmployeeLeaveBalance::firstOrCreate(
                        ['employee_id' => $leave->employee_id, 'year' => $year],
                        [
                            'annual_balance' => $leave->employee?->annual_leave_entitlement ?? 21.00,
                            'casual_balance' => 7.00,
                            'sick_balance'   => 14.00,
                            'used_annual'    => 0,
                            'used_casual'    => 0,
                            'used_sick'      => 0,
                        ]
                    );
                    $balance->recalculateUsed();
                }
            });

            return redirect()->back()->with('success', __('global.restore'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function forceDelete(int $id)
    {
        $leave = EmployeeLeave::onlyTrashed()->findOrFail($id);
        $leave->forceDelete();
        return redirect()->back()->with('success', __('global.force_delete'));
    }

    // ========================================================
    //  الإشعارات
    // ========================================================

    /** إشعار المسؤولين بطلب إجازة جديد محتاج مراجعة. */
    private function notifyLeaveRequested(EmployeeLeave $leave): void
    {
        $employee = $leave->employee;

        app(NotificationService::class)->notifyEligible(
            type: NotificationType::LeaveRequested,
            title: __('notification.messages.leave_requested.title'),
            body: __('notification.messages.leave_requested.body', [
                'employee' => $employee?->name ?? '-',
                'days'     => $leave->days_count,
                'type'     => LeaveType::fromValue($leave->leave_type)?->label() ?? $leave->leave_type,
            ]),
            url: route('admin.leaves.index', ['employee_id' => $leave->employee_id]),
            branchId: $employee?->branch_id,
            data: ['leave_id' => $leave->id],
            excludeAdminId: admin()?->id,
        );
    }

    /** إشعار مقدّم الطلب بنتيجة المراجعة. */
    private function notifyLeaveDecided(EmployeeLeave $leave, string $decision): void
    {
        $employee = $leave->employee;
        $targetAdminId = $employee?->admin_id;

        // مفيش حساب مشرف مربوط بالموظف => مفيش حد نبلّغه
        if (!$targetAdminId) {
            return;
        }

        app(NotificationService::class)->send(
            adminId: $targetAdminId,
            type: NotificationType::LeaveDecided,
            title: __('notification.messages.leave_decided.title'),
            body: __('notification.messages.leave_decided.body', [
                'status' => LeaveStatus::fromValue($decision)?->label() ?? $decision,
                'days'   => $leave->days_count,
            ]),
            url: route('admin.leaves.index', ['employee_id' => $leave->employee_id]),
            branchId: $employee?->branch_id,
            data: ['leave_id' => $leave->id, 'decision' => $decision],
        );
    }
}
