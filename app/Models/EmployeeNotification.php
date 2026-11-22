<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** إشعار في بوابة الموظف. */
class EmployeeNotification extends Model
{
    protected $fillable = [
        'employee_id', 'type', 'title', 'body', 'url', 'icon', 'level',
        'data', 'causer_type', 'causer_id', 'causer_name', 'read_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    /** رابط نسبي — نفس سبب AdminNotification::url (الدومين بيتغيّر بين البيئات). */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => AdminNotification::relativeUrl($value),
            set: fn (?string $value) => AdminNotification::relativeUrl($value),
        );
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    /** شكل الإشعار للجرس (JSON) — نفس شكل إشعارات المشرفين. */
    public function toPayload(): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'title'      => $this->title,
            'body'       => $this->body,
            'url'        => $this->url,
            'icon'       => $this->icon ?: 'fa-solid fa-bell',
            'level'      => $this->level,
            'causer'     => $this->causer_name,
            'created_at' => $this->created_at?->toIso8601String(),
            'human_time' => $this->created_at?->diffForHumans(),
            'is_read'    => $this->isRead(),
        ];
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
