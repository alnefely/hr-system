<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmployeeDocumentRequest;
use App\Models\EmployeeDocument;
use App\Services\HR\EmployeeDocumentService;
use Exception;
use Illuminate\Http\Request;

class EmployeeDocumentController extends Controller
{
    public function __construct(
        protected EmployeeDocumentService $documentService
    ) {}

    /**
     * حفظ مستند جديد
     */
    public function store(EmployeeDocumentRequest $request)
    {
        $doc = $this->documentService->create($request->validated());

        return redirect()->to(route('admin.employees.show', $doc->employee_id) . '#tab-docs')
            ->with('success', __('document.messages.created'));
    }

    /**
     * تحديث بيانات المستند
     */
    public function update(EmployeeDocumentRequest $request, EmployeeDocument $document)
    {
        $this->documentService->update($document, $request->validated());

        return redirect()->to(route('admin.employees.show', $document->employee_id) . '#tab-docs')
            ->with('success', __('document.messages.updated'));
    }

    /**
     * اعتماد أو إلغاء اعتماد المستند
     */
    public function toggleVerification(EmployeeDocument $document)
    {
        $this->documentService->toggleVerification($document);

        $msg = $document->is_verified 
            ? 'تم التحقق واعتماد المستند بنجاح' 
            : 'تم إلغاء اعتماد المستند وإعادته لقيد المراجعة';

        return redirect()->to(route('admin.employees.show', $document->employee_id) . '#tab-docs')
            ->with('success', $msg);
    }

    /**
     * نقل المستند لسلة المحذوفات
     */
    public function destroy(EmployeeDocument $document)
    {
        try {
            $employeeId = $document->employee_id;
            $this->documentService->delete($document);
            return redirect()->to(route('admin.employees.show', $employeeId) . '#tab-docs')
                ->with('success', __('document.messages.deleted'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * استرجاع المستند من السلة
     */
    public function restore(int $id)
    {
        $this->documentService->restore($id);

        return redirect()->back()->with('success', __('document.messages.restored'));
    }

    /**
     * الحذف النهائي للمستند
     */
    public function forceDelete(int $id)
    {
        try {
            $this->documentService->forceDelete($id);
            return redirect()->back()->with('success', __('document.messages.force_deleted'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
