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
        return $query
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));
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
    public function getChangesListAttribute(): array
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        $fields = array_unique(array_merge(array_keys($old), array_keys($new)));
        sort($fields);

        $rows = [];

        foreach ($fields as $field) {
            $rows[] = [
                'field' => $field,
                'label' => activity_field_label($this->subject_type, $field),
                'old'   => $old[$field] ?? null,
                'new'   => $new[$field] ?? null,
            ];
        }

        return $rows;
    }
}
