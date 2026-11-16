<?php

namespace App\Models;

use App\Models\Concerns\ScopedToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyCashBalance extends Model
{
    /**
     * العزل على عمود branch_id. من غيره مشرف الفرع كان بيشوف أرصدة
     * عهد كل الفروع في شاشة المصاريف وفي get-custody-balance.
     * الأوامر المجدولة مافيهاش مشرف، فالعزل مابيأثرش على الترحيل.
     */
    use HasFactory, ScopedToBranch;

    protected $fillable = [
        'branch_id',
        'year',
        'month',
        'allocated_amount',
        'carried_over_amount',
        'total_balance',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'year'                => 'integer',
        'month'               => 'integer',
        'allocated_amount'    => 'decimal:2',
        'carried_over_amount' => 'decimal:2',
        'total_balance'       => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (PettyCashBalance $balance) {
            $balance->total_balance = (float) $balance->allocated_amount + (float) $balance->carried_over_amount;
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
