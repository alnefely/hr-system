<?php

namespace App\Services\HR;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\NotificationType;
use App\Exceptions\InvalidStateException;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveBalance;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * طلبات الإجازة وأرصدتها.
 *
 * كانت كلها جوّه EmployeeLeaveController. أي عملية مش مسموحة (رصيد مش
 * كفاية، تداخل، طلب مش معلّق) بترمي InvalidStateException برسالة مترجمة.
 *
 * الرصيد المستهلك دايماً بيتحسب من الإجازات المعتمدة (recalculateUsed)
 * مش بالزيادة والنقص — فأي حذف أو استرجاع أو رفض بيرجّع الرقم الصح.
 */
class LeaveService
{
    /** الأرصدة الافتراضية لأي سنة جديدة. */
    public const DEFAULT_ANNUAL = 21.00;
    public const DEFAULT_CASUAL = 7.00;
    public const DEFAULT_SICK   = 14.00;

    public function __construct(
        private NotificationService $notifications,
    ) {}

    // ========================================================
    //  الطلبات
    // ========================================================

    public function create(array $data): EmployeeLeave
    {
        $maxDays = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;

        if ((float) $data['days_count'] > $maxDays) {
            throw new InvalidStateException(__('leave.errors.invalid_days_count', [
                'days' => $data['days_count'],
                'max'  => $maxDays,
            ]));
        }

        $overlap = EmployeeLeave::where('employee_id', $data['employee_id'])
            ->whereIn('status', [LeaveStatus::Pending->value, LeaveStatus::Approved->value])
            ->where('start_date', '<=', $data['end_date'])
            ->where('end_date', '>=', $data['start_date'])
            ->exists();

        if ($overlap) {
            throw new InvalidStateException(__('leave.errors.overlap'));
        }

        $leave = EmployeeLeave::create(['status' => LeaveStatus::Pending->value] + $data);

        $this->notifyRequested($leave);

        return $leave;
    }

    public function approve(EmployeeLeave $leave): void
    {
        if ($leave->status !== LeaveStatus::Pending->value) {
            throw new InvalidStateException(__('leave.errors.not_pending'));
        }

        DB::transaction(function () use ($leave) {
            $balance = $this->balanceFor($leave, lock: true, create: true);

            $this->ensureSufficientBalance($leave, $balance);

            $leave->update([
                'status'      => LeaveStatus::Approved->value,
                'approved_by' => admin()?->id,
                'approved_at' => now(),
            ]);

            $balance->recalculateUsed();
        });

        $this->notifyDecided($leave->fresh(), LeaveStatus::Approved);
    }

    /** الرفض مسموح للمعلّق والمعتمد (إلغاء إجازة معتمدة بيرجّع رصيدها). */
    public function reject(EmployeeLeave $leave, string $reason): void
    {
        DB::transaction(function () use ($leave, $reason) {
            $wasApproved = $leave->status === LeaveStatus::Approved->value;

            $leave->update([
                'status'           => LeaveStatus::Rejected->value,
                'rejection_reason' => $reason,
                'approved_by'      => admin()?->id,
                'approved_at'      => now(),
            ]);

            if ($wasApproved) {
                $this->balanceFor($leave, lock: true)?->recalculateUsed();
            }
        });

        $this->notifyDecided($leave->fresh(), LeaveStatus::Rejected);
    }

    public function delete(EmployeeLeave $leave): void
    {
        DB::transaction(function () use ($leave) {
            $wasApproved = $leave->status === LeaveStatus::Approved->value;

            $leave->delete();

            if ($wasApproved) {
                $this->balanceFor($leave, lock: true)?->recalculateUsed();
            }
        });
    }

    public function restore(int $id): void
    {
        DB::transaction(function () use ($id) {
            $leave = EmployeeLeave::onlyTrashed()->findOrFail($id);
            $leave->restore();

            if ($leave->status === LeaveStatus::Approved->value) {
                $this->balanceFor($leave, create: true)->recalculateUsed();
            }
        });
    }

