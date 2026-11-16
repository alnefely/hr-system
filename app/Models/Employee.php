<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\ScopedToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes, ScopedToBranch, LogsActivity;

    /** حقول حساسة مابتتسجّلش في سجل الحركات. */
    protected array $activityHidden = ['national_id', 'iban', 'bank_account_number', 'insurance_number'];

    protected $fillable = [
        'employee_code',
        'name',
        'national_id',
        'birth_date',
        'gender',
        'marital_status',
        'nationality',
        'phone',
        'phone_country',
        'alt_phone',
        'alt_phone_country',
        'personal_email',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_phone_country',
        'address',
        'avatar',
        'branch_id',
        'job_title_id',
        'manager_id',
        'admin_id',
        'hire_date',
        'probation_end_date',
        'contract_type',
        'status',
        'termination_date',
        'termination_reason',
        'weekly_hours',
        'basic_salary',
        'annual_leave_entitlement',
        'monthly_permission_hours_limit',
        'daily_work_hours',
        'custom_daily_rate',
        'custom_hourly_rate',
        'bank_name',
        'bank_account_number',
        'iban',
        'insurance_number',
        'notes',
    ];

    protected $casts = [
        'birth_date'                     => 'date',
        'hire_date'                      => 'date',
        'probation_end_date'             => 'date',
        'termination_date'               => 'date',
        'weekly_hours'                   => 'integer',
        'basic_salary'                   => 'decimal:2',
        'annual_leave_entitlement'       => 'decimal:2',
        'monthly_permission_hours_limit' => 'decimal:2',
        'daily_work_hours'               => 'decimal:2',
        'custom_daily_rate'              => 'decimal:2',
        'custom_hourly_rate'             => 'decimal:2',
    ];

    // Relations
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(EmployeeAllowance::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class)->orderBy('effective_date', 'desc');
    }

    public function currentSalary(): HasOne
    {
        return $this->hasOne(EmployeeSalary::class)->latestOfMany('effective_date');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class)->orderBy('created_at', 'desc');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(EmployeeSkill::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(EmployeeLeave::class)->orderBy('start_date', 'desc');
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(EmployeeLeaveBalance::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(EmployeePermission::class)->orderBy('permission_date', 'desc');
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(EmployeePenalty::class)->orderBy('penalty_date', 'desc');
    }

    public function advances(): HasMany
    {
        return $this->hasMany(EmployeeAdvance::class)->orderBy('advance_date', 'desc');
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    /**
     * مُبقاة للتوافق — نفس hourly_rate بالظبط.
     *
     * كانت بتحسب من الأساسي ÷ 30 وبتتجاهل custom_daily_rate، فموظف له
     * سعر يوم خاص كان أجر ساعته في المسير مختلف عن اللي في ملفه.
     */
    public function getEffectiveHourlyRateAttribute(): float
    {
        return $this->hourly_rate;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }


    // Aliases & Accessors for Form Compatibility
    public function getCodeAttribute(): ?string
    {
        return $this->employee_code;
    }

    public function setCodeAttribute(?string $value): void
    {
        $this->attributes['employee_code'] = $value;
    }

    public function getHiredAtAttribute(): ?string
    {
        return $this->hire_date ? $this->hire_date->format('Y-m-d') : null;
    }

    public function setHiredAtAttribute(?string $value): void
    {
        $this->attributes['hire_date'] = $value;
    }

    public function getProbationEndAtAttribute(): ?string
    {
        return $this->probation_end_date ? $this->probation_end_date->format('Y-m-d') : null;
    }

    public function setProbationEndAtAttribute(?string $value): void
    {
        $this->attributes['probation_end_date'] = $value;
    }

    public function getSalaryAttribute(): ?float
    {
        return $this->basic_salary !== null ? (float) $this->basic_salary : null;
    }

    public function setSalaryAttribute($value): void
    {
        $this->attributes['basic_salary'] = $value;
    }

    public function getBankAccountAttribute(): ?string
    {
        return $this->bank_account_number;
    }

    public function setBankAccountAttribute(?string $value): void
    {
        $this->attributes['bank_account_number'] = $value;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->personal_email;
    }

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['personal_email'] = $value;
    }

    public function getImgAttribute(): ?string
    {
        return $this->avatar;
    }

    public function setImgAttribute(?string $value): void
    {
        $this->attributes['avatar'] = $value;
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

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar ?: demo('img');
    }

    public function getDailyRateAttribute(): float
    {
        if ($this->custom_daily_rate && $this->custom_daily_rate > 0) {
            return (float) $this->custom_daily_rate;
        }
        if ($this->basic_salary && $this->basic_salary > 0) {
            return round((float) $this->basic_salary / 30, 2);
        }
        return 0.0;
    }

    public function getHourlyRateAttribute(): float
    {
        if ($this->custom_hourly_rate && $this->custom_hourly_rate > 0) {
            return (float) $this->custom_hourly_rate;
        }
        $dailyHours = (float) ($this->daily_work_hours ?: 8.0);
        return round($this->daily_rate / ($dailyHours > 0 ? $dailyHours : 8.0), 2);
    }
}
