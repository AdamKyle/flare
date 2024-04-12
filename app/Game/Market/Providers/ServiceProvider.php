<?php

namespace App\Game\Market\Providers;

use App\Game\Character\CharacterInventory\Services\EquipItemService;
use App\Game\Market\Middleware\CanCharacterAccessMarket;
use App\Game\Market\Services\MarketBoard;
use App\Game\Market\Services\MarketHistory;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(MarketBoard::class, function ($app) {
            return new MarketBoard($app->make(EquipItemService::class));
        });

        $this->app->bind(MarketSaleHistory::class, function ($app) {
            return new MarketSaleHistory();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        $router = $this->app['router'];

        $router->aliasMiddleware('can.access.market', CanCharacterAccessMarket::class);
    }
}
