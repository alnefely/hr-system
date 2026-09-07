<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * مصدر العميل — إزاي وصلنا للعميل ده (ترشيح، معرض، الموقع...).
 *
 * بيانات مرجعية مشتركة زي تصنيفات المصروفات وقنوات البيع، فمالهاش
 * عمود فرع ومابتتفلترش بالفرع. العميل نفسه مش مربوط بفرع أصلاً.
 */
class CustomerSource extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /** المصادر المفعّلة بترتيب العرض — ده اللي بيتحط في القوائم. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
