<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\FlushesDashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, FlushesDashboardCache;

    protected $fillable = [
        'name',
        'code',
        'country',
        'city',
        'address',
        'phone',
        'email',
        'currency',
        'timezone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * الفروع اللي المشرف الحالي يقدر يسجّل عليها — لقوائم الاختيار.
     *
     * مشرف الفرع بيشوف فرعه بس، والمشرف الرئيسي بيشوف الكل. القائمة
     * بس للراحة: الحماية الفعلية في قاعدة AccessibleBranch.
     */
    public function scopeAccessible(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        $branchId = \App\Support\BranchScopeResolver::branchId();

        return $query->when($branchId !== null, fn ($q) => $q->whereKey($branchId));
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
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
