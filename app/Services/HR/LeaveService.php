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
        $start   = Carbon::parse($data['start_date']);
        $end     = Carbon::parse($data['end_date']);
        $maxDays = (int) $start->diffInDays($end) + 1;

        // أقل عدد مقبول = الأيام التقويمية ناقص أيام الإجازة الأسبوعية (جمعة
        // وسبت). قبل كده إجازة 30 يوم كانت بتتسجّل "يوم واحد" وتستهلك يوم
        // من الرصيد. اليوم الواحد بس اللي ينفع يبقى نص يوم.
        $weekendDays = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if ($d->isFriday() || $d->isSaturday()) {
                $weekendDays++;
            }
        }
        $minDays = $maxDays === 1 ? 0.5 : max(1, $maxDays - $weekendDays);

        if ((float) $data['days_count'] > $maxDays || (float) $data['days_count'] < $minDays) {
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
        ApprovalService::ensureNotBeneficiary($leave->employee_id, 'leave');

        DB::transaction(function () use ($leave) {
            // الفحص جوّه القفل: طلبين اعتماد/رفض متزامنين مايشتغلوش على حالة قديمة
            $locked = EmployeeLeave::whereKey($leave->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== LeaveStatus::Pending->value) {
                throw new InvalidStateException(__('leave.errors.not_pending'));
            }

            $this->ensurePayrollOpen($locked);

            $balances = [];
            foreach ($this->yearsOf($locked) as $year) {
                $balances[$year] = $this->balanceFor($locked, lock: true, create: true, year: $year);
                $this->ensureSufficientBalance($locked, $balances[$year], $year);
            }

            $locked->update([
                'status'      => LeaveStatus::Approved->value,
                'approved_by' => admin()?->id,
                'approved_at' => now(),
            ]);

            foreach ($balances as $balance) {
                $balance->recalculateUsed();
            }

            $leave->setRawAttributes($locked->getAttributes(), true);
        });

        $this->notifyDecided($leave->fresh(), LeaveStatus::Approved);
    }

    /** الرفض مسموح للمعلّق والمعتمد (إلغاء إجازة معتمدة بيرجّع رصيدها). */
    public function reject(EmployeeLeave $leave, string $reason): void
    {
        DB::transaction(function () use ($leave, $reason) {
            $locked = EmployeeLeave::whereKey($leave->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [LeaveStatus::Pending->value, LeaveStatus::Approved->value], true)) {
                throw new InvalidStateException(__('leave.errors.not_pending'));
            }

            $wasApproved = $locked->status === LeaveStatus::Approved->value;

            if ($wasApproved) {
                $this->ensurePayrollOpen($locked);
            }

            $locked->update([
                'status'           => LeaveStatus::Rejected->value,
                'rejection_reason' => $reason,
                'approved_by'      => admin()?->id,
                'approved_at'      => now(),
            ]);

            if ($wasApproved) {
                $this->recalculateBalances($locked);
            }

            $leave->setRawAttributes($locked->getAttributes(), true);
        });

        $this->notifyDecided($leave->fresh(), LeaveStatus::Rejected);
    }

    public function delete(EmployeeLeave $leave): void
    {
        DB::transaction(function () use ($leave) {
            $wasApproved = $leave->status === LeaveStatus::Approved->value;

            $leave->delete();

            if ($wasApproved) {
                $this->recalculateBalances($leave);
            }
        });
    }

    public function restore(int $id): void
    {
        DB::transaction(function () use ($id) {
            $leave = EmployeeLeave::onlyTrashed()->findOrFail($id);
            $leave->restore();

            if ($leave->status === LeaveStatus::Approved->value) {
                foreach ($this->yearsOf($leave) as $year) {
                    $this->balanceFor($leave, create: true, year: $year)->recalculateUsed();
                }
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
        // array_flip: البحث O(1) بدل in_array جوّه اللوب
        $existing = array_flip(EmployeeLeaveBalance::where('year', $year)->pluck('employee_id')->all());
        $now = now();
        $rows = [];

        foreach ($employees as $employee) {
            if (isset($existing[$employee->id])) {
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

    /** الإجازة بدون أجر بتأثر على المسير — مايتغيّرش قرارها بعد اعتماد مسير شهرها. */
    private function ensurePayrollOpen(EmployeeLeave $leave): void
    {
        if ($leave->leave_type === LeaveType::Unpaid->value
            && $leave->start_date && $leave->end_date
            && PayrollRules::hasClosedPayroll($leave->employee_id, $leave->start_date, $leave->end_date)) {
            throw new InvalidStateException(__('payroll.messages.period_closed'));
        }
    }

    /** السنين اللي الإجازة بتقع فيها (سنة أو اتنين). @return array<int, int> */
    private function yearsOf(EmployeeLeave $leave): array
    {
        $first = $this->yearOf($leave);
        $last  = $leave->end_date ? $leave->end_date->year : $first;

        return range($first, max($first, $last));
    }

    private function recalculateBalances(EmployeeLeave $leave): void
    {
        foreach ($this->yearsOf($leave) as $year) {
            $this->balanceFor($leave, lock: true, year: $year)?->recalculateUsed();
        }
    }

    private function balanceFor(EmployeeLeave $leave, bool $lock = false, bool $create = false, ?int $year = null): ?EmployeeLeaveBalance
    {
        $year ??= $this->yearOf($leave);

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
    private function ensureSufficientBalance(EmployeeLeave $leave, EmployeeLeaveBalance $balance, ?int $year = null): void
    {
        // نصيب السنة دي بس من أيام الإجازة
        $requested = $year
            ? PayrollRules::leaveDaysWithin($leave, sprintf('%04d-01-01', $year), sprintf('%04d-12-31', $year))
            : (float) $leave->days_count;

        $remaining = match (LeaveType::fromValue($leave->leave_type)) {
            LeaveType::Annual => $balance->remaining_annual,
            LeaveType::Casual => $balance->remaining_casual,
            LeaveType::Sick   => $balance->remaining_sick,
            default           => null,
        };

        if ($remaining !== null && $remaining < $requested) {
            throw new InvalidStateException(__('leave.errors.insufficient_' . $leave->leave_type, [
                'remaining' => $remaining,
                'requested' => $requested,
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
            excludeAdminId: \App\Services\Notification\NotificationService::actingAdminId(),
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
