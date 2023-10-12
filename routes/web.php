<?php


use Brucelwayne\Admin\Controllers\AuthController;
use Brucelwayne\Admin\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::domain(config('app.www_domain'))
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
    Route::middleware(['web'])->group(function () {

        Route::get('login', [AuthController::class, 'login'])
            ->name('login');
        Route::middleware(['throttle:10,1'])
            ->post('login',[AuthController::class, 'attemptLogin'])
            ->name('attempt-login');
    });

    Route::middleware(['web','auth:admin'])->group(function(){
        Route::get('/',[WelcomeController::class,'index'])
            ->name('index');
        Route::post('logout',[AuthController::class, 'logout'])->name('logout');
    });
});
