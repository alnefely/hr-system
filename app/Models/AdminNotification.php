<?php

namespace App\Models;

use App\Enums\NotificationType;
use App\Models\Concerns\HasDateRangeScopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * إشعار موجّه لمشرف.
 *
 * مافيش عليه ScopedToBranch لأن الفلترة هنا بتتم بـ admin_id مباشرة —
 * كل مشرف بيشوف إشعاراته هو، وده أدق من العزل بالفرع.
 */
class AdminNotification extends Model
{
    use HasFactory, HasDateRangeScopes;

    protected string $dateRangeColumn = 'created_at';

    protected $fillable = [
        'admin_id',
        'branch_id',
        'type',
        'title',
        'body',
        'url',
        'icon',
        'level',
        'data',
        'causer_id',
        'causer_name',
        'read_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'causer_id');
    }

    // ========================================================
    //  Scopes
    // ========================================================

    public function scopeForAdmin(Builder $query, int $adminId): Builder
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('type', $type) : $query;
    }

    // ========================================================
    //  Helpers
    // ========================================================

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): bool
    {
        if ($this->isRead()) {
            return false;
        }

        return $this->forceFill(['read_at' => now()])->save();
    }

    public function typeEnum(): ?NotificationType
    {
        return NotificationType::fromValue($this->type);
    }

    /** الشكل اللي بيتبعت للواجهة (جرس الإشعارات). */
    public function toPayload(): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'title'      => $this->title,
            'body'       => $this->body,
            'url'        => $this->url,
            'icon'       => $this->icon ?: $this->typeEnum()?->icon(),
            'level'      => $this->level,
            'causer'     => $this->causer_name,
            'created_at' => $this->created_at?->toIso8601String(),
            'human_time' => $this->created_at?->diffForHumans(),
            'is_read'    => $this->isRead(),
        ];
    }
}
