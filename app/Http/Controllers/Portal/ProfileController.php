<?php

namespace App\Http\Controllers\Portal;

/**
 * بياناتي — للعرض بس. أي تعديل في البيانات الشخصية بيتم عن طريق
 * الموارد البشرية عشان يتسجّل ويتراجع.
 *
 * الراتب والمستندات ليهم صفحات مستقلة (SalaryController / DocumentController).
 */
class ProfileController extends PortalController
{
    public function show()
    {
        return view('portal.profile', [
            'employee' => $this->employee()->loadMissing([
                'branch:id,name,currency',
                'jobTitle:id,name',
                'manager:id,name',
            ]),
        ]);
    }
}
