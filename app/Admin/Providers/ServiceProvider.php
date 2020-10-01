<?php

namespace App\Admin\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Admin\Middleware\IsAdminMiddleware;
use App\Admin\Services\ItemAffixService;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ItemAffixService::class, function ($app) {
            return new ItemAffixService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        $router = $this->app['router'];

        $router->aliasMiddleware('is.admin', IsAdminMiddleware::class);
    }
}
