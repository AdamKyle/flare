<?php

namespace App\Game\Market\Providers;

use App\Game\Market\Middleware\CanCharacterAccessMarket;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register the Market middleware alias.
     *
     * @return void
     */
    public function boot(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('can.access.market', CanCharacterAccessMarket::class);
    }
}
