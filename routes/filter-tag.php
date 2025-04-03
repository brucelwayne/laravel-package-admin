<?php


use Brucelwayne\Admin\Controllers\FeatureTagController;
use Illuminate\Support\Facades\Route;

//管理员登录后
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {
        Route::get('/filter-tags/index', [FeatureTagController::class, 'index'])->name('filter-tags.index');
        Route::post('/filter-tag/toggle', [FeatureTagController::class, 'toggle'])->name('filter-tag.toggle');
    });
