<?php

namespace App\Game\Skills\Providers;

use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Flare\Transformers\BasicSkillsTransformer;
use App\Flare\Transformers\SkillsTransformer;
use App\Game\Core\Services\CharacterInventoryService;
use App\Game\Core\Services\RandomEnchantmentService;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\EnchantItemService;
use App\Game\Skills\Services\SkillService;
use App\Game\Skills\Services\TrinketCraftingService;
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
        $this->app->bind(EnchantItemService::class, function() {
            return new EnchantItemService;
        });

        $this->app->bind(CraftingService::class, function($app) {
            return new CraftingService(
                $app->make(RandomEnchantmentService::class),
                $app->make(SkillService::class)
            );
        });

        $this->app->bind(AlchemyService::class, function() {
            return new AlchemyService;
        });

        $this->app->bind(DisenchantService::class, function() {
            return new DisenchantService();
        });

        $this->app->bind(SkillService::class, function($app) {
            return new SkillService(
                $app->make(Manager::class),
                $app->make(BasicSkillsTransformer::class),
                $app->make(SkillsTransformer::class),
                $app->make(UpdateCharacterAttackTypes::class),
            );
        });

        $this->app->bind(TrinketCraftingService::class, function($app) {
            return new TrinketCraftingService($app->make(CraftingService::class));
        });

        $this->app->bind(EnchantingService::class, function($app) {
            return new EnchantingService(
                $app->make(CharacterStatBuilder::class),
                $app->make(CharacterInventoryService::class),
                $app->make(EnchantItemService::class),
                $app->make(RandomEnchantmentService::class),
            );
        });
    }
}
