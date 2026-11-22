<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\HasDateRangeScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * سجل حركة واحدة في النظام.
 *
 * السجلات دي للقراءة بس — بتتكتب تلقائياً من LogsActivity ومحدش
 * المفروض يعدّلها من الواجهة، عشان السجل يفضل موثوق.
 */
class ActivityLog extends Model
{
    use HasFactory, HasDateRangeScopes;

    /** العمود اللي scopes التاريخ بتشتغل عليه افتراضياً. */
    protected string $dateRangeColumn = 'created_at';

    protected $fillable = [
        'event',
        'subject_type',
        'subject_id',
        'subject_label',
        'causer_id',
        'causer_name',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'causer_id');
    }

    // ========================================================
    //  Scopes
    // ========================================================

    public function scopeForSubjectType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('subject_type', $type) : $query;
    }

    public function scopeForSubject(Builder $query, string $type, int|string $id): Builder
    {
        return $query->where('subject_type', $type)->where('subject_id', $id);
    }

    public function scopeForEvent(Builder $query, ?string $event): Builder
    {
        return $event ? $query->where('event', $event) : $query;
    }

    public function scopeForCauser(Builder $query, $causerId): Builder
    {
        return $causerId ? $query->where('causer_id', $causerId) : $query;
    }

    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): Builder
    {
        // مقارنة نطاق بدل whereDate() عشان فهرس created_at يشتغل:
        // من أول يوم البداية لحد ما قبل أول اليوم اللي بعد النهاية.
        $from = static::parseDay($from);
        $to   = static::parseDay($to);

        return $query
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from->toDateTimeString()))
            ->when($to, fn ($q) => $q->where('created_at', '<', $to->addDay()->toDateTimeString()));
    }

    /** تاريخ من الفلتر — القيمة الغلط بتتجاهل بدل ما توقع الصفحة. */
    protected static function parseDay(?string $value): ?\Carbon\CarbonImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return \Carbon\CarbonImmutable::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    // ========================================================
    //  Accessors
    // ========================================================

    /** اسم الموديول بالعربي/الإنجليزي حسب لغة الواجهة. */
    public function getSubjectLabelNameAttribute(): string
    {
        return activity_subject_label($this->subject_type);
    }

    /**
     * الحقول اللي اتغيّرت، جاهزة للعرض جنب بعض.
     *
     * @return array<int, array{field: string, label: string, old: mixed, new: mixed}>
     */
    /** حقول الرواتب اللي بتتخفي عن اللي مالوش employee_salary_view. */
    public const SALARY_FIELDS = ['basic_salary', 'salary', 'amount', 'custom_daily_rate', 'custom_hourly_rate'];

    /** موديولات الراتب اللي قيمها بتتخفي. */
    private const SALARY_SUBJECTS = [
        \App\Models\Employee::class,
        \App\Models\EmployeeSalary::class,
        \App\Models\EmployeeAllowance::class,
    ];

    /**
     * السجل كان بيعرض الراتب القديم والجديد لأي حد عنده "عرض السجل"،
     * حتى لو مالوش صلاحية يشوف الرواتب في شاشة الموظفين نفسها.
     */
    private function salaryHiddenFrom(): bool
    {
        return in_array($this->subject_type, self::SALARY_SUBJECTS, true)
            && admin() !== null
            && ! admin()->main
            && ! admin_can('employee_salary_view');
    }

    public function getChangesListAttribute(): array
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        $fields = array_unique(array_merge(array_keys($old), array_keys($new)));
        sort($fields);

        $rows = [];
        $mask = $this->salaryHiddenFrom();

        foreach ($fields as $field) {
            $hidden = $mask && in_array($field, self::SALARY_FIELDS, true);

            $rows[] = [
                'field' => $field,
                'label' => activity_field_label($this->subject_type, $field),
                'old'   => $hidden ? '***' : ($old[$field] ?? null),
                'new'   => $hidden ? '***' : ($new[$field] ?? null),
            ];
        }

        return $rows;
    }
}
