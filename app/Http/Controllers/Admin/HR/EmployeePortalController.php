<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Concerns\RespondsToStateActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Portal\DisablePortalRequest;
use App\Http\Requests\Admin\Portal\PortalPasswordRequest;
use App\Models\Employee;
use App\Services\Portal\PortalAccessService;

/**
 * إدارة دخول الموظف لبوابته من ملفه في لوحة الإدارة.
 *
 * Route Model Binding بيطبّق عزل الفروع: مشرف الفرع يدير موظفين فرعه بس.
 */
class EmployeePortalController extends Controller
{
    use RespondsToStateActions;

    public function __construct(
        private PortalAccessService $access,
    ) {}

    public function enable(PortalPasswordRequest $request, Employee $employee)
    {
        return $this->attempt(
            fn () => $this->access->enable($employee, $request->validated('password')),
            'portal.admin.enabled_msg'
        );
    }

    public function disable(DisablePortalRequest $request, Employee $employee)
    {
        return $this->attempt(
            fn () => $this->access->disable($employee, $request->reason()),
            'portal.admin.disabled_msg'
        );
    }

    public function resetPassword(PortalPasswordRequest $request, Employee $employee)
    {
        return $this->attempt(
            fn () => $this->access->resetPassword($employee, $request->validated('password')),
            'portal.admin.reset_msg'
        );
    }
}
