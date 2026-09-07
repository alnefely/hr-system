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

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'img',
        'main',
        'employee_id',
    ];

    /**
     * إرسال رابط إعادة تعيين كلمة المرور.
     *
     * بنستخدم قالب البريد بتاع المشروع بدل نوتيفيكيشن Laravel الافتراضي
     * عشان الرسالة تفضل بنفس الهوية البصرية وبالعربي.
     */
    public function sendPasswordResetNotification($token): void
    {
        $expireMinutes = (int) config('auth.passwords.admins.expire', 60);

        sendEmail(
            to: $this->email,
            subject: __('auth.email.reset_link.title'),
            markdownView: 'emails.admin.reset-link',
            data: [
                'name'            => $this->name,
                'reset_url'       => route('admin.password.reset', [
                    'token' => $token,
                    'email' => $this->email,
                ]),
                'expire_minutes'  => $expireMinutes,
            ],
        );
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
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
