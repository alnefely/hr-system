<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Models\Concerns\HasDateRangeScopes;
use App\Models\Concerns\ScopedToBranch;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\FlushesDashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePenalty extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasDateRangeScopes, ScopedToBranch, FlushesDashboardCache;

    /** العزل بيتم عبر فرع الموظف مش عمود على الجدول نفسه. */
    public $branchScopeVia = 'employee';

    /** العمود اللي scopes التاريخ بتشتغل عليه افتراضياً. */
    protected string $dateRangeColumn = 'penalty_date';

    protected $fillable = [
        'employee_id',
        'penalty_type',
        'days_count',
        'deduction_amount',
        'penalty_date',
        'reason',
        'notes',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'created_by',
    ];

    protected $casts = [
        'penalty_date'     => 'date',
        'days_count'       => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'approved_at'      => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    // ========================================================
    //  الاعتماد
    // ========================================================

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function statusEnum(): ?ApprovalStatus
    {
        return ApprovalStatus::fromValue($this->status);
    }

    /** المعتمد بس هو اللي بيدخل مسير الرواتب. */
    public function scopeApproved($query)
    {
        return $query->where('status', ApprovalStatus::Approved->value);
    }

    public function scopePending($query)
    {
        return $query->where('status', ApprovalStatus::Pending->value);
    }

    public function isApproved(): bool
    {
        return $this->status === ApprovalStatus::Approved->value;
    }

    public function isPending(): bool
    {
        return $this->status === ApprovalStatus::Pending->value;
    }

    /** التعديل والحذف ممنوعين بعد الاعتماد — الحركة بقت جزء من المسير. */
    public function isEditable(): bool
    {
        return $this->status !== ApprovalStatus::Approved->value;
    }
}
