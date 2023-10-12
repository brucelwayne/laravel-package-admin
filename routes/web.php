<?php


use Brucelwayne\Admin\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::get('admin/login', [AuthController::class, 'login'])
        ->name('admin.login');
    Route::middleware(['throttle:10,1'])
        ->post('admin/login',[AuthController::class, 'attemptLogin'])
        ->name('admin.attempt-login');
});