<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فهارس للاستعلامات اللي بتتنفّذ كتير ومالهاش فهرس:
 *
 *  - employee_salaries(employee_id, effective_date) => آخر راتب ساري للموظف
 *  - employee_documents(expiry_date)                 => تنبيهات انتهاء المستندات
 *  - employee_documents(is_verified)                 => المستندات قيد المراجعة
 *  - vendor_subscriptions(status, next_billing_date) => الاشتراكات المستحقة
 *  - employees(hire_date)                            => المعيّنين الجدد في اللوحة
 *  - payrolls(status)                                => المسيرات المعلّقة
 *  - employee_leave_balances(year)                   => أرصدة سنة كاملة
 *  - settings(key)                                   => Settings::get()
 *
 * كل فهرس بيتضاف بس لو مش موجود (hasIndex) عشان الـ migration يتعاد
 * من غير مشاكل على MySQL و SQLite.
 */
return new class extends Migration
{
    /** @var array<string, array<string, array<int, string>>> الجدول => [اسم الفهرس => الأعمدة] */
    private array $indexes = [
        'employee_salaries'       => ['employee_salaries_employee_effective_idx' => ['employee_id', 'effective_date']],
        'employee_documents'      => [
            'employee_documents_expiry_date_idx' => ['expiry_date'],
            'employee_documents_is_verified_idx' => ['is_verified'],
        ],
        'vendor_subscriptions'    => ['vendor_subscriptions_status_billing_idx' => ['status', 'next_billing_date']],
        'employees'               => ['employees_hire_date_idx' => ['hire_date']],
        'payrolls'                => ['payrolls_status_idx' => ['status']],
        'employee_leave_balances' => ['employee_leave_balances_year_idx' => ['year']],
        'settings'                => ['settings_key_idx' => ['key']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $name => $columns) {
                // متضافش لو فيه فهرس بنفس الاسم أو على نفس الأعمدة بالظبط
                if (Schema::hasIndex($table, $name) || Schema::hasIndex($table, $columns)) {
                    continue;
                }

                Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (array_keys($indexes) as $name) {
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }
};
