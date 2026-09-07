<?php

namespace App\Services\HR;

use App\Models\JobTitle;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class JobTitleService
{
    /**
     * استعلام المسميات الوظيفية مع عدد الشاغلين
     */
    public function query(): Builder
    {
        return JobTitle::withCount('employees');
    }

    /**
     * استعلام سلة محذوفات المسميات الوظيفية
     */
    public function trashQuery(): Builder
    {
        return JobTitle::onlyTrashed()->withCount('employees');
    }

    /**
     * عدد المسميات في سلة المحذوفات
     */
    public function trashCount(): int
    {
        return JobTitle::onlyTrashed()->count();
    }

    /**
     * جلب المسميات المتاحة للتعيين
     */
    public function allActive(): Collection
    {
        return JobTitle::where('is_active', true)->select('id', 'name')->get();
    }

    /**
     * إضافة مسمى وظيفي جديد
     */
    public function create(array $data): JobTitle
    {
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;

        return JobTitle::create($data);
    }

    /**
     * تعديل مسمى وظيفي
     */
    public function update(JobTitle $jobTitle, array $data): JobTitle
    {
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : false;

        $jobTitle->update($data);

        return $jobTitle;
    }

    /**
     * نقل المسمى الوظيفي لسلة المحذوفات (Soft Delete)
     */
    public function delete(JobTitle $jobTitle): bool
    {
        if ($jobTitle->employees()->exists()) {
            throw new Exception(__('job_title.messages.cannot_delete'));
        }

        return (bool) $jobTitle->delete();
    }

    /**
     * استرجاع مسمى وظيفي من السلة
     */
    public function restore(int $id): bool
    {
        $jobTitle = JobTitle::onlyTrashed()->findOrFail($id);

        return (bool) $jobTitle->restore();
    }

    /**
     * الحذف النهائي للمسمى الوظيفي
     */
    public function forceDelete(int $id): bool
    {
        $jobTitle = JobTitle::onlyTrashed()->findOrFail($id);

        if ($jobTitle->employees()->withTrashed()->exists()) {
            throw new Exception(__('job_title.messages.cannot_delete'));
        }

        return (bool) $jobTitle->forceDelete();
    }
}
