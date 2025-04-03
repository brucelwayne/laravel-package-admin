<?php

use Brucelwayne\Admin\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

//未登录
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'guest.admin'])
    ->group(function () {

        Route::get('/login', [AuthController::class, 'login'])
            ->name('login');
        Route::middleware(['throttle:300,1'])
            ->post('/auth/send-email-otp', [AuthController::class, 'sendEmailOtp'])
            ->name('auth.send-email-otp');
        Route::middleware(['throttle:300,1'])
            ->post('/auth/verify-email-otp', [AuthController::class, 'verityEmailOtp'])
            ->name('auth.verify-email-otp');

    });

//管理员登录后
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        Route::post('logout', [AuthController::class, 'logout'])->name('logout');


    });
