<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\ScopedToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorSubscription extends Model
{
    /**
     * معزولة بالفرع: مشرف الفرع بيشوف ويعدّل اشتراكات فرعه بس.
     * الاشتراك من غير فرع (عام) بيظهر للمشرف الرئيسي بس.
     */
    use HasFactory, SoftDeletes, LogsActivity, ScopedToBranch;

    protected $fillable = [
        'title',
        'vendor_id',
        'branch_id',
        'type',
        'billing_cycle',
        'cost',
        'currency',
        'start_date',
        'next_billing_date',
        'auto_renew',
        'status',
        'attachment',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'start_date' => 'date',
        'next_billing_date' => 'date',
        'auto_renew' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function payments()
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function getComputedStatusAttribute(): string
    {
        if ($this->status === 'active' && $this->next_billing_date && $this->next_billing_date->lt(now()->startOfDay())) {
            return 'expired';
        }
        return $this->status;
    }

    public function getRemainingDaysAttribute(): ?int
    {
        if (!$this->next_billing_date) {
            return null;
        }

        $today = now()->startOfDay();
        $target = $this->next_billing_date->startOfDay();

        return (int)$today->diffInDays($target, false);
    }
}
