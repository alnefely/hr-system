<?php

namespace App\Models;

use App\Models\Concerns\HasDateRangeScopes;
use App\Models\Concerns\ScopedToBranch;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePermission extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasDateRangeScopes, ScopedToBranch;

    /** العزل بيتم عبر فرع الموظف مش عمود على الجدول نفسه. */
    public $branchScopeVia = 'employee';

    /** العمود اللي scopes التاريخ بتشتغل عليه افتراضياً. */
    protected string $dateRangeColumn = 'permission_date';

    protected $fillable = [
        'employee_id',
        'permission_date',
        'start_time',
        'end_time',
        'hours_count',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'permission_date' => 'date',
        'approved_at'     => 'datetime',
        'hours_count'     => 'decimal:2',
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
