<?php


use Brucelwayne\Admin\Controllers\PagesController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        Route::get('pages/index', [PagesController::class, 'index'])
            ->name('pages.index');
        Route::post('pages/create', [PagesController::class, 'create'])
            ->name('pages.create');
        Route::post('page/edit', [PagesController::class, 'edit'])
            ->name('page.edit');
        Route::post('page/translate', [PagesController::class, 'translate'])
            ->name('page.translate');
        Route::post('pages/search', [PagesController::class, 'search'])
            ->name('pages.search');

        Route::post('page/ai-seo', [PagesController::class, 'aiSEO'])
            ->name('page.ai-seo');
        Route::post('page/ai-translate', [PagesController::class, 'aiTranslate'])
            ->name('page.ai-translate');
        Route::post('page/seo-index', [PagesController::class, 'seoIndex'])
            ->name('page.seo-index');

    });
