<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Hidden(['password', 'remember_token'])]
class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    protected static function booted(): void
    {
        static::saved(function () {
            Role::ClearCache();
        });

        static::deleted(function () {
            Role::ClearCache();
        });
    }


    // $admin->role_name
    public function getRoleNameAttribute()
    {
        return $this->role?->name ?? '';
    }

    public function getCreatedAtAttribute($date)
    {
        return FormatDate($date);
    }

    public function getUpdatedAtAttribute($date)
    {
        return FormatDate($date);
    }

}
