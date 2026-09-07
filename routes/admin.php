<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\AuthAdminController;
use App\Http\Controllers\Admin\CustomerSourceController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthAdminController::class,'login'])->name('login');
Route::post('/login', [AuthAdminController::class,'checkLogin'])->name('checkLogin');

// forgot-password
Route::get('/forgot-password', [AuthAdminController::class,'forgotPassword'])->name('forgotPassword');
Route::post('/forgot-password', [AuthAdminController::class,'sendPassword'])->name('sendPassword');

// reset-password (بيتفتح من الرابط اللي في الإيميل)
Route::get('/reset-password/{token}', [AuthAdminController::class,'showResetForm'])->name('password.reset');
Route::post('/reset-password', [AuthAdminController::class,'resetPassword'])->name('password.update');

Route::get('/logout', [AuthAdminController::class,'logout'])->name('logout');

// HomeController
Route::get('/home', [HomeController::class,'home'])->name('home')->middleware('AdminPermission:dashboard_show');
Route::get('/home/data', [HomeController::class,'dashboardData'])->name('home.data')->middleware('AdminPermission:dashboard_show');
Route::get('/system-guide', [HomeController::class,'systemGuide'])->name('system-guide')->middleware('AdminPermission:dashboard_show');
Route::get('/403', [HomeController::class,'error_403'])->name('error_403');

// AdminController
Route::get('/profile', [AdminController::class,'profile'])->name('profile')->middleware('AdminPermission:admin_profile_show');
Route::post('/profile', [AdminController::class,'updateProfile'])->name('profile.update')->middleware('AdminPermission:admin_profile_update');

Route::resource('/admins', AdminController::class)
->except(['show'])
->names([
    'index' => 'admins',
    'store' => 'admins.store',
    'update' => 'admins.update',
    'destroy' => 'admins.destroy',
])->middleware([
    'index' => 'AdminPermission:admin_show',
    'store' => 'AdminPermission:admin_create',
    'update' => 'AdminPermission:admin_update',
    'destroy' => 'AdminPermission:admin_delete',
]);

// SettingsController
Route::get('/settings', [SettingsController::class,'index'])->name('settings')->middleware('AdminPermission:setting_show');
Route::post('/settings', [SettingsController::class,'update'])->name('settings.update')->middleware('AdminPermission:setting_update');
Route::get('/settings/clearCache', [SettingsController::class,'clearCache'])->name('settings.clearCache')->middleware('AdminPermission:setting_clear_cache');



// RoleController
Route::resource('/roles', RoleController::class)
->except(['show'])
->names([
    'index'   => 'roles',
    'create'  => 'roles.create',
    'store'   => 'roles.store',
    'edit'    => 'roles.edit',
    'update'  => 'roles.update',
    'destroy' => 'roles.destroy',
])->middleware([
    'index'   => 'AdminPermission:role_show',
    'create'  => 'AdminPermission:role_create',
    'store'   => 'AdminPermission:role_create',
    'edit'    => 'AdminPermission:role_update',
    'update'  => 'AdminPermission:role_update',
    'destroy' => 'AdminPermission:role_delete',
]);

// ========================================================
//  HR Module — الموارد البشرية
// ========================================================

use App\Http\Controllers\Admin\HR\BranchController;
use App\Http\Controllers\Admin\HR\JobTitleController;
use App\Http\Controllers\Admin\HR\EmployeeController;
use App\Http\Controllers\Admin\HR\EmployeeSalaryController;
use App\Http\Controllers\Admin\HR\EmployeeDocumentController;
use App\Http\Controllers\Admin\HR\EmployeeSkillController;

// 1. الفروع (Branches)
Route::get('/branches/trash', [BranchController::class, 'trash'])->name('branches.trash')->middleware('AdminPermission:branch_trash');
Route::post('/branches/{id}/restore', [BranchController::class, 'restore'])->name('branches.restore')->middleware('AdminPermission:branch_restore');
Route::delete('/branches/{id}/force-delete', [BranchController::class, 'forceDelete'])->name('branches.forceDelete')->middleware('AdminPermission:branch_force_delete');

