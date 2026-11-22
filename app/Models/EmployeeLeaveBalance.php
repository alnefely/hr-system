<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Concerns\ScopedToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLeaveBalance extends Model
{
    use HasFactory, ScopedToBranch;

    /**
     * العزل عبر فرع الموظف. من غيره مسار تعديل الرصيد
     * (leaves/balances/{balance}) كان بيقبل أي id حتى لو الموظف في فرع تاني.
     */
    public $branchScopeVia = 'employee';

    protected $fillable = [
        'employee_id',
        'year',
        'annual_balance',
        'casual_balance',
        'sick_balance',
        'used_annual',
        'used_casual',
        'used_sick',
        'used_unpaid',
    ];

    protected $casts = [
        'annual_balance' => 'decimal:2',
        'casual_balance' => 'decimal:2',
        'sick_balance'   => 'decimal:2',
        'used_annual'    => 'decimal:2',
        'used_casual'    => 'decimal:2',
        'used_sick'      => 'decimal:2',
        'used_unpaid'    => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getRemainingAnnualAttribute(): float
    {
        return max(0, (float) $this->annual_balance - (float) $this->used_annual);
    }

    public function getRemainingCasualAttribute(): float
    {
        return max(0, (float) $this->casual_balance - (float) $this->used_casual);
    }

    public function getRemainingSickAttribute(): float
    {
        return max(0, (float) $this->sick_balance - (float) $this->used_sick);
    }

    /**
     * إعادة احتساب وتحديث الأرصدة المستهلكة من واقع الإجازات المعتمدة فقط
     */
    public function recalculateUsed(): void
    {
        // الإجازة اللي بتعدّي السنة (28 ديسمبر → 5 يناير) بتتوزّع على السنتين
        // بنسبة أيامها، بدل ما تتحسب كلها على سنة البداية.
        $from = sprintf('%04d-01-01', $this->year);
        $to   = sprintf('%04d-12-31', $this->year);

        $used = \App\Services\HR\PayrollRules::overlapping(
                EmployeeLeave::where('employee_id', $this->employee_id)
                    ->where('status', LeaveStatus::Approved->value),
                $from,
                $to
            )
            ->get(['id', 'leave_type', 'start_date', 'end_date', 'days_count'])
            ->groupBy('leave_type')
            ->map(fn ($leaves) => round($leaves->sum(
                fn (EmployeeLeave $leave) => \App\Services\HR\PayrollRules::leaveDaysWithin($leave, $from, $to)
            ), 2));

        $this->used_annual = (float) ($used[LeaveType::Annual->value] ?? 0);
        $this->used_casual = (float) ($used[LeaveType::Casual->value] ?? 0);
        $this->used_sick   = (float) ($used[LeaveType::Sick->value] ?? 0);
        $this->used_unpaid = (float) ($used[LeaveType::Unpaid->value] ?? 0);

        $this->saveQuietly();
    }
}
