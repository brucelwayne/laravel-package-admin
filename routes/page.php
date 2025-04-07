<?php


use Brucelwayne\Admin\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        Route::get('pages/index', [PageController::class, 'index'])
            ->name('pages.index');
        Route::post('pages/create', [PageController::class, 'create'])
            ->name('pages.create');
        Route::post('page/edit', [PageController::class, 'edit'])
            ->name('page.edit');
        Route::post('page/translate', [PageController::class, 'translate'])
            ->name('page.translate');
        Route::post('pages/search', [PageController::class, 'search'])
            ->name('pages.search');

        Route::post('page/ai-seo', [PageController::class, 'aiSEO'])
            ->name('page.ai-seo');
        Route::post('page/ai-translate', [PageController::class, 'aiTranslate'])
            ->name('page.ai-translate');
        Route::post('page/seo-index', [PageController::class, 'seoIndex'])
            ->name('page.seo-index');

    });
