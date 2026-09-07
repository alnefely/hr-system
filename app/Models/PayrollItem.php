<?php

namespace App\Models;

use App\Models\Concerns\ScopedToBranch;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    use HasFactory, LogsActivity, ScopedToBranch;

    /** العزل بيتم عبر فرع الموظف مش عمود على الجدول نفسه. */
    public $branchScopeVia = 'employee';

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'basic_salary',
        'allowances',
        'allowance_details',
        'deductions',
        'deduction_details',
        'net_salary',
        'status',
        'payment_method',
        'paid_at',
        'paid_by',
        'notes',
    ];

    protected $casts = [
        'basic_salary'      => 'decimal:2',
        'allowances'        => 'decimal:2',
        'allowance_details' => 'array',
        'deductions'        => 'decimal:2',
        'deduction_details' => 'array',
        'net_salary'        => 'decimal:2',
        'paid_at'           => 'datetime',
    ];

    /**
     * لما بنبني المسير لعدد كبير من الموظفين، إعادة حساب إجماليات المسير
     * بعد كل بند بتعمل 4 استعلامات SUM لكل صف. الفلاج ده بيوقف ده مؤقتاً،
     * والمسير بيتحسب مرة واحدة في الآخر.
     */
    public static bool $syncPayrollTotals = true;

    protected static function booted(): void
    {
        static::saving(function (PayrollItem $item) {
            // الإجماليات بتتحسب من التفاصيل بس لو التفاصيل هي اللي اتغيرت.
            // من غير الشرط ده، أي تعديل يدوي على allowances/deductions كان بيتمسح.
            if ($item->isDirty('allowance_details') && is_array($item->allowance_details)) {
                $item->allowances = array_reduce($item->allowance_details, function ($sum, $row) {
                    return $sum + (float) ($row['amount'] ?? 0);
                }, 0.0);
            }

            if ($item->isDirty('deduction_details') && is_array($item->deduction_details)) {
                $item->deductions = array_reduce($item->deduction_details, function ($sum, $row) {
                    return $sum + (float) ($row['amount'] ?? 0);
                }, 0.0);
            }

            $item->net_salary = max(0, round(((float) $item->basic_salary + (float) $item->allowances) - (float) $item->deductions, 2));
        });

        static::saved(function (PayrollItem $item) {
            if (static::$syncPayrollTotals) {
                $item->payroll?->recalculateTotals();
            }
        });

        static::deleted(function (PayrollItem $item) {
            if (static::$syncPayrollTotals) {
                $item->payroll?->recalculateTotals();
            }
        });
    }

    /** تنفيذ عملية جماعية من غير إعادة حساب إجماليات المسير بعد كل بند. */
    public static function withoutSyncingTotals(callable $callback): mixed
    {
        static::$syncPayrollTotals = false;

        try {
            return $callback();
        } finally {
            static::$syncPayrollTotals = true;
        }
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'paid_by');
    }
}
