<?php


use Brucelwayne\Admin\Controllers\UserController;
use Illuminate\Support\Facades\Route;

//管理员登录后
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        //用户管理
        Route::get('/users/index', [UserController::class, 'active'])->name('users.index');
        Route::get('users/active', [UserController::class, 'active'])
            ->name('users.active');
        Route::get('users/registered', [UserController::class, 'registered'])
            ->name('users.registered');
        //用户
        Route::post('user/update-profile', [UserController::class, 'updateProfile'])
            ->name('user.update-profile');

    });
