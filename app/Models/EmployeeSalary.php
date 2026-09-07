<?php

namespace App\Models;

use App\Models\Concerns\ScopedToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSalary extends Model
{
    use HasFactory, SoftDeletes, ScopedToBranch;

    /** العزل بيتم عبر فرع الموظف مش عمود على الجدول نفسه. */
    public $branchScopeVia = 'employee';

    protected $fillable = [
        'employee_id',
        'basic_salary',
        'allowances',
        'currency',
        'effective_date',
        'change_reason',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'basic_salary'   => 'decimal:2',
        'allowances'     => 'decimal:2',
        'effective_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function getTotalSalaryAttribute(): float
    {
        return (float) ($this->basic_salary + $this->allowances);
    }

    public function getCreatedAtAttribute($date)
    {
        return FormatDate($date);
    }

    public function getUpdatedAtAttribute($date)
    {
        return FormatDate($date);
    }

    public function getDeletedAtAttribute($date)
    {
        return $date ? FormatDate($date) : null;
    }
}
