<?php

namespace App\Models;

use App\Enums\AdvanceStatus;
use App\Models\Concerns\HasDateRangeScopes;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\ScopedToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAdvance extends Model
{
    use HasFactory, SoftDeletes, ScopedToBranch, LogsActivity, HasDateRangeScopes;

    /** العمود اللي scopes التاريخ بتشتغل عليه افتراضياً. */
    protected string $dateRangeColumn = 'advance_date';

    /** العزل بيتم عبر فرع الموظف مش عمود على الجدول نفسه. */
    public $branchScopeVia = 'employee';

    protected $fillable = [
        'employee_id',
        'amount',
        'installments_count',
        'monthly_installment',
        'paid_amount',
        'waived_amount',
        'remaining_amount',
        'advance_date',
        'start_date',
        'status',
        'approved_by',
        'approved_at',
        'reason',
        'notes',
    ];

    protected $casts = [
        'amount'              => 'decimal:2',
        'monthly_installment' => 'decimal:2',
        'paid_amount'         => 'decimal:2',
        'waived_amount'       => 'decimal:2',
        'remaining_amount'    => 'decimal:2',
        'advance_date'        => 'date',
        'start_date'          => 'date',
        'approved_at'         => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(EmployeeAdvanceRepayment::class, 'employee_advance_id')->latest('repayment_date');
    }

    /**
     * إعادة احتساب سدادات وإسقاطات السلفة وتحديث الأرصدة والحالة آلياً
     */
    public function recalculateRepayments(): void
    {
        $paidTotal = (float) $this->repayments()->where('type', '!=', 'manager_waiver')->sum('amount');
        $waivedTotal = (float) $this->repayments()->where('type', 'manager_waiver')->sum('amount');
        $totalSettled = round($paidTotal + $waivedTotal, 2);

        $this->paid_amount = round($paidTotal, 2);
        $this->waived_amount = round($waivedTotal, 2);
        $this->remaining_amount = max(0, round((float) $this->amount - $totalSettled, 2));

        if ($this->remaining_amount <= 0 && in_array($this->status, [AdvanceStatus::Approved->value, AdvanceStatus::Pending->value], true)) {
            $this->status = AdvanceStatus::Paid->value;
        } elseif ($this->remaining_amount > 0 && $this->status === AdvanceStatus::Paid->value) {
            $this->status = AdvanceStatus::Approved->value;
        }

        $this->saveQuietly();
    }

}
