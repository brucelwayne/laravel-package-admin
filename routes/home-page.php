<?php


use Brucelwayne\Admin\Controllers\HomePageController;
use Illuminate\Support\Facades\Route;

//管理员登录后
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        Route::get('/home-page/index', [HomePageController::class, 'index'])->name('home-page.index');
        Route::get('/home-page/get-option', [HomePageController::class, 'getOption'])->name('home-page.get-option');
        Route::post('/home-page/index', [HomePageController::class, 'store']);

    });
