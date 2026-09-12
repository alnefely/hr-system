<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نقرة واحدة على رابط مختصر.
 *
 * الصفوف دي بتتكتب من أمر مجدول (links:flush) مش من مسار التحويل —
 * التحويل بيكتب في Redis بس عشان مايستناش قاعدة البيانات.
 *
 * مافيش عمود branch_id: الفرع بييجي من الرابط نفسه.
 */
class LinkClick extends Model
{
    /** عندنا clicked_at، مش محتاجين created_at/updated_at. */
    public $timestamps = false;

    protected $fillable = [
        'link_id',
        'ip_hash',
        'visitor_hash',
        'device_type',
        'os',
        'browser',
        'country_code',
        'language',
        'referrer_domain',
        'is_bot',
        'bot_name',
        'clicked_at',
    ];

    protected $casts = [
        'is_bot'     => 'boolean',
        'clicked_at' => 'datetime',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    /** الزيارات البشرية بس — دي اللي بتتحسب في الإحصائيات. */
    public function scopeHumans(Builder $query): Builder
    {
        return $query->where('is_bot', false);
    }

    public function scopeBots(Builder $query): Builder
    {
        return $query->where('is_bot', true);
    }
}
