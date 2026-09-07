<?php

namespace App\Models;

use App\Models\Concerns\ScopedToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSkill extends Model
{
    use HasFactory, SoftDeletes, ScopedToBranch;

    /** العزل بيتم عبر فرع الموظف مش عمود على الجدول نفسه. */
    public $branchScopeVia = 'employee';

    protected $fillable = [
        'employee_id',
        'skill_name',
        'proficiency_level',
        'years_of_experience',
        'is_core',
        'evaluation_source',
    ];

    protected $casts = [
        'years_of_experience' => 'integer',
        'is_core'             => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
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
