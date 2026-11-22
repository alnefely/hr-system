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

class Expense extends Model
{
    use HasFactory, SoftDeletes, ScopedToBranch, LogsActivity, HasDateRangeScopes, FlushesDashboardCache;

    /** العمود اللي scopes التاريخ بتشتغل عليه افتراضياً. */
    protected string $dateRangeColumn = 'expense_date';

    protected $fillable = [
        'title',
        'expense_category_id',
        'branch_id',
        'employee_id',
        'amount',
        'currency',
        'expense_date',
        'payment_method',
        'invoice_number',
        'attachment',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
    ];

    /**
     * أي تغيير في مصروف بيعيد حساب ترحيل العهدة للفرع فوراً.
     *
     * المرحّل = إجمالي الشهر اللي فات − مصروفه، فتعديل مصروف قديم
     * بيغيّر كل الشهور اللي بعده. من غير ده كان بيفضل غلط لحد ما
     * الأمر الشهري يشتغل أول الشهر الجاي.
     */
    protected static function booted(): void
    {
        $recalculate = function (Expense $expense) {
            $branches = array_filter(array_unique([
                $expense->branch_id,
                $expense->getOriginal('branch_id'),   // لو الفرع نفسه اتغيّر
            ]));

            $service = app(\App\Services\Finance\PettyCashService::class);

            foreach ($branches as $branchId) {
                $service->recalculateBranch((int) $branchId, now()->year, now()->month);
            }
        };

        static::saved($recalculate);
        static::deleted($recalculate);
        static::restored($recalculate);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

}
