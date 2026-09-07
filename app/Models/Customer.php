<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'company_name',
        'phone',
        'phone_country',
        'email',
        'tax_number',
        'address',
        'customer_type',
        'customer_source_id',
        'source',
        'industry',
        'contact_person',
        'contact_position',
        'secondary_phone',
        'secondary_phone_country',
        'website',
        'credit_limit',
        'payment_terms',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'credit_limit' => 'decimal:2',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function customerSource(): BelongsTo
    {
        return $this->belongsTo(CustomerSource::class);
    }

    /**
     * اسم المصدر للعرض: المرجع الجديد وإلا النص القديم.
     * القيم القديمة كانت مفاتيح زي 'referral' فبنترجمها.
     */
    public function sourceLabel(): ?string
    {
        if ($this->customerSource) {
            return $this->customerSource->name;
        }

        if (! $this->source) {
            return null;
        }

        $key = 'customer.enums.' . $this->source;

        return __($key) === $key ? $this->source : __($key);
    }
}
