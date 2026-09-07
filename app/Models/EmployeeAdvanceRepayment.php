<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAdvanceRepayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_advance_id',
        'amount',
        'repayment_date',
        'type',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'repayment_date' => 'date',
        'amount'         => 'decimal:2',
    ];

    public function advance(): BelongsTo
    {
        return $this->belongsTo(EmployeeAdvance::class, 'employee_advance_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
