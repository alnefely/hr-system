<?php

namespace App\Models;

use App\Models\Concerns\ScopedToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDocument extends Model
{
    use HasFactory, SoftDeletes, ScopedToBranch;

    /** العزل بيتم عبر فرع الموظف مش عمود على الجدول نفسه. */
    public $branchScopeVia = 'employee';

    protected $fillable = [
        'employee_id',
        'document_type',
        'title',
        'file_path',
        'issue_date',
        'expiry_date',
        'is_verified',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'issue_date'   => 'date',
        'expiry_date'  => 'date',
        'is_verified'  => 'boolean',
        'verified_at'  => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function verifier()
    {
        return $this->belongsTo(Admin::class, 'verified_by');
    }

    /** عدد أيام التنبيه قبل انتهاء الصلاحية. */
    public const EXPIRY_ALERT_DAYS = 30;

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * الأيام المتبقية للانتهاء — موجبة للمستقبل وسالبة للمنتهي.
     *
     * diffInDays لوحدها بترجّع قيمة بالاتجاه المعكوس، فكان أي
     * تاريخ مستقبلي بيدي رقم سالب والشرط "<= 30" بيتحقق عليه —
     * يعني مستند بينتهي سنة 2030 كان بيتحسب "ينتهي قريباً".
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expiry_date->startOfDay(), false);
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        $days = $this->days_until_expiry;

        return $days !== null && $days >= 0 && $days <= self::EXPIRY_ALERT_DAYS;
    }

    /** مستندات قرب انتهاء صلاحيتها (ولسه ما انتهتش). */
    public function scopeExpiringSoon(Builder $query, int $days = self::EXPIRY_ALERT_DAYS): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now()->toDateString())
            ->whereDate('expiry_date', '<=', now()->addDays($days)->toDateString());
    }

    /** مستندات انتهت صلاحيتها بالفعل. */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now()->toDateString());
    }

    /** مستندات لسه قيد المراجعة (مش معتمدة). */
    public function scopePendingVerification(Builder $query): Builder
    {
        return $query->where('is_verified', false);
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
