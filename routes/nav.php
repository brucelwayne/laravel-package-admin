<?php


use Brucelwayne\Admin\Controllers\NavController;
use Illuminate\Support\Facades\Route;

//管理员登录后
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        //网页导航栏
        Route::get('/navs/index', [NavController::class, 'index'])->name('navs.index');
        Route::post('/navs/store', [NavController::class, 'store'])->name('nav.store');
        Route::put('/navs/update', [NavController::class, 'update'])->name('nav.update');
        Route::post('/navs/search', [NavController::class, 'search'])->name('navs.search');
        Route::post('/navs/up', [NavController::class, 'up'])->name('nav.up');
        Route::post('/navs/down', [NavController::class, 'down'])->name('nav.down');
        Route::delete('/navs/delete', [NavController::class, 'delete'])->name('nav.delete');
    });
