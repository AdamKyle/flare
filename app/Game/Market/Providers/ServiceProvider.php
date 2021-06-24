<?php

namespace App\Game\Market\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Market\Middleware\CanCharacterAccessMarket;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('can.access.market', CanCharacterAccessMarket::class);
    }
}
