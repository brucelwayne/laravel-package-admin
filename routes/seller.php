<?php


use Brucelwayne\Admin\Controllers\SellerController;
use Illuminate\Support\Facades\Route;

//管理员登录后
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {
        //商家审核
        Route::get('/sellers/index', [SellerController::class, 'index'])->name('sellers.index');
    });
