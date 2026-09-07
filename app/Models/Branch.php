<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

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
