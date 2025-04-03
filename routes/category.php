<?php


use Brucelwayne\Admin\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

//管理员登录后
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        Route::get('/categories/index', [CategoryController::class, 'index'])->name('categories.index');

        Route::get('categories/sub', [CategoryController::class, 'sub'])
            ->name('categories.sub');

        Route::post('category/up', [CategoryController::class, 'up'])
            ->name('category.up');
        Route::post('category/down', [CategoryController::class, 'down'])
            ->name('category.down');
        Route::post('category/status', [CategoryController::class, 'status'])
            ->name('category.status');
        Route::delete('categories/delete', [CategoryController::class, 'delete'])
            ->name('categories.delete');

        Route::get('categories/search', [CategoryController::class, 'search'])
            ->name('categories.search');
        Route::get('categories/create', [CategoryController::class, 'create'])
            ->name('categories.create');
        Route::post('categories/store', [CategoryController::class, 'store'])
            ->name('categories.store');
        Route::get('categories/edit', [CategoryController::class, 'edit'])
            ->name('categories.edit');
        Route::put('categories/update', [CategoryController::class, 'update'])
            ->name('categories.update');
        Route::put('categories/update-files', [CategoryController::class, 'updateFiles'])
            ->name('categories.update-files');

        Route::post('categories/ai-seo', [CategoryController::class, 'aiSEO'])
            ->name('category.ai-seo');
        Route::post('categories/ai-translate', [CategoryController::class, 'aiTranslateJson'])
            ->name('category.ai-translate');
    });