Route::resource('/branches', BranchController::class)
->except(['show'])
->names([
    'index'   => 'branches',
    'create'  => 'branches.create',
    'store'   => 'branches.store',
    'edit'    => 'branches.edit',
    'update'  => 'branches.update',
    'destroy' => 'branches.destroy',
])->middleware([
    'index'   => 'AdminPermission:branch_show',
    'create'  => 'AdminPermission:branch_create',
    'store'   => 'AdminPermission:branch_create',
    'edit'    => 'AdminPermission:branch_update',
    'update'  => 'AdminPermission:branch_update',
    'destroy' => 'AdminPermission:branch_delete',
]);

// 2. المسميات الوظيفية (Job Titles)
Route::get('/job-titles/trash', [JobTitleController::class, 'trash'])->name('job_titles.trash')->middleware('AdminPermission:job_title_trash');
Route::post('/job-titles/{id}/restore', [JobTitleController::class, 'restore'])->name('job_titles.restore')->middleware('AdminPermission:job_title_restore');
Route::delete('/job-titles/{id}/force-delete', [JobTitleController::class, 'forceDelete'])->name('job_titles.forceDelete')->middleware('AdminPermission:job_title_force_delete');

Route::resource('/job-titles', JobTitleController::class)
->except(['show'])
->names([
    'index'   => 'job_titles',
    'create'  => 'job_titles.create',
    'store'   => 'job_titles.store',
    'edit'    => 'job_titles.edit',
    'update'  => 'job_titles.update',
    'destroy' => 'job_titles.destroy',
])->middleware([
    'index'   => 'AdminPermission:job_title_show',
    'create'  => 'AdminPermission:job_title_create',
    'store'   => 'AdminPermission:job_title_create',
    'edit'    => 'AdminPermission:job_title_update',
    'update'  => 'AdminPermission:job_title_update',
    'destroy' => 'AdminPermission:job_title_delete',
]);

// 3. الموظفون (Employees)
Route::get('/employees/trash', [EmployeeController::class, 'trash'])->name('employees.trash')->middleware('AdminPermission:employee_trash');
Route::post('/employees/{id}/restore', [EmployeeController::class, 'restore'])->name('employees.restore')->middleware('AdminPermission:employee_restore');
Route::delete('/employees/{id}/force-delete', [EmployeeController::class, 'forceDelete'])->name('employees.forceDelete')->middleware('AdminPermission:employee_force_delete');

Route::resource('/employees', EmployeeController::class)
->names([
    'index'   => 'employees',
    'show'    => 'employees.show',
    'create'  => 'employees.create',
    'store'   => 'employees.store',
    'edit'    => 'employees.edit',
    'update'  => 'employees.update',
    'destroy' => 'employees.destroy',
])->middleware([
    'index'   => 'AdminPermission:employee_show',
    'show'    => 'AdminPermission:employee_show',
    'create'  => 'AdminPermission:employee_create',
    'store'   => 'AdminPermission:employee_create',
    'edit'    => 'AdminPermission:employee_update',
    'update'  => 'AdminPermission:employee_update',
    'destroy' => 'AdminPermission:employee_delete',
]);

// 4. سجل رواتب الموظف (Employee Salaries)
Route::post('/salaries', [EmployeeSalaryController::class, 'store'])->name('salaries.store')->middleware('AdminPermission:salary_create');
Route::put('/salaries/{salary}', [EmployeeSalaryController::class, 'update'])->name('salaries.update')->middleware('AdminPermission:salary_update');
Route::delete('/salaries/{salary}', [EmployeeSalaryController::class, 'destroy'])->name('salaries.destroy')->middleware('AdminPermission:salary_delete');
Route::post('/salaries/{id}/restore', [EmployeeSalaryController::class, 'restore'])->name('salaries.restore')->middleware('AdminPermission:salary_restore');
Route::delete('/salaries/{id}/force-delete', [EmployeeSalaryController::class, 'forceDelete'])->name('salaries.forceDelete')->middleware('AdminPermission:salary_force_delete');

