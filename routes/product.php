<?php


use Brucelwayne\Admin\Controllers\ProductsController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        Route::get('/products/index', [ProductsController::class, 'index'])->name('products.index');


    });
