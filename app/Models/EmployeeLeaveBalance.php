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
        // استعلام واحد مجمّع بالنوع بدل 4 استعلامات SUM
        $used = EmployeeLeave::where('employee_id', $this->employee_id)
            ->where('status', LeaveStatus::Approved->value)
            ->inYear($this->year, 'start_date')
            ->groupBy('leave_type')
            ->selectRaw('leave_type, SUM(days_count) as total')
            ->pluck('total', 'leave_type');

        $this->used_annual = (float) ($used[LeaveType::Annual->value] ?? 0);
        $this->used_casual = (float) ($used[LeaveType::Casual->value] ?? 0);
        $this->used_sick   = (float) ($used[LeaveType::Sick->value] ?? 0);
        $this->used_unpaid = (float) ($used[LeaveType::Unpaid->value] ?? 0);

        $this->saveQuietly();
    }
}
