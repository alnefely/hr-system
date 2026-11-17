<?php

use App\Http\Controllers\Portal\AdvanceController;
use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\HomeController;
use App\Http\Controllers\Portal\LeaveController;
use App\Http\Controllers\Portal\PayslipController;
use App\Http\Controllers\Portal\PermissionController;
use App\Http\Controllers\Portal\ProfileController;
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

        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::delete('/permissions/{id}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

        Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
        Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
        Route::delete('/leaves/{id}', [LeaveController::class, 'destroy'])->name('leaves.destroy');

        Route::get('/advances', [AdvanceController::class, 'index'])->name('advances.index');
        Route::post('/advances', [AdvanceController::class, 'store'])->name('advances.store');
        Route::delete('/advances/{id}', [AdvanceController::class, 'destroy'])->name('advances.destroy');

        Route::get('/payslips', [PayslipController::class, 'index'])->name('payslips.index');
        Route::get('/payslips/{id}', [PayslipController::class, 'show'])->name('payslips.show');
    });
});
