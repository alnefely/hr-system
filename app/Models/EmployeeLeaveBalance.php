<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLeaveBalance extends Model
{
    use HasFactory;

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
        $this->used_annual = (float) EmployeeLeave::where('employee_id', $this->employee_id)
            ->where('status', 'approved')
            ->where('leave_type', 'annual')
            ->inYear($this->year, 'start_date')
            ->sum('days_count');

        $this->used_casual = (float) EmployeeLeave::where('employee_id', $this->employee_id)
            ->where('status', 'approved')
            ->where('leave_type', 'casual')
            ->inYear($this->year, 'start_date')
            ->sum('days_count');

        $this->used_sick = (float) EmployeeLeave::where('employee_id', $this->employee_id)
            ->where('status', 'approved')
            ->where('leave_type', 'sick')
            ->inYear($this->year, 'start_date')
            ->sum('days_count');

        $this->used_unpaid = (float) EmployeeLeave::where('employee_id', $this->employee_id)
            ->where('status', 'approved')
            ->where('leave_type', 'unpaid')
            ->inYear($this->year, 'start_date')
            ->sum('days_count');

        $this->saveQuietly();
    }
}
