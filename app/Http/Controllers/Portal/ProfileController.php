<?php

namespace App\Http\Controllers\Portal;

/**
 * بياناتي — للعرض بس. أي تعديل في البيانات الشخصية بيتم عن طريق
 * الموارد البشرية عشان يتسجّل ويتراجع.
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
                'allowances:id,employee_id,name,amount',
                'documents:id,employee_id,title,document_type,expiry_date',
            ]),
        ]);
    }
}
