<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 50)->unique();
            $table->string('name', 150);
            $table->string('national_id', 50)->nullable()->unique();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->string('marital_status', 50)->nullable();
            $table->string('nationality', 100)->nullable();
            // اسم الدخول لبوابة الموظف — بيتخزّن دولي موحّد من مكوّن الهاتف (+2010...)
            $table->string('phone', 50)->unique();
            $table->string('phone_country', 10)->default('EG');
            $table->string('alt_phone', 50)->nullable();
            $table->string('alt_phone_country', 10)->nullable();
            $table->string('personal_email', 100)->nullable();
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->string('emergency_contact_phone_country', 10)->nullable();
            $table->text('address')->nullable();
            $table->string('avatar', 255)->nullable();

            // الوظيفة والفرع
            $table->foreignId('branch_id')->constrained('branches')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('job_title_id')->constrained('job_titles')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('manager_id')->nullable()->constrained('employees')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('admin_id')->nullable()->constrained('admins')->onUpdate('cascade')->onDelete('set null');

            // تفاصيل التعاقد
            $table->date('hire_date');
            $table->date('probation_end_date')->nullable();
            $table->enum('contract_type', ['full_time', 'part_time', 'contractor', 'freelancer'])->default('full_time');
            $table->enum('status', ['active', 'on_leave', 'terminated'])->default('active');
            $table->date('termination_date')->nullable();
            $table->string('termination_reason', 255)->nullable();
            $table->unsignedInteger('weekly_hours')->default(40);

            // الراتب وإعدادات الاستحقاقات
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('annual_leave_entitlement', 5, 2)->default(21);
            $table->decimal('monthly_permission_hours_limit', 5, 2)->default(4);
            $table->decimal('daily_work_hours', 5, 2)->default(8);

            // أسعار مخصصة تتجاوز الحساب الافتراضي (الراتب ÷ 30)
            $table->decimal('custom_daily_rate', 10, 2)->nullable();
            $table->decimal('custom_hourly_rate', 10, 2)->nullable();

            // البيانات البنكية والتأمينية (صلاحيات خاصة)
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->string('iban', 100)->nullable();
            $table->string('insurance_number', 100)->nullable();

            // ملاحظات داخلية
            $table->text('notes')->nullable();

            // بوابة الموظف: الدخول برقم الهاتف + كلمة المرور.
            // مقفول افتراضياً لحد ما الموارد البشرية تفعّل كل موظف لوحده.
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->boolean('portal_enabled')->default(false);
            $table->boolean('must_change_password')->default(true);
            $table->text('portal_disabled_reason')->nullable();
            $table->timestamp('portal_disabled_at')->nullable();
            $table->timestamp('last_login_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // فهرس الفلترة الأكثر استخداماً في القوائم والتقارير
            $table->index(['status', 'branch_id'], 'employees_status_branch_idx');
            $table->index('hire_date', 'employees_hire_date_idx');
        });

        // القيد الدائري: admins.employee_id -> employees.id
        // العمود اتعمل في create_admins_table، والقيد هنا بعد ما الجدولين بقوا موجودين.
        Schema::table('admins', function (Blueprint $table) {
            $table->foreign('employee_id')
                ->references('id')->on('employees')
                ->onUpdate('cascade')->onDelete('set null');
        });

        // جدول البدلات الثابتة التعاقدية
        Schema::create('employee_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onUpdate('cascade')->onDelete('cascade');
            $table->string('name', 150);
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_allowances');

        // لازم نفك القيد الدائري الأول وإلا الحذف هيفشل
        if (Schema::hasTable('admins')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropForeign(['employee_id']);
            });
        }

        Schema::dropIfExists('employees');
    }
};
