<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Employee;

/**
 * أساس كنترولرات بوابة الموظف.
 *
 * القاعدة الوحيدة في البوابة: الموظف يشوف ويعدّل بياناته هو بس. فكل
 * استعلام بيبدأ من علاقات الموظف الحالي ($this->employee()->leaves())
 * مش من الموديل مباشرة — مستحيل يوصل لسجل زميله حتى لو غيّر الرقم في الرابط.
 */
abstract class PortalController extends Controller
{
    protected function employee(): Employee
    {
        return auth('employee')->user();
    }
}
