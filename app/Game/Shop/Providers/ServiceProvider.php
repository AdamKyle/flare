<?php

namespace App\Game\Shop\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Shop\Services\ShopService;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->bind(ShopService::class, function($app) {
            return new ShopService();
        });
    }
}
