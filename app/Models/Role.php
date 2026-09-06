<?php

namespace App\Models;

use App\Services\Cache\Keys\RoleCache;
use App\Services\Cache\CacheService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

#[Fillable(['name', 'description', 'roles', 'main'])]
class Role extends Model
{
    protected $casts = [
        'roles' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            static::ClearCache();
        });

        static::deleting(function (Role $role) {
            $role->admins()->update(['role_id' => 2]);
        });

        static::deleted(function () {
            static::ClearCache();
        });
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'role_id');
    }

    public function admins()
    {
        return $this->hasMany(Admin::class, 'role_id');
    }

    protected static function cache(): CacheService
    {
        return app(CacheService::class);
    }

    /**
     * كل الأدوار مع عدد المشرفين — لشاشة إدارة الأدوار.
     *
     * ⚠️ مش متكاشة عن قصد.
     *
     * دي شاشة داخلية في لوحة التحكم، بيفتحها عدد محدود من المشرفين
     * ومرات قليلة. تخزين كل الأدوار وكل مشرفيهم في مفتاح واحد
     * مكانش بيوفّر حاجة تُذكر، وكان بيخلّي أي تعديل على أي دور
     * يلغي الليستة كلها.
     *
     * الصلاحيات نفسها (اللي بتتقرا في كل صفحة) لسه متكاشة —
     * بص على admin_roles() في app/Helpers/AdminRoles.php.
     */
    public static function allWithAdmins()
    {
        return static::select('id', 'name', 'description', 'main')
            ->withCount('admins')
            ->with('admins:name,img,role_id')
            ->paginate(10);
    }

    public static function ClearCache()
    {
        static::cache()->invalidate(RoleCache::class);
    }
}

/*
|--------------------------------------------------------------------------
| طريقة الاستخدام
|--------------------------------------------------------------------------
|
| جلب كل الـ Roles مع عدد الـ Admins (استعلام مباشر، مش متكاش)
|
|   $roles = Role::allWithAdmins();
|
|
| اللي متكاش فعلاً هو صلاحيات كل دور على حدة، وبيتلغي أوتوماتيك
| مع أي saved أو deleted عن طريق ClearCache().
|
| مسح الكاش يدوياً لو احتجت
|
|   Role::ClearCache();
|
| أو من أي مكان تاني:
|
|   app(CacheService::class)->invalidate(RoleCache::class);
|
|
| في Blade
|
|   @foreach(Role::allWithAdmins() as $role)
|       {{ $role->name }} - {{ $role->admins_count }}
|   @endforeach
|
*/