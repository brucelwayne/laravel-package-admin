<?php


use Illuminate\Support\Facades\Route;

Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        Route::get('/products/index', function () {
        })->name('products.index');


    });
