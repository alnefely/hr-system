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

    /** المهام اللي المشرف أنشأها من لوحة التحكم. */
    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Task::class, 'creator');
    }

    protected static function booted(): void
    {
        // مش كل حفظ للمشرف (زي remember_token وقت اللوجين) يستاهل نمسح
        // كاش الأدوار — بس لما الدور نفسه يتغيّر.
        static::saved(function (Admin $admin) {
            if ($admin->isDirty('role_id')) {
                Role::ClearCache();
            }
        });

        static::deleted(function () {
            Role::ClearCache();
        });
    }


    // $admin->role_name
    // لو العلاقة متحمّلة نستخدمها، غير كده من كاش الأدوار (من غير lazy load)
    public function getRoleNameAttribute()
    {
        if ($this->relationLoaded('role')) {
            return $this->role?->name ?? '';
        }

        return app(\App\Services\Role\RoleService::class)->roleName($this->role_id);
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
