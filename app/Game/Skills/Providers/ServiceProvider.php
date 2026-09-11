<?php

namespace App\Game\Skills\Providers;

use App\Flare\Pagination\Pagination;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\BattleRewardProcessing\Services\FactionLoyaltyRewardRequestService;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Transformers\CharacterGemSlotsTransformer;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Items\Builders\RandomAffixGenerator;
use App\Game\Core\Items\Transformers\Api\UsableItemTransformer;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Events\Services\GlobalEventGoalProgressionService;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Gems\Builders\GemBuilder;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Transformers\GemTransformer;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Npcs\Actions\QueenOfHearts\Services\RandomEnchantmentService;
use App\Game\Skills\Builders\BaseSkillBuilder;
use App\Game\Skills\Console\Commands\AssignNewSkillsToPlayers;
use App\Game\Skills\Handlers\HandleUpdatingCraftingGlobalEventGoal;
use App\Game\Skills\Handlers\HandleUpdatingEnchantingGlobalEventGoal;
use App\Game\Skills\Handlers\UpdateCraftingTasksForFactionLoyalty;
use App\Game\Skills\Handlers\UpdateItemSkill;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\DisenchantManyService;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\EnchantItemService;
use App\Game\Skills\Services\GemService;
use App\Game\Skills\Services\ItemListCostTransformerService;
use App\Game\Skills\Services\ItemSkillService;
use App\Game\Skills\Services\MassDisenchantService;
use App\Game\Skills\Services\SkillBonusContextService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Services\SkillService;
use App\Game\Skills\Services\TrinketCraftingService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use App\Game\Skills\Transformers\AlchemyItemTransformer;
use App\Game\Skills\Transformers\BasicSkillsTransformer;
use App\Game\Skills\Transformers\CraftableItemTransformer;
use App\Game\Skills\Transformers\EnchantingAffixTransformer;
use App\Game\Skills\Transformers\EnchantingItemTransformer;
use App\Game\Skills\Transformers\EventEnchantingItemTransformer;
use App\Game\Skills\Transformers\SkillsTransformer;
use App\Game\Skills\Transformers\TrinketCraftingItemTransformer;
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
        $this->app->bind(SkillBonusContextService::class);

        $this->app->bind(BaseSkillBuilder::class, function ($app) {
            return new BaseSkillBuilder($app->make(RandomNumberGenerator::class));
        });

        $this->commands([
            AssignNewSkillsToPlayers::class,
        ]);

        $this->app->bind(ItemListCostTransformerService::class, function () {
            return new ItemListCostTransformerService;
        });

        $this->app->bind(SkillCheckService::class, function ($app) {
            return new SkillCheckService($app->make(RandomNumberGenerator::class));
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
                $app->make(EventGoalsService::class),
                $app->make(GlobalEventGoalProgressionService::class),
                $app->make(GlobalEventGoalEligibilityService::class),
            );
        });

        $this->app->bind(HandleUpdatingEnchantingGlobalEventGoal::class, function ($app) {
            return new HandleUpdatingEnchantingGlobalEventGoal(
                $app->make(RandomAffixGenerator::class),
                $app->make(EventGoalsService::class),
                $app->make(GlobalEventGoalProgressionService::class),
                $app->make(GlobalEventGoalEligibilityService::class),
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
                $app->make(FactionLoyaltyService::class),
                $app->make(Pagination::class),
                $app->make(CraftableItemTransformer::class),
            );
        });

        $this->app->bind(AlchemyService::class, function ($app) {
            return new AlchemyService(
                $app->make(SkillCheckService::class),
                $app->make(ItemListCostTransformerService::class),
                $app->make(Pagination::class),
                $app->make(AlchemyItemTransformer::class),
                $app->make(UsableItemTransformer::class),
            );
        });

        $this->app->bind(MassDisenchantService::class, function ($app) {
            return new MassDisenchantService(
                $app->make(SkillCheckService::class),
                $app->make(RandomNumberGenerator::class),
                $app->make(ChanceCalculator::class),
            );
        });

        $this->app->bind(SkillService::class, function ($app) {
            return new SkillService(
                $app->make(Manager::class),
                $app->make(BasicSkillsTransformer::class),
                $app->make(SkillsTransformer::class),
                $app->make(UpdateCharacterAttackTypesHandler::class),
                $app->make(BattleMessageHandler::class),
                $app->make(PlainDataSerializer::class),
                $app->make(RandomNumberGenerator::class),
                $app->make(CharacterAreaGemEffectService::class),
            );
        });

        $this->app->bind(TrinketCraftingService::class, function ($app) {
            return new TrinketCraftingService(
                $app->make(CraftingService::class),
                $app->make(SkillCheckService::class),
                $app->make(ItemListCostTransformerService::class),
                $app->make(SkillService::class),
                $app->make(Pagination::class),
                $app->make(CraftingItemPreviewTransformer::class),
                $app->make(TrinketCraftingItemTransformer::class),
            );
        });

        $this->app->bind(EnchantingService::class, function ($app) {
            return new EnchantingService(
                $app->make(CharacterStatBuilder::class),
                $app->make(CharacterInventoryService::class),
                $app->make(EnchantItemService::class),
                $app->make(RandomEnchantmentService::class),
                $app->make(GlobalEventGoalEligibilityService::class),
                $app->make(Pagination::class),
                $app->make(EnchantingItemTransformer::class),
                $app->make(EventEnchantingItemTransformer::class),
                $app->make(EnchantingAffixTransformer::class),
            );
        });

        $this->app->bind(GemService::class, function ($app) {
            return new GemService(
                $app->make(GemBuilder::class),
                $app->make(ChanceCalculator::class),
                $app->make(GemTransformer::class),
                $app->make(ServerMessageBuilder::class),
                $app->make(CharacterGemSlotsTransformer::class),
            );
        });

        $this->app->bind(UpdateCraftingTasksForFactionLoyalty::class, function ($app) {
            return new UpdateCraftingTasksForFactionLoyalty(
                $app->make(FactionLoyaltyService::class),
                $app->make(FactionLoyaltyRewardRequestService::class),
            );
        });

        $this->app->bind(DisenchantService::class, function ($app) {
            return new DisenchantService(
                $app->make(SkillCheckService::class),
                $app->make(RandomNumberGenerator::class),
                $app->make(ChanceCalculator::class),
            );
        });

        $this->app->bind(ItemSkillService::class, function () {
            return new ItemSkillService;
        });

        $this->app->bind(UpdateItemSkill::class, function ($app) {
            return new UpdateItemSkill(
                $app->make(UpdateCharacterAttackTypesHandler::class),
                $app->make(BattleMessageHandler::class),
            );
        });

        $this->app->bind(DisenchantManyService::class, function ($app) {
            return new DisenchantManyService(
                $app->make(SkillCheckService::class),
                $app->make(RandomNumberGenerator::class),
                $app->make(ChanceCalculator::class),
            );
        });
    }
}
