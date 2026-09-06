<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuthAdminController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthAdminController::class,'login'])->name('login');
Route::post('/login', [AuthAdminController::class,'checkLogin'])->name('checkLogin');

// forgot-password
Route::get('/forgot-password', [AuthAdminController::class,'forgotPassword'])->name('forgotPassword');
Route::post('/forgot-password', [AuthAdminController::class,'sendPassword'])->name('sendPassword');

Route::get('/logout', [AuthAdminController::class,'logout'])->name('logout');

// HomeController
Route::get('/home', [HomeController::class,'home'])->name('home')->middleware('AdminPermission:dashboard_show');
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

