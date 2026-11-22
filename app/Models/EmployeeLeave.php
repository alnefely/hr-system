<?php

namespace App\Models;

use App\Models\Concerns\HasDateRangeScopes;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\ScopedToBranch;
use App\Models\Concerns\FlushesDashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeLeave extends Model
{
    use HasFactory, SoftDeletes, ScopedToBranch, LogsActivity, HasDateRangeScopes, FlushesDashboardCache;

    /** العمود اللي scopes التاريخ بتشتغل عليه افتراضياً. */
    protected string $dateRangeColumn = 'start_date';

    /** العزل بيتم عبر فرع الموظف مش عمود على الجدول نفسه. */
    public $branchScopeVia = 'employee';

    protected $fillable = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'attachment',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'approved_at' => 'datetime',
        'days_count'  => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

}
