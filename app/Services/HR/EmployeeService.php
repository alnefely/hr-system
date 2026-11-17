<?php

namespace App\Services\HR;

use App\Models\Branch;
use App\Models\Employee;
use App\Support\CodeSequence;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeDocument;
use App\Models\EmployeeSalary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    /**
     * استعلام الموظفين للقوائم والـ DataTables
     */
    public function query(): Builder
    {
        // select صريح: yajra بيعمل join تلقائي للترتيب بأعمدة
        // العلاقات، و select * ساعتها بيخلي أعمدة الجدول المنضم
        // تدهس أعمدة الموظف (id بالذات).
        return Employee::visibleTo()->select('employees.*')->with([
            'branch:id,name,code,currency',
            'jobTitle:id,name',
            'manager:id,name',
            'currentSalary',
            'admin:id,name,email,role_id',
        ]);
    }

    /**
     * استعلام سلة محذوفات الموظفين
     */
    public function trashQuery(): Builder
    {
        return Employee::onlyTrashed()->visibleTo()->with([
            'branch:id,name,code',
            'jobTitle:id,name',
        ]);
    }

    /**
     * عدد الموظفين في سلة المحذوفات
     */
    public function trashCount(): int
    {
        return Employee::onlyTrashed()->count();
    }

    /**
     * قائمة المديرين المتاحين للاختيار
     */
    public function managersList(?int $excludeId = null)
    {
        return Employee::active()
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->select('id', 'name', 'employee_code')
            ->get();
    }

    /**
     * توليد الكود الوظيفي التالي للعرض في شاشة الإضافة
     */
    public function getNextEmployeeCode(?int $branchId = null): string
    {
        $prefix = 'EMP';
        if ($branchId) {
            $branch = Branch::find($branchId);
            if ($branch && $branch->code) {
                $prefix = $branch->code;
            }
        }
        return CodeSequence::next(Employee::class, 'employee_code', $prefix . '-');
    }

    /**
     * إضافة موظف جديد ودخول البوابة والبدلات والمستندات بـ Transaction واحد
     */
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $branch = Branch::findOrFail($data['branch_id']);

            // تحديد الرقم الوظيفي
            $code = $data['code'] ?? $data['employee_code'] ?? null;
            if (empty($code)) {
                $code = $this->generateEmployeeCode($branch);
            }
            $data['employee_code'] = $code;

            // توحيد مسميات الحقول
            $data['hire_date'] = $data['hired_at'] ?? $data['hire_date'] ?? now()->toDateString();
            $data['probation_end_date'] = $data['probation_end_at'] ?? $data['probation_end_date'] ?? null;
            $data['contract_type'] = $data['employment_type'] ?? $data['contract_type'] ?? 'full_time';
            $data['personal_email'] = $data['email'] ?? $data['personal_email'] ?? null;
            $data['avatar'] = $data['img'] ?? $data['avatar'] ?? null;
            $data['bank_account_number'] = $data['bank_account'] ?? $data['bank_account_number'] ?? null;
            $data['basic_salary'] = $data['salary'] ?? $data['basic_salary'] ?? 0;

            // استخراج البيانات الفرعية قبل إنشاء الموظف
            $allowancesData = $data['allowances'] ?? [];
            $documentsData = $data['documents'] ?? [];
            $portalPassword = $this->pullPortalPassword($data);

            unset(
                $data['code'], $data['hired_at'], $data['probation_end_at'],
                $data['employment_type'], $data['email'], $data['img'],
                $data['bank_account'], $data['salary'], $data['allowances'],
                $data['documents']
            );

            // إنشاء سجل الموظف
            $employee = Employee::create($data);

            // 1. دخول بوابة الموظف (لو اتكتبت كلمة مرور)
            $this->applyPortalPassword($employee, $portalPassword);

            // 2. حفظ البدلات الشهرية الثابتة
            $totalAllowances = 0;
            if (is_array($allowancesData)) {
                foreach ($allowancesData as $allowance) {
                    if (!empty($allowance['name']) && isset($allowance['amount']) && is_numeric($allowance['amount'])) {
                        $amount = (float) $allowance['amount'];
                        $employee->allowances()->create([
                            'name'   => trim($allowance['name']),
                            'amount' => $amount,
                        ]);
                        $totalAllowances += $amount;
                    }
                }
            }

            // 3. حفظ المستندات والوثائق الأولية
            if (is_array($documentsData)) {
                foreach ($documentsData as $doc) {
                    if (!empty($doc['file_path'])) {
                        $documentType = $doc['document_type'] ?? 'other';

                        $employee->documents()->create([
                            // النوع بيجي من القائمة زي شاشة البروفايل بالظبط.
                            // كان ثابتاً بقيمة مالهاش ترجمة، فكانت بتظهر
                            // كمفتاح خام في جدول مستندات الموظف.
                            'document_type' => $documentType,
                            // الاسم اختياري: لو سابه فاضي بنستخدم اسم النوع
                            // بدل ما نجبره يكتب نفس المعنى مرتين.
                            'title'         => !empty($doc['title'])
                                ? trim($doc['title'])
                                : __('document.types.' . $documentType),
                            'file_path'     => trim($doc['file_path']),
                            'issue_date'    => $doc['issue_date'] ?? null ?: null,
                            'expiry_date'   => $doc['expiry_date'] ?? null ?: null,
                            'is_verified'   => !empty($doc['is_verified']),
                            'verified_by'   => !empty($doc['is_verified']) ? auth('admin')->id() : null,
                            'verified_at'   => !empty($doc['is_verified']) ? now() : null,
                        ]);
                    }
                }
            }

            // 4. تسجيل الراتب في جدول مسير الرواتب لربطه بالنظام المالي
            if ($employee->basic_salary > 0 || $totalAllowances > 0) {
                EmployeeSalary::create([
                    'employee_id'    => $employee->id,
                    'basic_salary'   => (float) $employee->basic_salary,
                    'allowances'     => (float) $totalAllowances,
                    'currency'       => $branch->currency ?? 'EGP',
                    'effective_date' => $employee->hire_date,
                    'change_reason'  => 'hiring',
                    'approved_by'    => auth('admin')->id(),
                    'notes'          => __('salary.reasons.hiring'),
                ]);
            }

            return $employee;
        });
    }

    /**
     * تحديث بيانات الموظف والبدلات ودخول البوابة
     */
    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            // توحيد مسميات الحقول
            if (isset($data['code'])) {
                $data['employee_code'] = $data['code'];
            }
            if (isset($data['hired_at'])) {
                $data['hire_date'] = $data['hired_at'];
            }
            if (isset($data['probation_end_at'])) {
                $data['probation_end_date'] = $data['probation_end_at'];
            }
            if (isset($data['employment_type'])) {
                $data['contract_type'] = $data['employment_type'];
            }
            if (isset($data['email'])) {
                $data['personal_email'] = $data['email'];
            }
            if (isset($data['img'])) {
                $data['avatar'] = $data['img'];
            }
            if (isset($data['bank_account'])) {
                $data['bank_account_number'] = $data['bank_account'];
            }
            if (isset($data['salary'])) {
                $data['basic_salary'] = $data['salary'];
            }

            // حماية البيانات الحساسة إذا لم يملك المستخدم الصلاحية
            if (!admin_can('employee_bank_view')) {
                unset($data['bank_name'], $data['bank_account_number'], $data['iban'], $data['insurance_number']);
            }
            if (!admin_can('employee_salary_view')) {
                unset($data['basic_salary'], $data['salary'], $data['custom_hourly_rate']);
            }

            // استخراج البدلات والمستندات
            $allowancesData = $data['allowances'] ?? null;
            $documentsData = $data['documents'] ?? null;
            $portalPassword = $this->pullPortalPassword($data);

            unset(
                $data['code'], $data['hired_at'], $data['probation_end_at'],
                $data['employment_type'], $data['email'], $data['img'],
                $data['bank_account'], $data['salary'], $data['allowances'],
                $data['documents']
            );

            // تحديث بيانات الموظف
            $employee->update($data);

            // 1. دخول بوابة الموظف: كلمة مرور جديدة = تفعيل / إعادة تعيين
            $this->applyPortalPassword($employee, $portalPassword);

            // 2. تحديث البدلات الشهرية الثابتة إذا أُرسلت
            if ($allowancesData !== null && is_array($allowancesData)) {
                $employee->allowances()->delete();
                foreach ($allowancesData as $allowance) {
                    if (!empty($allowance['name']) && isset($allowance['amount']) && is_numeric($allowance['amount'])) {
                        $employee->allowances()->create([
                            'name'   => trim($allowance['name']),
                            'amount' => (float) $allowance['amount'],
                        ]);
                    }
                }
            }

            // 3. مزامنة المستندات
            //
            // شاشة التعديل بتعرض المستندات المحفوظة وبتبعتها تاني مع
            // الـ id بتاعها. من غير مطابقة بالـ id كان كل حفظ بيعمل
            // نسخة جديدة وتتكرر المستندات.
            if ($documentsData !== null && is_array($documentsData)) {
                $keptIds = [];

                foreach ($documentsData as $doc) {
                    if (empty($doc['file_path'])) {
                        continue;
                    }

                    $documentType = $doc['document_type'] ?? 'other';
                    $verified     = !empty($doc['is_verified']);

                    $attributes = [
                        // النوع بيجي من القائمة زي شاشة البروفايل بالظبط.
                        'document_type' => $documentType,
                        // الاسم اختياري: لو سابه فاضي بنستخدم اسم النوع
                        // بدل ما نجبره يكتب نفس المعنى مرتين.
                        'title'         => !empty($doc['title'])
                            ? trim($doc['title'])
                            : __('document.types.' . $documentType),
                        'file_path'     => trim($doc['file_path']),
                        'issue_date'    => $doc['issue_date'] ?? null ?: null,
                        'expiry_date'   => $doc['expiry_date'] ?? null ?: null,
                        'is_verified'   => $verified,
                    ];

                    $existing = !empty($doc['id'])
                        ? $employee->documents()->whereKey($doc['id'])->first()
                        : null;

                    if ($existing) {
                        // وقت الاعتماد بيتسجّل مرة واحدة عند أول اعتماد
                        if ($verified && ! $existing->is_verified) {
                            $attributes['verified_by'] = auth('admin')->id();
                            $attributes['verified_at'] = now();
                        } elseif (! $verified) {
                            $attributes['verified_by'] = null;
                            $attributes['verified_at'] = null;
                        }

                        $existing->update($attributes);
                        $keptIds[] = $existing->id;

                        continue;
                    }

                    $attributes['verified_by'] = $verified ? auth('admin')->id() : null;
                    $attributes['verified_at'] = $verified ? now() : null;

                    $keptIds[] = $employee->documents()->create($attributes)->id;
                }

                // اللي المستخدم شاله من الفورم يتحذف
                $employee->documents()->whereNotIn('id', $keptIds ?: [0])->delete();
            }

            return $employee;
        });
    }

    /**
     * نقل ملف الموظف لسلة المحذوفات (Soft Delete)
     */
    public function delete(Employee $employee): bool
    {
        return (bool) $employee->delete();
    }

    /**
     * استرجاع ملف الموظف من سلة المحذوفات
     */
    public function restore(int $id): bool
    {
        $employee = Employee::onlyTrashed()->findOrFail($id);

        return (bool) $employee->restore();
    }

    /**
     * الحذف النهائي لملف الموظف وكافة سجلاته الفرعية
     */
    public function forceDelete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $employee = Employee::onlyTrashed()->findOrFail($id);

            // حذف الملحقات نهائياً
            $employee->allowances()->withTrashed()->forceDelete();
            $employee->salaries()->withTrashed()->forceDelete();
            $employee->documents()->withTrashed()->forceDelete();
            $employee->skills()->withTrashed()->forceDelete();

            return (bool) $employee->forceDelete();
        });
    }

    /**
     * تجهيز بيانات الملف الشخصي الشامل للموظف
     */
    public function getProfileData(Employee $employee): Employee
    {
        return $employee->load([
            'branch',
            'jobTitle',
            'manager',
            'subordinates',
            'allowances',
            'salaries' => fn ($q) => $q->with('approver:id,name'),
            'documents' => fn ($q) => $q->with('verifier:id,name'),
            'skills',
            'admin:id,name,email,role_id',
        ]);
    }

    /**
     * يسحب كلمة مرور البوابة من البيانات (ومايسيبهاش تتحفظ كعمود عادي).
     *
     * بتتطبّق بس لو اللي بيحفظ عنده صلاحية إدارة البوابة — الحقل ممكن
     * يتبعت يدوياً حتى لو الجزء مخفي من الفورم.
     */
    protected function pullPortalPassword(array &$data): ?string
    {
        $password = $data['portal_password'] ?? null;

        unset($data['portal_password'], $data['portal_password_confirmation'], $data['password']);

        return $password && admin_can('employee_portal_manage') ? $password : null;
    }

    /**
     * تفعيل دخول البوابة بكلمة المرور (أو إعادة تعيينها لو مفعّل).
     * الموظف المنتهي خدمته مابيتفعّلش — الحقول أصلاً مخفية له في الفورم.
     */
    protected function applyPortalPassword(Employee $employee, ?string $password): void
    {
        if (! $password || $employee->status === 'terminated') {
            return;
        }

        app(\App\Services\Portal\PortalAccessService::class)->enable($employee, $password);
    }

    /**
     * توليد الكود الوظيفي التلقائي
     */
    protected function generateEmployeeCode(Branch $branch): string
    {
        $branchCode = $branch->code ?: 'EMP';
        return CodeSequence::next(Employee::class, 'employee_code', $branchCode . '-');
    }
}
