<?php

namespace App\Game\Shop\Providers;

use App\Game\CharacterInventory\Services\CharacterGemBagService;
use App\Game\CharacterInventory\Services\EquipItemService;
use App\Game\Shop\Services\GemShopService;
use App\Game\Shop\Services\GoblinShopService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Shop\Services\ShopService;

class ServiceProvider extends ApplicationServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {

        $this->app->bind(ShopService::class, function ($app) {
            return new ShopService(
                $app->make(EquipItemService::class)
            );
        });

        $this->app->bind(GoblinShopService::class, function () {
            return new GoblinShopService();
        });

        $this->app->bind(GemShopService::class, function ($app) {
            return new GemShopService($app->make(CharacterGemBagService::class));
        });
    }
}
