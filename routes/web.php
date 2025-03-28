<?php


use Brucelwayne\Admin\Controllers\AuthController;
use Brucelwayne\Admin\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

//未登录的状态
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'guest.admin'])
    ->group(function () {

        Route::get('login', [AuthController::class, 'login'])
            ->name('login');
        Route::middleware(['throttle:10,1'])
            ->post('login', [AuthController::class, 'attemptLogin'])
            ->name('attempt-login');

    });

//管理员登录后
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {
        Route::get('/', [WelcomeController::class, 'index'])
            ->name('dashboard');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    });