<?php

namespace App\Models;

use App\Models\Concerns\ScopedToBranch;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasFactory, LogsActivity, ScopedToBranch;

    protected $fillable = [
        'title',
        'year',
        'month',
        'branch_id',
        'status',
        'total_basic_salary',
        'total_allowances',
        'total_deductions',
        'total_net_salary',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'unapproved_by',
        'unapproved_at',
        'unapprove_reason',
        'paid_by',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'year'               => 'integer',
        'month'              => 'integer',
        'total_basic_salary' => 'decimal:2',
        'total_allowances'   => 'decimal:2',
        'total_deductions'   => 'decimal:2',
        'total_net_salary'   => 'decimal:2',
        'approved_at'        => 'datetime',
        'rejected_at'        => 'datetime',
        'unapproved_at'      => 'datetime',
        'paid_at'            => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'rejected_by');
    }

    public function unapprover(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'unapproved_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'paid_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function recalculateTotals(): void
    {
        $this->total_basic_salary = $this->items()->sum('basic_salary');
        $this->total_allowances   = $this->items()->sum('allowances');
        $this->total_deductions   = $this->items()->sum('deductions');
        $this->total_net_salary   = $this->items()->sum('net_salary');
        $this->saveQuietly();
    }
}
