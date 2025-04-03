<?php


use Brucelwayne\Admin\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

//管理员登录后
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        //网页导航栏
        Route::get('/media/upload-media-chunk', [MediaController::class, 'uploadChunk'])->name('media.upload-media-chunk');

    });
