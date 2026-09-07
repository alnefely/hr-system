<?php

namespace App\Services\HR;

use App\Models\Branch;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BranchService
{
    /**
     * استعلام الفروع مع عدد الموظفين
     */
    public function query(): Builder
    {
        return Branch::withCount('employees');
    }

    /**
     * استعلام سلة محذوفات الفروع
     */
    public function trashQuery(): Builder
    {
        return Branch::onlyTrashed()->withCount('employees');
    }

    /**
     * عدد الفروع في سلة المحذوفات
     */
    public function trashCount(): int
    {
        return Branch::onlyTrashed()->count();
    }

    /**
     * جلب الفروع المفعلة لقوائم الاختيار
     */
    public function allActive(): Collection
    {
        return Branch::where('is_active', true)->select('id', 'name', 'currency', 'code')->get();
    }

    /**
     * إضافة فرع جديد
     */
    public function create(array $data): Branch
    {
        // توليد الرمز تلقائياً إن لم يُدخل
        if (empty($data['code'])) {
            $data['code'] = $this->generateCode($data['country']);
        }

        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;

        return Branch::create($data);
    }

    /**
     * تعديل بيانات الفرع
     */
    public function update(Branch $branch, array $data): Branch
    {
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : false;

        $branch->update($data);

        return $branch;
    }

    /**
     * نقل الفرع لسلة المحذوفات (Soft Delete)
     */
    public function delete(Branch $branch): bool
    {
        if ($branch->employees()->exists()) {
            throw new Exception(__('branch.messages.cannot_delete'));
        }

        return (bool) $branch->delete();
    }

    /**
     * استرجاع الفرع من سلة المحذوفات
     */
    public function restore(int $id): bool
    {
        $branch = Branch::onlyTrashed()->findOrFail($id);

        return (bool) $branch->restore();
    }

    /**
     * الحذف النهائي للفرع من قاعدة البيانات
     */
    public function forceDelete(int $id): bool
    {
        $branch = Branch::onlyTrashed()->findOrFail($id);

        if ($branch->employees()->withTrashed()->exists()) {
            throw new Exception(__('branch.messages.cannot_delete'));
        }

        return (bool) $branch->forceDelete();
    }

    /**
     * توليد رمز الفرع تلقائياً
     */
    protected function generateCode(string $country): string
    {
        $prefix = match (strtolower(trim($country))) {
            'saudi arabia', 'sa', 'السعودية', 'المملكة العربية السعودية' => 'SA',
            'oman', 'om', 'عمان', 'سلطنة عمان', 'سلطنة عُمان'           => 'OM',
            default                                                     => 'EG',
        };

        $count = Branch::withTrashed()->where('country', $country)->count() + 1;

        return sprintf('%s-%02d', $prefix, $count);
    }
}
