<?php

namespace App\Console\Commands;

use App\Models\EmployeeSalary;
use App\Services\HR\EmployeeSalaryService;
use Illuminate\Console\Command;

/**
 * تطبيق العلاوات اللي ميعادها جه.
 *
 * employees.basic_salary بيتزامن وقت حفظ سجل الراتب بس، فالعلاوة
 * المسجّلة بتاريخ مستقبلي ماكانتش بتتطبق أبداً لحد ما حد يعدّل السجل.
 * الأمر ده بيتشغّل يومياً ويزامن الموظفين اللي عندهم سجل بيبدأ النهارده
 * (أو في آخر --days يوم، كشبكة أمان لو السيرفر كان واقف).
 *
 *   php artisan salaries:sync
 *   php artisan salaries:sync --days=30
 */
class SyncEffectiveSalariesCommand extends Command
{
    protected $signature = 'salaries:sync {--days=7 : عدد الأيام اللي فاتت اللي يتراجع فيها}';

    protected $description = 'مزامنة الراتب الأساسي للموظفين مع سجلات الرواتب اللي بدأ سريانها';

    public function handle(EmployeeSalaryService $service): int
    {
        $days = max(0, (int) $this->option('days'));

        $employeeIds = EmployeeSalary::withoutGlobalScope('branch')
            ->where('effective_date', '>=', now()->subDays($days)->toDateString())
            ->where('effective_date', '<=', now()->toDateString())
            ->distinct()
            ->pluck('employee_id');

        foreach ($employeeIds as $employeeId) {
            $service->syncEmployeeBasicSalary((int) $employeeId);
        }

        $this->info("تمت مزامنة {$employeeIds->count()} موظف.");

        return self::SUCCESS;
    }
}
