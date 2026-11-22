<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerSource;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLeave;
use App\Models\EmployeePenalty;
use App\Models\EmployeePermission;
use App\Models\EmployeeReward;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\JobTitle;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Sale;
use App\Models\SalesChannel;
use App\Models\Vendor;
use App\Models\VendorSubscription;

return [

    /*
    |--------------------------------------------------------------------------
    | تفعيل التسجيل
    |--------------------------------------------------------------------------
    | لو false، الـ trait بيفضل موجود لكن مابيكتبش أي سجل.
    | مفيد في بيئة الاختبار أو وقت ترحيل بيانات ضخم.
    */

    'enabled' => env('ACTIVITY_LOG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | مدة الاحتفاظ الافتراضية (بالأيام)
    |--------------------------------------------------------------------------
    | الأمر activity:prune بيستخدمها لو ماتمرّرش --days.
    */

    'retention_days' => env('ACTIVITY_LOG_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | الكتابة عبر الطابور
    |--------------------------------------------------------------------------
    | لما تبقى true، سجلات الحركات بتتكتب في الخلفية بدل ما تعمل INSERT
    | متزامن جوّه الـ request. مهم بشكل خاص في العمليات اللي بتكتب عشرات
    | السجلات (إصدار مسير رواتب مثلاً).
    |
    | محتاج queue:work شغال. لو الطابور sync الإعداد ده بيتجاهل تلقائياً.
    |
    | الافتراضي false: الكتابة مباشرة جوّه الـ request. السيرفر معتمد على
    | الـ Schedule ومفيش worker دايم، فالكتابة عبر الطابور ممكن تتأخر.
    | العمليات الجماعية (صرف المسير) بتكتب سجل واحد ملخّص فالتكلفة قليلة.
    */

    'queue_writes' => env('ACTIVITY_LOG_QUEUE_WRITES', false),

    /*
    | الطابور اللي سجلات الحركات بتتدفع عليه — يفضّل يبقى منفصل عن
    | الطوابير المهمة عشان مايزحمهاش.
    */

    'queue' => env('ACTIVITY_LOG_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | الموديولات المراقَبة
    |--------------------------------------------------------------------------
    | المفتاح = كلاس الموديل · القيمة = مفتاح الترجمة لاسم الموديول.
    |
    | الترتيب هنا هو نفس ترتيب القائمة المنسدلة في شاشة السجل.
    | أي موديل جديد: ضيف عليه trait LogsActivity وسجّله هنا.
    */

    'subjects' => [
        Employee::class           => 'employee.title',
        Branch::class             => 'branch.title',
        JobTitle::class           => 'job_title.title',
        EmployeeLeave::class      => 'leave.title',
        EmployeePermission::class => 'permission.title',
        EmployeePenalty::class    => 'penalty.title',
        EmployeeReward::class     => 'reward.title',
        EmployeeAdvance::class    => 'advance.title',
        Payroll::class            => 'payroll.title',
        PayrollItem::class        => 'payroll.singular',
        Expense::class            => 'expense.title',
        ExpenseCategory::class    => 'expense_category.title',
        Vendor::class             => 'vendor.title',
        VendorSubscription::class => 'vendor_subscription.title',
        Customer::class           => 'customer.title',
        CustomerSource::class     => 'customer_source.title',
        SalesChannel::class       => 'sales_channel.title',
        Sale::class               => 'sale.title',
    ],

    /*
    |--------------------------------------------------------------------------
    | ملف الترجمة لأسماء حقول كل موديول
    |--------------------------------------------------------------------------
    | بيُستخدم لعرض اسم الحقل بالعربي في تفاصيل التغيير بدل اسم العمود.
    | البحث بيتم على: "{file}.fields.{column}" وبعدين "global.{column}".
    */

    'field_labels' => [
        Employee::class           => 'employee',
        Branch::class             => 'branch',
        JobTitle::class           => 'job_title',
        EmployeeLeave::class      => 'leave',
        EmployeePermission::class => 'permission',
        EmployeePenalty::class    => 'penalty',
        EmployeeReward::class     => 'reward',
        EmployeeAdvance::class    => 'advance',
        Payroll::class            => 'payroll',
        PayrollItem::class        => 'payroll',
        Expense::class            => 'expense',
        ExpenseCategory::class    => 'expense_category',
        Vendor::class             => 'vendor',
        VendorSubscription::class => 'vendor_subscription',
        Customer::class           => 'customer',
        CustomerSource::class     => 'customer_source',
        SalesChannel::class       => 'sales_channel',
        Sale::class               => 'sale',
    ],

];
