<?php


use Brucelwayne\Admin\Controllers\ProductsController;
use Illuminate\Support\Facades\Route;

//管理员登录后
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        Route::get('/giveaways/index', [ProductsController::class, 'index'])->name('giveaways.index');

    });
