<?php

namespace App\Models;

use App\Enums\RewardStatus;
use App\Models\Concerns\HasDateRangeScopes;
use App\Models\Concerns\ScopedToBranch;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\FlushesDashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeReward extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasDateRangeScopes, ScopedToBranch, FlushesDashboardCache;

    /** العزل بيتم عبر فرع الموظف مش عمود على الجدول نفسه. */
    public $branchScopeVia = 'employee';

    /** العمود اللي scopes التاريخ بتشتغل عليه افتراضياً. */
    protected string $dateRangeColumn = 'reward_date';

    protected $fillable = [
        'employee_id',
        'reward_type',
        'title',
        'amount',
        'reward_date',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'reward_date' => 'date',
        'approved_at' => 'datetime',
        'amount'      => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    // ========================================================
    //  الاعتماد
    // ========================================================

    public function statusEnum(): ?RewardStatus
    {
        return RewardStatus::fromValue($this->status);
    }

    /** المعتمد بس هو اللي بيدخل مسير الرواتب. */
    public function scopeApproved($query)
    {
        return $query->where('status', RewardStatus::Approved->value);
    }

    public function scopePending($query)
    {
        return $query->where('status', RewardStatus::Pending->value);
    }

    public function isApproved(): bool
    {
        return $this->status === RewardStatus::Approved->value;
    }

    public function isPending(): bool
    {
        return $this->status === RewardStatus::Pending->value;
    }

    /** التعديل والحذف ممنوعين بعد الاعتماد أو الصرف — الحركة بقت جزء من المسير. */
    public function isEditable(): bool
    {
        return RewardStatus::fromValue($this->status)?->isEditable() ?? true;
    }
}
