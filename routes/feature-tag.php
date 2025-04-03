<?php


use Brucelwayne\Admin\Controllers\FeatureTagController;
use Illuminate\Support\Facades\Route;

//管理员登录后
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        Route::get('/feature-tags/index', [FeatureTagController::class, 'index'])->name('feature-tags.index');
        Route::post('/feature-tag/toggle', [FeatureTagController::class, 'toggle'])->name('feature-tag.toggle');

    });
