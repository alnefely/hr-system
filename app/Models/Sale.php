<?php

namespace App\Models;

use App\Models\Concerns\HasDateRangeScopes;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\ScopedToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes, ScopedToBranch, LogsActivity, HasDateRangeScopes;

    /** العمود اللي scopes التاريخ بتشتغل عليه افتراضياً. */
    protected string $dateRangeColumn = 'sale_date';

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'sale_type',
        'sales_channel',
        'sales_channel_id',
        'branch_id',
        'sale_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'paid_amount',
        'due_amount',
        'payment_status',
        'payment_method',
        'attachment',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesChannel()
    {
        return $this->belongsTo(SalesChannel::class, 'sales_channel_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class)->orderBy('payment_date', 'asc');
    }

    /**
     * أول رقم فاتورة متاح للشهر الحالي.
     *
     * بيعتمد على أعلى رقم متسجل فعلاً (بما فيه المحذوف soft-deleted)
     * مش على count()، عشان الأرقام ماتتكررش بعد أي حذف.
     */
    public static function nextInvoiceNumber(?string $period = null): string
    {
        $period = $period ?: date('Ym');
        $prefix = 'INV-' . $period . '-';

        $lastNumber = static::withTrashed()
            ->where('invoice_number', 'like', $prefix . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(invoice_number, ?) AS UNSIGNED)) as seq', [strlen($prefix) + 1])
            ->value('seq');

        return $prefix . str_pad((int) $lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function recalculatePayments(): void
    {
        $totalPaid = round((float) $this->payments()->sum('amount'), 2);
        $this->paid_amount = $totalPaid;
        $this->due_amount  = round(max(0, (float) $this->total_amount - $totalPaid), 2);

        if ($this->due_amount <= 0.001) {
            $this->payment_status = 'paid';
        } elseif ($totalPaid > 0) {
            $this->payment_status = 'partially_paid';
        } else {
            $this->payment_status = 'unpaid';
        }

        $this->save();
    }

}