// 5. مستندات الموظف (Employee Documents)
Route::post('/documents', [EmployeeDocumentController::class, 'store'])->name('documents.store')->middleware('AdminPermission:document_create');
Route::post('/documents/{document}/toggle-verification', [EmployeeDocumentController::class, 'toggleVerification'])->name('documents.toggle-verification')->middleware('AdminPermission:document_update');
Route::put('/documents/{document}', [EmployeeDocumentController::class, 'update'])->name('documents.update')->middleware('AdminPermission:document_update');
Route::delete('/documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('documents.destroy')->middleware('AdminPermission:document_delete');
Route::post('/documents/{id}/restore', [EmployeeDocumentController::class, 'restore'])->name('documents.restore')->middleware('AdminPermission:document_restore');
Route::delete('/documents/{id}/force-delete', [EmployeeDocumentController::class, 'forceDelete'])->name('documents.forceDelete')->middleware('AdminPermission:document_force_delete');

// 6. مهارات الموظف (Employee Skills)
Route::post('/skills', [EmployeeSkillController::class, 'store'])->name('skills.store')->middleware('AdminPermission:skill_create');
Route::put('/skills/{skill}', [EmployeeSkillController::class, 'update'])->name('skills.update')->middleware('AdminPermission:skill_update');
Route::delete('/skills/{skill}', [EmployeeSkillController::class, 'destroy'])->name('skills.destroy')->middleware('AdminPermission:skill_delete');
Route::post('/skills/{id}/restore', [EmployeeSkillController::class, 'restore'])->name('skills.restore')->middleware('AdminPermission:skill_restore');
Route::delete('/skills/{id}/force-delete', [EmployeeSkillController::class, 'forceDelete'])->name('skills.forceDelete')->middleware('AdminPermission:skill_force_delete');

use App\Http\Controllers\Admin\HR\EmployeeLeaveController;
use App\Http\Controllers\Admin\HR\EmployeePermissionController;
use App\Http\Controllers\Admin\HR\EmployeePenaltyController;
use App\Http\Controllers\Admin\HR\EmployeeRewardController;
use App\Http\Controllers\Admin\HR\EmployeeAdvanceController;
use App\Http\Controllers\Admin\HR\PayrollController;
use App\Http\Controllers\Admin\HR\EmployeeReportController;

// 7. الإجازات (Employee Leaves & Balances)
Route::get('/leaves/balances', [EmployeeLeaveController::class, 'balances'])->name('leaves.balances')->middleware('AdminPermission:leave_balance_manage');
Route::put('/leaves/balances/{balance}', [EmployeeLeaveController::class, 'updateBalance'])->name('leaves.balances.update')->middleware('AdminPermission:leave_balance_manage');
Route::post('/leaves/{leave}/approve', [EmployeeLeaveController::class, 'approve'])->name('leaves.approve')->middleware('AdminPermission:leave_approve');
Route::post('/leaves/{leave}/reject', [EmployeeLeaveController::class, 'reject'])->name('leaves.reject')->middleware('AdminPermission:leave_reject');

Route::get('/leaves/trash', [EmployeeLeaveController::class, 'trash'])->name('leaves.trash')->middleware('AdminPermission:leave_trash');
Route::post('/leaves/{id}/restore', [EmployeeLeaveController::class, 'restore'])->name('leaves.restore')->middleware('AdminPermission:leave_restore');
Route::delete('/leaves/{id}/force-delete', [EmployeeLeaveController::class, 'forceDelete'])->name('leaves.forceDelete')->middleware('AdminPermission:leave_force_delete');

Route::resource('/leaves', EmployeeLeaveController::class)
    ->only(['index', 'store', 'destroy'])
    ->parameters(['leaves' => 'leave'])
    ->names([
        'index'   => 'leaves.index',
        'store'   => 'leaves.store',
        'destroy' => 'leaves.destroy',
    ])->middleware([
        'index'   => 'AdminPermission:leave_show',
        'store'   => 'AdminPermission:leave_create',
        'destroy' => 'AdminPermission:leave_delete',
    ]);

// 8. الأذونات (Employee Permissions)
Route::post('/permissions/{permission}/approve', [EmployeePermissionController::class, 'approve'])->name('permissions.approve')->middleware('AdminPermission:permission_approve');
Route::post('/permissions/{permission}/reject', [EmployeePermissionController::class, 'reject'])->name('permissions.reject')->middleware('AdminPermission:permission_reject');

Route::get('/permissions/trash', [EmployeePermissionController::class, 'trash'])->name('permissions.trash')->middleware('AdminPermission:permission_trash');
Route::post('/permissions/{id}/restore', [EmployeePermissionController::class, 'restore'])->name('permissions.restore')->middleware('AdminPermission:permission_restore');
Route::delete('/permissions/{id}/force-delete', [EmployeePermissionController::class, 'forceDelete'])->name('permissions.forceDelete')->middleware('AdminPermission:permission_force_delete');

Route::resource('/permissions', EmployeePermissionController::class)
    ->only(['index', 'store', 'destroy'])
    ->names([
        'index'   => 'permissions.index',
        'store'   => 'permissions.store',
        'destroy' => 'permissions.destroy',
    ])->middleware([
        'index'   => 'AdminPermission:permission_show',
        'store'   => 'AdminPermission:permission_create',
        'destroy' => 'AdminPermission:permission_delete',
    ]);

// 9. الجزاءات (Employee Penalties)
Route::get('/penalties/trash', [EmployeePenaltyController::class, 'trash'])->name('penalties.trash')->middleware('AdminPermission:penalty_trash');
Route::post('/penalties/{id}/restore', [EmployeePenaltyController::class, 'restore'])->name('penalties.restore')->middleware('AdminPermission:penalty_restore');
Route::delete('/penalties/{id}/force-delete', [EmployeePenaltyController::class, 'forceDelete'])->name('penalties.forceDelete')->middleware('AdminPermission:penalty_force_delete');

// اعتماد ورفض الجزاءات — الجزاء مايدخلش المسير غير بعد الاعتماد
Route::post('/penalties/{penalty}/approve', [EmployeePenaltyController::class, 'approve'])
    ->name('penalties.approve')
    ->middleware('AdminPermission:penalty_approve');

Route::post('/penalties/{penalty}/reject', [EmployeePenaltyController::class, 'reject'])
    ->name('penalties.reject')
    ->middleware('AdminPermission:penalty_reject');

Route::resource('/penalties', EmployeePenaltyController::class)
    ->only(['index', 'store', 'destroy'])
    ->names([
        'index'   => 'penalties.index',
        'store'   => 'penalties.store',
        'destroy' => 'penalties.destroy',
    ])->middleware([
        'index'   => 'AdminPermission:penalty_show',
        'store'   => 'AdminPermission:penalty_create',
        'destroy' => 'AdminPermission:penalty_delete',
    ]);

// 9.1 المكافآت والبدلات الاستثنائية (Employee Rewards)
Route::get('/rewards/trash', [EmployeeRewardController::class, 'trash'])->name('rewards.trash')->middleware('AdminPermission:reward_trash');
Route::post('/rewards/{id}/restore', [EmployeeRewardController::class, 'restore'])->name('rewards.restore')->middleware('AdminPermission:reward_restore');
Route::delete('/rewards/{id}/force-delete', [EmployeeRewardController::class, 'forceDelete'])->name('rewards.forceDelete')->middleware('AdminPermission:reward_force_delete');

// اعتماد ورفض المكافآت — صرف مبلغ للموظف محتاج مراجعة
Route::post('/rewards/{reward}/approve', [EmployeeRewardController::class, 'approve'])
    ->name('rewards.approve')
    ->middleware('AdminPermission:reward_approve');

Route::post('/rewards/{reward}/reject', [EmployeeRewardController::class, 'reject'])
    ->name('rewards.reject')
    ->middleware('AdminPermission:reward_reject');

Route::resource('/rewards', EmployeeRewardController::class)
    ->only(['index', 'store', 'destroy'])
    ->names([
        'index'   => 'rewards.index',
        'store'   => 'rewards.store',
        'destroy' => 'rewards.destroy',
    ])->middleware([
        'index'   => 'AdminPermission:reward_show',
        'store'   => 'AdminPermission:reward_create',
        'destroy' => 'AdminPermission:reward_delete',
    ]);

// 10. السلف المالية (Employee Advances)
Route::post('/advances/{advance}/approve', [EmployeeAdvanceController::class, 'approve'])->name('advances.approve')->middleware('AdminPermission:advance_approve');
Route::post('/advances/{advance}/reject', [EmployeeAdvanceController::class, 'reject'])->name('advances.reject')->middleware('AdminPermission:advance_reject');
Route::post('/advances/{advance}/record-payment', [EmployeeAdvanceController::class, 'recordPayment'])->name('advances.record-payment')->middleware('AdminPermission:advance_update');
Route::post('/advances/{advance}/waive', [EmployeeAdvanceController::class, 'waive'])->name('advances.waive')->middleware('AdminPermission:advance_update');
Route::get('/advances/{advance}/repayments', [EmployeeAdvanceController::class, 'repayments'])->name('advances.repayments')->middleware('AdminPermission:advance_show');

Route::get('/advances/trash', [EmployeeAdvanceController::class, 'trash'])->name('advances.trash')->middleware('AdminPermission:advance_trash');
Route::post('/advances/{id}/restore', [EmployeeAdvanceController::class, 'restore'])->name('advances.restore')->middleware('AdminPermission:advance_restore');
Route::delete('/advances/{id}/force-delete', [EmployeeAdvanceController::class, 'forceDelete'])->name('advances.forceDelete')->middleware('AdminPermission:advance_force_delete');

Route::resource('/advances', EmployeeAdvanceController::class)
    ->only(['index', 'store', 'destroy'])
    ->names([
        'index'   => 'advances.index',
        'store'   => 'advances.store',
        'destroy' => 'advances.destroy',
    ])->middleware([
        'index'   => 'AdminPermission:advance_show',
        'store'   => 'AdminPermission:advance_create',
        'destroy' => 'AdminPermission:advance_delete',
    ]);

// 11. مسيرات الرواتب وسندات الصرف (Payrolls & Payslips)
Route::get('/payrolls/payslip/{item}', [PayrollController::class, 'payslip'])->name('payrolls.payslip')->middleware('AdminPermission:payroll_show');
Route::match(['get', 'post'], '/payrolls/{payroll}/bulk-payslips', [PayrollController::class, 'bulkPayslips'])->name('payrolls.bulk-payslips')->middleware('AdminPermission:payroll_show');
Route::post('/payrolls/{payroll}/approve', [PayrollController::class, 'approve'])->name('payrolls.approve')->middleware('AdminPermission:payroll_approve');
Route::post('/payrolls/{payroll}/reject', [PayrollController::class, 'reject'])->name('payrolls.reject')->middleware('AdminPermission:payroll_reject');
Route::post('/payrolls/{payroll}/unapprove', [PayrollController::class, 'unapprove'])->name('payrolls.unapprove')->middleware('AdminPermission:payroll_approve');
Route::post('/payrolls/{payroll}/resubmit', [PayrollController::class, 'resubmit'])->name('payrolls.resubmit')->middleware('AdminPermission:payroll_create');
Route::post('/payrolls/{payroll}/pay', [PayrollController::class, 'pay'])->name('payrolls.pay')->middleware('AdminPermission:payroll_pay');
Route::post('/payrolls/{payroll}/pay-selected', [PayrollController::class, 'paySelected'])->name('payrolls.pay-selected')->middleware('AdminPermission:payroll_pay');
Route::post('/payrolls/items/{item}/pay', [PayrollController::class, 'payItem'])->name('payrolls.pay-item')->middleware('AdminPermission:payroll_pay');
Route::post('/payrolls/{payroll}/update-items', [PayrollController::class, 'updateItems'])->name('payrolls.update-items')->middleware('AdminPermission:payroll_create');
Route::post('/payrolls/items/{item}/update-details', [PayrollController::class, 'updateItemDetails'])->name('payrolls.update-item-details')->middleware('AdminPermission:payroll_create');

Route::resource('/payrolls', PayrollController::class)
    ->only(['index', 'create', 'store', 'show', 'destroy'])
    ->names([
        'index'   => 'payrolls.index',
        'create'  => 'payrolls.create',
        'store'   => 'payrolls.store',
        'show'    => 'payrolls.show',
        'destroy' => 'payrolls.destroy',
    ])->middleware([
        'index'   => 'AdminPermission:payroll_show',
        'create'  => 'AdminPermission:payroll_create',
        'store'   => 'AdminPermission:payroll_create',
        'show'    => 'AdminPermission:payroll_show',
        'destroy' => 'AdminPermission:payroll_delete',
    ]);

// 12. التقارير الإدارية والمالية (Reports)
Route::get('/reports/employee-statement', [EmployeeReportController::class, 'statement'])->name('reports.employee-statement')->middleware('AdminPermission:report_employee_statement');
Route::get('/reports/salary-audit', [EmployeeReportController::class, 'salaryAudit'])->name('reports.salary-audit')->middleware('AdminPermission:report_salary_audit');

use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ExpenseCategoryController;

// 13. مصاريف العهدة وإدارة الأرصدة النقدية (Custody & Expenses)
Route::get('/expenses/get-custody-balance', [ExpenseController::class, 'getCustodyBalance'])->name('expenses.get-custody-balance')->middleware('AdminPermission:expense_balance_manage');
Route::post('/expenses/update-balance', [ExpenseController::class, 'updateBalance'])->name('expenses.update-balance')->middleware('AdminPermission:expense_balance_manage');
Route::post('/expenses/rollover-balance', [ExpenseController::class, 'rolloverBalance'])->name('expenses.rollover-balance')->middleware('AdminPermission:expense_balance_manage');

Route::get('/expenses/trash', [ExpenseController::class, 'trash'])->name('expenses.trash')->middleware('AdminPermission:expense_trash');
Route::post('/expenses/{id}/restore', [ExpenseController::class, 'restore'])->name('expenses.restore')->middleware('AdminPermission:expense_restore');
Route::delete('/expenses/{id}/force-delete', [ExpenseController::class, 'forceDelete'])->name('expenses.forceDelete')->middleware('AdminPermission:expense_force_delete');

Route::resource('/expenses', ExpenseController::class)
    ->except(['show'])
    ->names([
        'index'   => 'expenses.index',
        'create'  => 'expenses.create',
        'store'   => 'expenses.store',
        'edit'    => 'expenses.edit',
        'update'  => 'expenses.update',
        'destroy' => 'expenses.destroy',
    ])->middleware([
        'index'   => 'AdminPermission:expense_show',
        'create'  => 'AdminPermission:expense_create',
        'store'   => 'AdminPermission:expense_create',
        'edit'    => 'AdminPermission:expense_update',
        'update'  => 'AdminPermission:expense_update',
        'destroy' => 'AdminPermission:expense_delete',
    ]);

// 14. تصنيفات المصروفات والعهدة (Expense Categories)
Route::get('/expense-categories/trash', [ExpenseCategoryController::class, 'trash'])->name('expense-categories.trash')->middleware('AdminPermission:expense_category_trash');
Route::post('/expense-categories/{id}/restore', [ExpenseCategoryController::class, 'restore'])->name('expense-categories.restore')->middleware('AdminPermission:expense_category_restore');
Route::delete('/expense-categories/{id}/force-delete', [ExpenseCategoryController::class, 'forceDelete'])->name('expense-categories.forceDelete')->middleware('AdminPermission:expense_category_force_delete');

Route::resource('/expense-categories', ExpenseCategoryController::class)
    ->except(['create', 'edit'])
    ->names([
        'index'   => 'expense-categories.index',
        'store'   => 'expense-categories.store',
        'show'    => 'expense-categories.show',
        'update'  => 'expense-categories.update',
        'destroy' => 'expense-categories.destroy',
    ])->middleware([
        'index'   => 'AdminPermission:expense_category_show',
        'store'   => 'AdminPermission:expense_category_create',
        'show'    => 'AdminPermission:expense_category_show',
        'update'  => 'AdminPermission:expense_category_update',
        'destroy' => 'AdminPermission:expense_category_delete',
    ]);

use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\VendorSubscriptionController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\SalesChannelController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\ReportController;

// 15. إدارة الموردين والشركات الخارجية (Vendors)
Route::get('/vendors/trash', [VendorController::class, 'trash'])->name('vendors.trash')->middleware('AdminPermission:vendor_trash');
Route::post('/vendors/{id}/restore', [VendorController::class, 'restore'])->name('vendors.restore')->middleware('AdminPermission:vendor_restore');
Route::delete('/vendors/{id}/force-delete', [VendorController::class, 'forceDelete'])->name('vendors.forceDelete')->middleware('AdminPermission:vendor_force_delete');

Route::resource('/vendors', VendorController::class)
    ->except(['show', 'create', 'edit'])
    ->names([
        'index'   => 'vendors.index',
        'store'   => 'vendors.store',
        'update'  => 'vendors.update',
        'destroy' => 'vendors.destroy',
    ])->middleware([
        'index'   => 'AdminPermission:vendor_show',
        'store'   => 'AdminPermission:vendor_create',
        'update'  => 'AdminPermission:vendor_update',
        'destroy' => 'AdminPermission:vendor_delete',
    ]);

// 16. الاشتراكات والخدمات الخارجية (Vendor Subscriptions)
Route::post('/vendor-subscriptions/{vendorSubscription}/record-payment', [VendorSubscriptionController::class, 'recordPayment'])
    ->name('vendor-subscriptions.record-payment')
    ->middleware('AdminPermission:vendor_subscription_update');

Route::get('/vendor-subscriptions/trash', [VendorSubscriptionController::class, 'trash'])->name('vendor-subscriptions.trash')->middleware('AdminPermission:vendor_subscription_trash');
Route::post('/vendor-subscriptions/{id}/restore', [VendorSubscriptionController::class, 'restore'])->name('vendor-subscriptions.restore')->middleware('AdminPermission:vendor_subscription_restore');
Route::delete('/vendor-subscriptions/{id}/force-delete', [VendorSubscriptionController::class, 'forceDelete'])->name('vendor-subscriptions.forceDelete')->middleware('AdminPermission:vendor_subscription_force_delete');

Route::resource('/vendor-subscriptions', VendorSubscriptionController::class)
    ->names([
        'index'   => 'vendor-subscriptions.index',
        'create'  => 'vendor-subscriptions.create',
        'store'   => 'vendor-subscriptions.store',
        'show'    => 'vendor-subscriptions.show',
        'edit'    => 'vendor-subscriptions.edit',
        'update'  => 'vendor-subscriptions.update',
        'destroy' => 'vendor-subscriptions.destroy',
    ])->middleware([
        'index'   => 'AdminPermission:vendor_subscription_show',
        'create'  => 'AdminPermission:vendor_subscription_create',
        'store'   => 'AdminPermission:vendor_subscription_create',
        'show'    => 'AdminPermission:vendor_subscription_show',
        'edit'    => 'AdminPermission:vendor_subscription_update',
        'update'  => 'AdminPermission:vendor_subscription_update',
        'destroy' => 'AdminPermission:vendor_subscription_delete',
    ]);

// 17. إدارة العملاء (Customers)
Route::get('/customers/trash', [CustomerController::class, 'trash'])->name('customers.trash')->middleware('AdminPermission:customer_trash');
Route::post('/customers/{id}/restore', [CustomerController::class, 'restore'])->name('customers.restore')->middleware('AdminPermission:customer_restore');
Route::delete('/customers/{id}/force-delete', [CustomerController::class, 'forceDelete'])->name('customers.forceDelete')->middleware('AdminPermission:customer_force_delete');

Route::resource('/customers', CustomerController::class)
    ->except(['show'])
    ->names([
        'index'   => 'customers.index',
        'create'  => 'customers.create',
        'store'   => 'customers.store',
        'edit'    => 'customers.edit',
        'update'  => 'customers.update',
        'destroy' => 'customers.destroy',
    ])->middleware([
        'index'   => 'AdminPermission:customer_show',
        'create'  => 'AdminPermission:customer_create',
        'store'   => 'AdminPermission:customer_create',
        'edit'    => 'AdminPermission:customer_update',
        'update'  => 'AdminPermission:customer_update',
        'destroy' => 'AdminPermission:customer_delete',
    ]);

// 18. قنوات البيع والتطبيقات (Sales Channels)
// CustomerSourceController
Route::get('/customer-sources/trash', [CustomerSourceController::class, 'trash'])->name('customer-sources.trash')->middleware('AdminPermission:customer_source_trash');
Route::post('/customer-sources/{id}/restore', [CustomerSourceController::class, 'restore'])->name('customer-sources.restore')->middleware('AdminPermission:customer_source_restore');
Route::delete('/customer-sources/{id}/force-delete', [CustomerSourceController::class, 'forceDelete'])->name('customer-sources.forceDelete')->middleware('AdminPermission:customer_source_force_delete');

Route::resource('/customer-sources', CustomerSourceController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->names([
        'index'   => 'customer-sources.index',
        'store'   => 'customer-sources.store',
        'update'  => 'customer-sources.update',
        'destroy' => 'customer-sources.destroy',
    ])
    ->middleware([
        'AdminPermission:customer_source_show',
    ]);

Route::get('/sales-channels/trash', [SalesChannelController::class, 'trash'])->name('sales-channels.trash')->middleware('AdminPermission:sales_channel_trash');
Route::post('/sales-channels/{id}/restore', [SalesChannelController::class, 'restore'])->name('sales-channels.restore')->middleware('AdminPermission:sales_channel_restore');
Route::delete('/sales-channels/{id}/force-delete', [SalesChannelController::class, 'forceDelete'])->name('sales-channels.forceDelete')->middleware('AdminPermission:sales_channel_force_delete');

Route::resource('/sales-channels', SalesChannelController::class)
    ->except(['show', 'create', 'edit'])
    ->names([
        'index'   => 'sales-channels.index',
        'store'   => 'sales-channels.store',
        'update'  => 'sales-channels.update',
        'destroy' => 'sales-channels.destroy',
    ])->middleware([
        'index'   => 'AdminPermission:sales_channel_show',
        'store'   => 'AdminPermission:sales_channel_create',
        'update'  => 'AdminPermission:sales_channel_update',
        'destroy' => 'AdminPermission:sales_channel_delete',
    ]);

// 19. فواتير المبيعات والإيرادات (Sales & Invoices)
Route::get('/sales/trash', [SaleController::class, 'trash'])->name('sales.trash')->middleware('AdminPermission:sale_trash');
Route::post('/sales/{id}/restore', [SaleController::class, 'restore'])->name('sales.restore')->middleware('AdminPermission:sale_restore');
Route::delete('/sales/{id}/force-delete', [SaleController::class, 'forceDelete'])->name('sales.forceDelete')->middleware('AdminPermission:sale_force_delete');

Route::post('/sales/{sale}/payments', [SaleController::class, 'addPayment'])
    ->name('sales.payments.store')
    ->middleware('AdminPermission:sale_update');

Route::delete('/sales/{sale}/payments/{payment}', [SaleController::class, 'deletePayment'])
    ->name('sales.payments.destroy')
    ->middleware('AdminPermission:sale_update');

Route::resource('/sales', SaleController::class)
    ->names([
        'index'   => 'sales.index',
        'create'  => 'sales.create',
        'store'   => 'sales.store',
        'show'    => 'sales.show',
        'edit'    => 'sales.edit',
        'update'  => 'sales.update',
        'destroy' => 'sales.destroy',
    ])->middleware([
        'index'   => 'AdminPermission:sale_show',
        'create'  => 'AdminPermission:sale_create',
        'store'   => 'AdminPermission:sale_create',
        'show'    => 'AdminPermission:sale_show',
        'edit'    => 'AdminPermission:sale_update',
        'update'  => 'AdminPermission:sale_update',
        'destroy' => 'AdminPermission:sale_delete',
    ]);

// 20. التقارير المالية والإدارية (Financial & Management Reports)
Route::get('/reports/financial-pl', [ReportController::class, 'financialPL'])
    ->name('reports.financial-pl')
    ->middleware('AdminPermission:report_financial_pl');

Route::get('/reports/employee-statement', [EmployeeReportController::class, 'statement'])
    ->name('reports.employee-statement')
    ->middleware('AdminPermission:report_employee_statement');


// 21. سجل حركات النظام (Activity Log)
Route::get('/activity-logs', [ActivityLogController::class, 'index'])
    ->name('activity-logs.index')
    ->middleware('AdminPermission:activity_log_show_all');

Route::get('/activity-logs/subject/{type}', [ActivityLogController::class, 'subject'])
    ->name('activity-logs.subject')
    ->middleware('AdminPermission:activity_log_show')
    ->where('type', '.*');

Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])
    ->name('activity-logs.show')
    ->middleware('AdminPermission:activity_log_show');