    // ========================================================
    //  الأرصدة
    // ========================================================

    /**
     * إنشاء أرصدة السنة للموظفين اللي مالهمش رصيد — في INSERT واحد.
     *
     * @param  iterable<Employee>  $employees  محتاجة id و annual_leave_entitlement
     */
    public function ensureBalances(iterable $employees, int $year): void
    {
        $existing = EmployeeLeaveBalance::where('year', $year)->pluck('employee_id')->all();
        $now = now();
        $rows = [];

        foreach ($employees as $employee) {
            if (in_array($employee->id, $existing)) {
                continue;
            }

            $rows[] = $this->defaultBalance($employee->id, $year, $employee->annual_leave_entitlement) + [
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            EmployeeLeaveBalance::insert($rows);
        }
    }

    public function updateBalance(EmployeeLeaveBalance $balance, array $data): void
    {
        $balance->update($data);
    }

    // ========================================================
    //  الداخلي
    // ========================================================

    private function yearOf(EmployeeLeave $leave): int
    {
        return $leave->start_date ? $leave->start_date->year : (int) date('Y');
    }

    private function balanceFor(EmployeeLeave $leave, bool $lock = false, bool $create = false): ?EmployeeLeaveBalance
    {
        $year = $this->yearOf($leave);

        $balance = EmployeeLeaveBalance::where('employee_id', $leave->employee_id)
            ->where('year', $year)
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->first();

        if (! $balance && $create) {
            $balance = EmployeeLeaveBalance::create(
                $this->defaultBalance($leave->employee_id, $year, $leave->employee?->annual_leave_entitlement)
            );
        }

        return $balance;
    }

    private function defaultBalance(int $employeeId, int $year, mixed $annualEntitlement): array
    {
        return [
            'employee_id'    => $employeeId,
            'year'           => $year,
            'annual_balance' => $annualEntitlement ?? self::DEFAULT_ANNUAL,
            'casual_balance' => self::DEFAULT_CASUAL,
            'sick_balance'   => self::DEFAULT_SICK,
            'used_annual'    => 0,
            'used_casual'    => 0,
            'used_sick'      => 0,
        ];
    }

    /** الإجازة بدون أجر مالهاش رصيد — الباقي لازم يكفي قبل الاعتماد. */
    private function ensureSufficientBalance(EmployeeLeave $leave, EmployeeLeaveBalance $balance): void
    {
        $remaining = match (LeaveType::fromValue($leave->leave_type)) {
            LeaveType::Annual => $balance->remaining_annual,
            LeaveType::Casual => $balance->remaining_casual,
            LeaveType::Sick   => $balance->remaining_sick,
            default           => null,
        };

        if ($remaining !== null && $remaining < $leave->days_count) {
            throw new InvalidStateException(__('leave.errors.insufficient_' . $leave->leave_type, [
                'remaining' => $remaining,
                'requested' => $leave->days_count,
            ]));
        }
    }

    /** إشعار المسؤولين بطلب إجازة جديد محتاج مراجعة. */
    private function notifyRequested(EmployeeLeave $leave): void
    {
        $employee = $leave->employee;

        $this->notifications->notifyEligible(
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
    private function notifyDecided(EmployeeLeave $leave, LeaveStatus $decision): void
    {
        $employee = $leave->employee;

        // مفيش حساب مشرف مربوط بالموظف => مفيش حد نبلّغه
        if (! $employee?->admin_id) {
            return;
        }

        $this->notifications->send(
            adminId: $employee->admin_id,
            type: NotificationType::LeaveDecided,
            title: __('notification.messages.leave_decided.title'),
            body: __('notification.messages.leave_decided.body', [
                'status' => $decision->label(),
                'days'   => $leave->days_count,
            ]),
            url: route('admin.leaves.index', ['employee_id' => $leave->employee_id]),
            branchId: $employee->branch_id,
            data: ['leave_id' => $leave->id, 'decision' => $decision->value],
        );
    }
}
