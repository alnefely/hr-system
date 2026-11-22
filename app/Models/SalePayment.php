<?php

namespace App\Models;

use App\Models\Concerns\HasDateRangeScopes;
use App\Models\Concerns\FlushesDashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalePayment extends Model
{
    use HasFactory, SoftDeletes, HasDateRangeScopes, FlushesDashboardCache;

    /** العمود اللي scopes التاريخ بتشتغل عليه افتراضياً. */
    protected string $dateRangeColumn = 'payment_date';

    protected $fillable = [
        'sale_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
