<?php

namespace App\Helpers;

use App\Support\BranchScopeResolver;

/**
 * اسم المجلد الخاص في مدير الملفات.
 *
 *  - المدير العام: الجذر (بيشوف كل حاجة).
 *  - مشرف الفرع: مجلد الفرع — كل مشرفي الفرع بيشتغلوا على نفس الملفات،
 *    ومايشوفوش ملفات فرع تاني.
 *  - مشرف مش مربوط بفرع: مجلده الشخصي.
 */
class LfmConfigHandler
{
    public function userField()
    {
        if (! auth('admin')->check()) {
            return null;
        }

        $admin = auth('admin')->user();

        if ($admin->main) {
            return '';
        }

        $branchId = BranchScopeResolver::branchId();

        return $branchId !== null ? 'branches/' . $branchId : 'admins/' . $admin->id;
    }
}
