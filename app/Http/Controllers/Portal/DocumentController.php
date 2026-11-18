<?php

namespace App\Http\Controllers\Portal;

/**
 * مستنداتي — للعرض بس.
 *
 * المستندات بترفعها الموارد البشرية في ملف الموظف. الموظف بيشوفها ويفتح
 * ملفها، وبيعرف المنتهي والقريب من الانتهاء عشان يجدده.
 */
class DocumentController extends PortalController
{
    /** المستند القريب من الانتهاء خلال كام يوم بيتعلّم بالأصفر. */
    public const EXPIRY_WARNING_DAYS = 30;

    public function index()
    {
        $documents = $this->employee()->documents()->reorder()
            ->orderByRaw('expiry_date IS NULL')   // اللي ليها تاريخ انتهاء الأول
            ->orderBy('expiry_date')
            ->orderByDesc('id')
            ->get(['id', 'title', 'document_type', 'file_path', 'issue_date', 'expiry_date', 'is_verified']);

        return view('portal.documents.index', compact('documents'));
    }
}
