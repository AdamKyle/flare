<?php

namespace App\Game\Skills\Providers;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Transformers\BasicSkillsTransformer;
use App\Flare\Transformers\SkillsTransformer;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Gems\Builders\GemBuilder;
use App\Game\NpcActions\QueenOfHeartsActions\Services\RandomEnchantmentService;
use App\Game\Skills\Handlers\HandleUpdatingCraftingGlobalEventGoal;
use App\Game\Skills\Handlers\HandleUpdatingEnchantingGlobalEventGoal;
use App\Game\Skills\Handlers\UpdateCraftingTasksForFactionLoyalty;
use App\Game\Skills\Handlers\UpdateItemSkill;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\EnchantItemService;
use App\Game\Skills\Services\GemService;
use App\Game\Skills\Services\ItemListCostTransformerService;
use App\Game\Skills\Services\ItemSkillService;
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

        $this->app->bind(ItemListCostTransformerService::class, function () {
            return new ItemListCostTransformerService;
        });

        $this->app->bind(SkillCheckService::class, function () {
            return new SkillCheckService;
        });

        $this->app->bind(EnchantItemService::class, function ($app) {
            return new EnchantItemService(
                $app->make(SkillCheckService::class),
                $app->make(HandleUpdatingEnchantingGlobalEventGoal::class)
            );
        });

        $this->app->bind(UpdateCharacterSkillsService::class, function ($app) {
            return new UpdateCharacterSkillsService($app->make(SkillService::class));
        });

        $this->app->bind(HandleUpdatingCraftingGlobalEventGoal::class, function ($app) {
            return new HandleUpdatingCraftingGlobalEventGoal(
                $app->make(RandomAffixGenerator::class),
                $app->make(EventGoalsService::class)
            );
        });

        $this->app->bind(HandleUpdatingEnchantingGlobalEventGoal::class, function ($app) {
            return new HandleUpdatingEnchantingGlobalEventGoal(
                $app->make(RandomAffixGenerator::class),
                $app->make(EventGoalsService::class)
            );
        });

        $this->app->bind(CraftingService::class, function ($app) {
            return new CraftingService(
                $app->make(RandomEnchantmentService::class),
                $app->make(SkillService::class),
                $app->make(ItemListCostTransformerService::class),
                $app->make(SkillCheckService::class),
                $app->make(UpdateCraftingTasksForFactionLoyalty::class),
                $app->make(HandleUpdatingCraftingGlobalEventGoal::class),
                $app->make(FactionLoyaltyService::class)
            );
        });

        $this->app->bind(AlchemyService::class, function ($app) {
            return new AlchemyService(
                $app->make(SkillCheckService::class),
                $app->make(ItemListCostTransformerService::class)
            );
        });

        $this->app->bind(MassDisenchantService::class, function ($app) {
            return new MassDisenchantService($app->make(SkillCheckService::class));
        });

        $this->app->bind(SkillService::class, function ($app) {
            return new SkillService(
                $app->make(Manager::class),
                $app->make(BasicSkillsTransformer::class),
                $app->make(SkillsTransformer::class),
                $app->make(UpdateCharacterAttackTypesHandler::class),
            );
        });

        $this->app->bind(TrinketCraftingService::class, function ($app) {
            return new TrinketCraftingService(
                $app->make(CraftingService::class),
                $app->make(SkillCheckService::class),
                $app->make(ItemListCostTransformerService::class)
            );
        });

        $this->app->bind(EnchantingService::class, function ($app) {
            return new EnchantingService(
                $app->make(CharacterStatBuilder::class),
                $app->make(CharacterInventoryService::class),
                $app->make(EnchantItemService::class),
                $app->make(RandomEnchantmentService::class),
            );
        });

        $this->app->bind(GemService::class, function ($app) {
            return new GemService(
                $app->make(GemBuilder::class)
            );
        });

        $this->app->bind(UpdateCraftingTasksForFactionLoyalty::class, function ($app) {
            return new UpdateCraftingTasksForFactionLoyalty(
                $app->make(RandomAffixGenerator::class),
                $app->make(FactionLoyaltyService::class)
            );
        });



        $this->app->bind(DisenchantService::class, function ($app) {
            return new DisenchantService($app->make(SkillCheckService::class), $app->make(CharacterInventoryService::class));
        });

        $this->app->bind(ItemSkillService::class, function () {
            return new ItemSkillService;
        });

        $this->app->bind(UpdateItemSkill::class, function ($app) {
            return new UpdateItemSkill(
                $app->make(UpdateCharacterAttackTypesHandler::class),
            );
        });
    }
}
