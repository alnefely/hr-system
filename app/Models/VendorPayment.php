<?php

namespace App\Models;

use App\Models\Concerns\HasDateRangeScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorPayment extends Model
{
    use HasFactory, HasDateRangeScopes;

    /** العمود اللي scopes التاريخ بتشتغل عليه افتراضياً. */
    protected string $dateRangeColumn = 'payment_date';

    protected $fillable = [
        'vendor_subscription_id',
        'amount',
        'payment_date',
        'payment_method',
        'transaction_reference',
        'invoice_number',
        'attachment',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function subscription()
    {
        return $this->belongsTo(VendorSubscription::class, 'vendor_subscription_id');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