// 22. الإشعارات (Notifications)
// كل المسارات مقيّدة بإشعارات المشرف نفسه، فمحتاجة صلاحية عرض بس.
Route::get('/notifications', [NotificationController::class, 'index'])
    ->name('notifications.index')
    ->middleware('AdminPermission:notification_show');

Route::get('/notifications/latest', [NotificationController::class, 'latest'])
    ->name('notifications.latest')
    ->middleware('AdminPermission:notification_show');

Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])
    ->name('notifications.read-all')
    ->middleware('AdminPermission:notification_show');

Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
    ->name('notifications.read')
    ->middleware('AdminPermission:notification_show');


// ========================================================
//  تفويض قنوات البث اللحظي
// ========================================================
// المسار ده لازم يتسجّل على guard الـ admin مش الافتراضي (web)،
// وإلا Pusher بياخد 403 على /broadcasting/auth لأن المستخدم
// بيتقرا من الـ guard الغلط ويطلع null.
//
// بيتسجّل جوّه مجموعة admin عشان ياخد نفس الـ middleware والجلسة.
Route::post('/broadcasting/auth', function (Illuminate\Http\Request $request) {
    return Illuminate\Support\Facades\Broadcast::auth($request);
})
    ->name('broadcasting.auth')
    // auth:admin إجبارية: من غيرها الزائر بيوصل للتفويض، و Broadcast::auth
    // بترجع 200 لأي قناة مالهاش تعريف مطابق — يعني تسريب محتمل.
    ->middleware('auth:admin');
