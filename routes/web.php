<?php


use Illuminate\Support\Facades\Route;
use UniSharp\LaravelFilemanager\Lfm;

Route::get('/', function () {
    return redirect()->route('admin.home');
});

Route::middleware('throttle:WebRateLimiter')->group(function () {
    Route::prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));
});



Route::group(['prefix' => 'filemanager', 'middleware' => ['AdminPermission:filemanager_show']], function () {
    Lfm::routes();
});

