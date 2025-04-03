<?php


use Brucelwayne\Admin\Controllers\AuthController;
use Brucelwayne\Admin\Controllers\FeatureTagController;
use Brucelwayne\Admin\Controllers\MainNavController;
use Brucelwayne\Admin\Controllers\ProductsController;
use Brucelwayne\Admin\Controllers\SellersController;
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

        Route::get('/', [WelcomeController::class, 'index'])
            ->name('home');
        Route::get('/dashboard', [WelcomeController::class, 'index'])
            ->name('dashboard');
        Route::delete('dashboard/clear-cache', [WelcomeController::class, 'clearCache'])
            ->name('dashboard.clear-cache');

        Route::get('/admin/products/index', function () {
        })->name('products.index');

        Route::get('/admin/orders/index', function () {
        })->name('orders.index');

        Route::get('/admin/shops/index', function () {
        })->name('shops.index');

        //网页导航栏
        Route::get('/main-nav/index', [MainNavController::class, 'index'])->name('main-nav.index');
        Route::post('/main-nav/store', [MainNavController::class, 'store'])->name('main-nav.store');
        Route::put('/main-nav/update', [MainNavController::class, 'update'])->name('main-nav.update');
        Route::post('/main-nav/search', [MainNavController::class, 'search'])->name('main-nav.search');
        Route::post('/main-nav/up', [MainNavController::class, 'up'])->name('main-nav.up');
        Route::post('/main-nav/down', [MainNavController::class, 'down'])->name('main-nav.down');
        Route::delete('/main-nav/delete', [MainNavController::class, 'delete'])->name('main-nav.delete');


        //分类
        Route::get('/categories/index', [ProductsController::class, 'index'])->name('categories.index');

        //所有产品
        Route::get('/products/index', [ProductsController::class, 'index'])->name('products.index');

        //所有订单
        Route::get('/orders/index', [SellersController::class, 'index'])->name('orders.index');

        //商家审核
        Route::get('/sellers/index', [SellersController::class, 'index'])->name('sellers.index');

        //店铺管理
        Route::get('/shops/index', [SellersController::class, 'index'])->name('shops.index');

        //用户管理
        Route::get('/users/index', [SellersController::class, 'index'])->name('users.index');

        //权限
        Route::get('/permissions/index', [SellersController::class, 'index'])->name('permissions.index');

        Route::get('/feature-tags/index', [FeatureTagController::class, 'index'])->name('feature-tags.index');
        Route::post('/feature-tags/toggle', [FeatureTagController::class, 'toggle'])->name('feature-tags.toggle');

    });
