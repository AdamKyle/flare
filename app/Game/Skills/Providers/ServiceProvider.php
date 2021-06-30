<?php

namespace App\Game\Skills\Providers;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\EnchantItemService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(EnchantItemService::class, function() {
            return new EnchantItemService;
        });

        $this->app->bind(CraftingService::class, function() {
            return new CraftingService;
        });

        $this->app->bind(AlchemyService::class, function() {
            return new AlchemyService;
        });

        $this->app->bind(DisenchantService::class, function() {
            return new DisenchantService;
        });

        $this->app->bind(EnchantingService::class, function($app) {
            return new EnchantingService(
                $app->make(CharacterInformationBuilder::class),
                $app->make(EnchantItemService::class)
            );
        });
    }
}
