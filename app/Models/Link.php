<?php

namespace App\Models;

use App\Models\Concerns\HasDateRangeScopes;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\ScopedToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Link extends Model
{
    use HasFactory, SoftDeletes, ScopedToBranch, LogsActivity, HasDateRangeScopes;

    /** العمود اللي scopes التاريخ بتشتغل عليه افتراضياً. */
    protected string $dateRangeColumn = 'created_at';

    protected $fillable = [
        'short_code',
        'original_url',
        'title',
        'notes',
        'branch_id',
        'created_by',
        'is_active',
        'expires_at',
        'max_clicks',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'expires_at'      => 'datetime',
        'last_clicked_at' => 'datetime',
        'max_clicks'      => 'integer',
    ];

    /**
     * مسح الكاش عند أي تغيير.
     *
     * من غير ده الريدايركت يفضل شغّال على الوجهة القديمة لحد ما الكاش
     * يخلص — يعني ممكن ساعات بعد ما المستخدم عدّل الرابط فعلاً.
     */
    protected static function booted(): void
    {
        static::saved(fn (Link $link) => $link->forgetCache());
        static::deleted(fn (Link $link) => $link->forgetCache());
    }

    public function forgetCache(): void
    {
        Cache::forget('link:' . $this->short_code);

        // لو الكود نفسه اتغيّر، الكاش القديم لازم يتمسح كمان
        $original = $this->getOriginal('short_code');

        if ($original && $original !== $this->short_code) {
            Cache::forget('link:' . $original);
        }
    }

    public function getShortUrlAttribute(): string
    {
        return url('/s/' . $this->short_code);
    }

    /** منتهي بالتاريخ أو باستنفاد عدد النقرات. */
    public function getIsExpiredAttribute(): bool
    {
        return ($this->expires_at && now()->gt($this->expires_at))
            || ($this->max_clicks && $this->clicks_count >= $this->max_clicks);
    }

    /** جاهز للتحويل: مفعّل ومش منتهي. */
    public function getIsUsableAttribute(): bool
    {
        return $this->is_active && ! $this->is_expired;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(LinkClick::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(LinkDailyStat::class);
    }
}
