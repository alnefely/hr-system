<?php

namespace App\Services\Portal;

use App\Exceptions\InvalidStateException;
use App\Models\Employee;

/**
 * إدارة دخول الموظف لبوابته — من جهة الموارد البشرية.
 *
 * التفعيل لكل موظف لوحده: الموارد البشرية بتحدد كلمة مرور أولية وتسلّمها
 * للموظف، والموظف مجبر يغيّرها أول ما يدخل. الإيقاف لازم يكون بسبب، والسبب
 * بيظهر للموظف في شاشة الدخول.
 *
 * كلمة المرور مابتتسجّلش في سجل الحركات (LogsActivity بيستبعدها)، لكن
 * التفعيل والإيقاف وسببه بيتسجّلوا.
 */
class PortalAccessService
{
    /**
     * تفعيل الدخول (أو إعادة تفعيل حساب موقوف) بكلمة مرور أولية.
     * إعادة التفعيل بتطلب كلمة مرور جديدة دايماً — أوضح وأأمن.
     */
    public function enable(Employee $employee, string $password): void
    {
        if ($employee->status === 'terminated') {
            throw new InvalidStateException(__('portal.admin.cannot_enable_terminated'));
        }

        $employee->forceFill([
            'password'               => $password,
            'portal_enabled'         => true,
            'must_change_password'   => true,
            'portal_disabled_reason' => null,
            'portal_disabled_at'     => null,
        ])->save();
    }

    /** إيقاف الدخول مع سبب يظهر للموظف. */
    public function disable(Employee $employee, string $reason): void
    {
        $employee->forceFill([
            'portal_enabled'         => false,
            'portal_disabled_reason' => $reason,
            'portal_disabled_at'     => now(),
        ])->save();

        // أي جلسة "تذكرني" قديمة ماتشتغلش تاني
        $employee->setRememberToken(null);
        $employee->saveQuietly();
    }

    /** كلمة مرور جديدة من الموارد البشرية (الموظف نسيها) — يغيّرها أول دخول. */
    public function resetPassword(Employee $employee, string $password): void
    {
        $employee->forceFill([
            'password'             => $password,
            'must_change_password' => true,
        ])->save();

        $employee->setRememberToken(null);
        $employee->saveQuietly();
    }
}
