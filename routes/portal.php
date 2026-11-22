<?php

use App\Http\Controllers\Portal\AdvanceController;
use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\DocumentController;
use App\Http\Controllers\Portal\SalaryController;
use App\Http\Controllers\Portal\ExpenseController;
use App\Http\Controllers\Portal\PenaltyController;
use App\Http\Controllers\Portal\HomeController;
use App\Http\Controllers\Portal\LeaveController;
use App\Http\Controllers\Portal\PayslipController;
use App\Http\Controllers\Portal\PermissionController;
use App\Http\Controllers\Portal\ProjectController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\RewardController;
use App\Http\Controllers\Portal\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| بوابة الموظف — /portal
|--------------------------------------------------------------------------
| guard منفصل (employee) عن لوحة الإدارة (admin): جلسة الموظف ماتوصلش لأي
| مسار إداري، وجلسة المشرف ماتفتحش البوابة.
|
| portal.access بيتأكد مع كل طلب إن الحساب لسه مفعّل، وبيجبر الموظف يغيّر
| كلمة المرور الأولية قبل أي حاجة تانية.
*/

Route::prefix('portal')->name('portal.')->middleware('throttle:WebRateLimiter')->group(function () {

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

    Route::middleware(['auth:employee', 'portal.access'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/password', [AuthController::class, 'editPassword'])->name('password.edit');
        Route::put('/password', [AuthController::class, 'updatePassword'])->name('password.update');

        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/salary', [SalaryController::class, 'index'])->name('salary.index');

        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::delete('/permissions/{id}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

        Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
        Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
        Route::delete('/leaves/{id}', [LeaveController::class, 'destroy'])->name('leaves.destroy');

        Route::get('/advances', [AdvanceController::class, 'index'])->name('advances.index');
        Route::post('/advances', [AdvanceController::class, 'store'])->name('advances.store');
        Route::delete('/advances/{id}', [AdvanceController::class, 'destroy'])->name('advances.destroy');

        // عرض بس — التسجيل من لوحة الإدارة
        Route::get('/rewards', [RewardController::class, 'index'])->name('rewards.index');
        Route::get('/penalties', [PenaltyController::class, 'index'])->name('penalties.index');
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');

        // مشاريعي — مدير المشروع بيعدّله من هنا
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->whereNumber('project')->name('projects.show');
        Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->whereNumber('project')->name('projects.edit');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->whereNumber('project')->name('projects.update');

        // مهامي — الصلاحية بدور الموظف في المهمة (TaskAccess) مش بصلاحيات أدوار
        Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('/tasks/{task}', [TaskController::class, 'show'])->whereNumber('task')->name('tasks.show');
        Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->whereNumber('task')->name('tasks.edit');
        Route::put('/tasks/{task}', [TaskController::class, 'update'])->whereNumber('task')->name('tasks.update');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->whereNumber('task')->name('tasks.destroy');
        Route::post('/tasks/{task}/status', [TaskController::class, 'status'])->whereNumber('task')->name('tasks.status');
        Route::post('/tasks/{task}/progress', [TaskController::class, 'progress'])->whereNumber('task')->name('tasks.progress');
        Route::put('/tasks/{task}/assignees', [TaskController::class, 'assignees'])->whereNumber('task')->name('tasks.assignees');
        Route::post('/tasks/{task}/decline', [TaskController::class, 'decline'])->whereNumber('task')->name('tasks.decline');
        Route::post('/tasks/{task}/checklist', [TaskController::class, 'storeChecklist'])->whereNumber('task')->name('tasks.checklist.store');
        Route::patch('/tasks/{task}/checklist/{item}', [TaskController::class, 'toggleChecklist'])->whereNumber(['task', 'item'])->name('tasks.checklist.toggle');
        Route::delete('/tasks/{task}/checklist/{item}', [TaskController::class, 'destroyChecklist'])->whereNumber(['task', 'item'])->name('tasks.checklist.destroy');

        Route::get('/payslips', [PayslipController::class, 'index'])->name('payslips.index');
        Route::get('/payslips/{id}', [PayslipController::class, 'show'])->name('payslips.show');
    });
});
