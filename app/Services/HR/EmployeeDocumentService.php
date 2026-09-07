<?php

namespace App\Services\HR;

use App\Models\EmployeeDocument;
use Illuminate\Database\Eloquent\Builder;

class EmployeeDocumentService
{
    /**
     * استعلام سلة محذوفات المستندات
     */
    public function trashQuery(int $employeeId): Builder
    {
        return EmployeeDocument::onlyTrashed()
            ->where('employee_id', $employeeId)
            ->with('verifier:id,name');
    }

    /**
     * عدد المستندات في سلة المحذوفات
     */
    public function trashCount(int $employeeId): int
    {
        return EmployeeDocument::onlyTrashed()->where('employee_id', $employeeId)->count();
    }

    /**
     * رفع مستند جديد
     */
    public function create(array $data): EmployeeDocument
    {
        $data = $this->fillTitleFromType($data);

        if (!empty($data['is_verified'])) {
            $data['verified_by'] = auth('admin')->id();
            $data['verified_at'] = now();
        }

        return EmployeeDocument::create($data);
    }

    /**
     * تعديل بيانات المستند
     */
    /**
     * الاسم اختياري: لو المستخدم سابه فاضي بنستخدم اسم النوع
     * بدل ما نجبره يكتب نفس المعنى مرتين.
     */
    private function fillTitleFromType(array $data): array
    {
        if (empty($data['title'])) {
            $type = $data['document_type'] ?? 'other';
            $data['title'] = __('document.types.' . $type);
        }

        return $data;
    }

    public function update(EmployeeDocument $document, array $data): EmployeeDocument
    {
        if (!empty($data['is_verified']) && !$document->is_verified) {
            $data['verified_by'] = auth('admin')->id();
            $data['verified_at'] = now();
        }

        $document->update($this->fillTitleFromType($data));

        return $document;
    }

    /**
     * تبديل حالة الاعتماد والتحقق للمستند
     */
    public function toggleVerification(EmployeeDocument $document): bool
    {
        if ($document->is_verified) {
            return $document->update([
                'is_verified' => false,
                'verified_by' => null,
                'verified_at' => null,
            ]);
        }

        return $document->update([
            'is_verified' => true,
            'verified_by' => auth('admin')->id(),
            'verified_at' => now(),
        ]);
    }

    /**
     * نقل المستند لسلة المحذوفات (Soft Delete)
     */
    public function delete(EmployeeDocument $document): bool
    {
        return (bool) $document->delete();
    }

    /**
     * استرجاع المستند من السلة
     */
    public function restore(int $id): bool
    {
        $document = EmployeeDocument::onlyTrashed()->findOrFail($id);

        return (bool) $document->restore();
    }

    /**
     * الحذف النهائي للمستند
     */
    public function forceDelete(int $id): bool
    {
        $document = EmployeeDocument::onlyTrashed()->findOrFail($id);

        return (bool) $document->forceDelete();
    }
}
