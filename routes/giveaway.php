<?php


use Brucelwayne\Admin\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

//管理员登录后
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        Route::get('/giveaways/index', [ProductController::class, 'index'])->name('giveaways.index');

    });
