<?php


use Brucelwayne\Admin\Controllers\AuthController;
use Brucelwayne\Admin\Controllers\FeatureTagController;
use Brucelwayne\Admin\Controllers\MainNavController;
use Brucelwayne\Admin\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

//未登录的状态
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'guest.admin'])
    ->group(function () {

        Route::get('/login', [AuthController::class, 'login'])
            ->name('login');
        Route::middleware(['throttle:300,1'])
            ->post('/auth/send-email-otp', [AuthController::class, 'sendEmailOtp'])
            ->name('auth.send-email-otp');
        Route::middleware(['throttle:300,1'])
            ->post('/auth/verify-email-otp', [AuthController::class, 'verityEmailOtp'])
            ->name('auth.verify-email-otp');

    });

//管理员登录后
Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/dashboard', [WelcomeController::class, 'index'])
            ->name('dashboard');

        Route::get('/admin/products/index', function () {
        })->name('products.index');

        Route::get('/admin/orders/index', function () {
        })->name('orders.index');

        Route::get('/admin/shops/index', function () {
        })->name('shops.index');

        Route::get('/main-nav/index', [MainNavController::class, 'index'])->name('main-nav.index');
        Route::post('/main-nav/store', [MainNavController::class, 'store'])->name('main-nav.store');
        Route::put('/main-nav/update', [MainNavController::class, 'update'])->name('main-nav.update');
        Route::post('/main-nav/search', [MainNavController::class, 'search'])->name('main-nav.search');
        Route::post('/main-nav/up', [MainNavController::class, 'up'])->name('main-nav.up');
        Route::post('/main-nav/down', [MainNavController::class, 'down'])->name('main-nav.down');
        Route::delete('/main-nav/delete', [MainNavController::class, 'delete'])->name('main-nav.delete');

        Route::get('/feature-tags/index', [FeatureTagController::class, 'index'])->name('feature-tags.index');
        Route::post('/feature-tags/toggle', [FeatureTagController::class, 'toggle'])->name('feature-tags.toggle');

    });