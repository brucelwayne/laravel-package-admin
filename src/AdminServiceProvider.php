<?php

namespace Brucelwayne\Admin;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{

    protected string $module_name = 'admin';

    public function register()
    {

    }

    public function boot()
    {

        $this->bootConfigs();
        $this->bootRoutes();
        $this->bootMigrations();
        $this->bootComponentNamespace();
        $this->loadBladeViews();

    }

    protected function bootConfigs(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/admin.php', $this->module_name
        );
    }

    protected function bootRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/auth.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/category.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/external.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/feature-tag.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/filter-tag.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/giveaway.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/home-page.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/media.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/nav.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/order.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/page.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/permission.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/product.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/seller.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/shop.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/user.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }

    protected function bootMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function bootComponentNamespace(): void
    {
        Blade::componentNamespace('Brucelwayne\\Admin\\View\\Components', $this->module_name);
    }

    protected function loadBladeViews(): void
    {
//        $this->loadViewsFrom(__DIR__ . '/../resources/views', $this->module_name);
    }
}