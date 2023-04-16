<?php

namespace App\Game\Skills\Providers;

use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Flare\Models\Skill;
use App\Flare\Transformers\BasicSkillsTransformer;
use App\Flare\Transformers\SkillsTransformer;
use App\Game\Core\Services\CharacterInventoryService;
use App\Game\NpcActions\QueenOfHeartsActions\Services\RandomEnchantmentService;
use App\Game\Skills\Builders\GemBuilder;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\EnchantItemService;
use App\Game\Skills\Services\GemService;
use App\Game\Skills\Services\ItemListCostTransformerService;
use App\Game\Skills\Services\MassDisenchantService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Services\SkillService;
use App\Game\Skills\Services\TrinketCraftingService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
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

        $this->app->bind(ItemListCostTransformerService::class, function() {
            return new ItemListCostTransformerService();
        });

        $this->app->bind(SkillCheckService::class, function() {
            return new SkillCheckService();
        });

        $this->app->bind(EnchantItemService::class, function($app) {
            return new EnchantItemService($app->make(SkillCheckService::class));
        });

        $this->app->bind(UpdateCharacterSkillsService::class, function($app) {
            return new UpdateCharacterSkillsService($app->make(SkillService::class));
        });

        $this->app->bind(CraftingService::class, function($app) {
            return new CraftingService(
                $app->make(RandomEnchantmentService::class),
                $app->make(SkillService::class),
                $app->make(ItemListCostTransformerService::class),
                $app->make(SkillCheckService::class),
            );
        });

        $this->app->bind(AlchemyService::class, function($app) {
            return new AlchemyService(
                $app->make(SkillCheckService::class),
                $app->make(ItemListCostTransformerService::class)
            );
        });

        $this->app->bind(MassDisenchantService::class, function() {
            return new MassDisenchantService();
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

        $this->app->bind(GemService::class, function($app) {
            return new GemService(
                $app->make(GemBuilder::class)
            );
        });

        $this->app->bind(GemBuilder::class, function() {
            return new GemBuilder();
        });

        $this->app->bind(DisenchantService::class, function($app) {
            return new DisenchantService($app->make(SkillCheckService::class));
        });
    }
}
