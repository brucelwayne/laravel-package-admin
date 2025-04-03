<?php


use Brucelwayne\Admin\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

//管理员登录后
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        Route::get('/', [WelcomeController::class, 'index'])
            ->name('home');
        Route::get('/dashboard', [WelcomeController::class, 'index'])
            ->name('dashboard');
        Route::delete('dashboard/clear-cache', [WelcomeController::class, 'clearCache'])
            ->name('dashboard.clear-cache');

    });
