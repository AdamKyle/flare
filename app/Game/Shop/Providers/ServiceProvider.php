<?php

namespace App\Game\Shop\Providers;

use App\Flare\Pagination\Pagination;
use App\Flare\Transformers\ItemTransformer;
use App\Game\Character\CharacterInventory\Services\CharacterGemBagService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use App\Game\Shop\Services\GemShopService;
use App\Game\Shop\Services\GoblinShopService;
use App\Game\Shop\Services\ShopService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use League\Fractal\Manager;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->bind(ShopService::class, function ($app) {
            return new ShopService(
                $app->make(EquipItemService::class),
                $app->make(CharacterInventoryService::class),
                $app->make(Pagination::class),
                $app->make(ItemTransformer::class),
                $app->make(Manager::class)
            );
        });

        $this->app->bind(GoblinShopService::class, function () {
            return new GoblinShopService;
        });

        $this->app->bind(GemShopService::class, function ($app) {
            return new GemShopService($app->make(CharacterGemBagService::class));
        });
    }
}
