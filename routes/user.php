<?php


use Brucelwayne\Admin\Controllers\SellersController;
use Illuminate\Support\Facades\Route;

//管理员登录后
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        //用户管理
        Route::get('/users/index', [SellersController::class, 'index'])->name('users.index');


    });
