<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ملخّص يوم واحد لرابط واحد.
 *
 * كل شاشات الإحصائيات بتقرا من هنا مش من link_clicks — العدّ
 * المباشر على جدول النقرات بينهار بعد ملايين الصفوف.
 *
 * الصفوف دي دائمة: links:prune بيحذف النقرات الخام بعد ٩٠ يوم،
 * لكن التجميع بيفضل فالتاريخ مابيضيعش.
 */
class LinkDailyStat extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'link_id',
        'date',
        'clicks',
        'unique_clicks',
        'bot_clicks',
        'by_country',
        'by_device',
        'by_browser',
        'by_referrer',
    ];

    protected $casts = [
        // Y-m-d صراحةً: الكاست المجرّد 'date' بيبني قيمة البحث
        // كـ datetime بينما العمود بيخزّن تاريخ بس، فـ updateOrCreate
        // كان بيفشل يلاقي الصف ويحاول insert مكرر.
        'date'          => 'date:Y-m-d',
        'clicks'        => 'integer',
        'unique_clicks' => 'integer',
        'bot_clicks'    => 'integer',
        'by_country'    => 'array',
        'by_device'     => 'array',
        'by_browser'    => 'array',
        'by_referrer'   => 'array',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
