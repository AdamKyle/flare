<?php

namespace App\Game\Character\CharacterInventory\Providers;

use App\Flare\Items\Enricher\ItemEnricherFactory;
use App\Flare\Items\Transformers\EquippableItemTransformer;
use App\Flare\Items\Transformers\QuestItemTransformer;
use App\Flare\Pagination\Pagination;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\CharacterGemSlotsTransformer;
use App\Flare\Transformers\CharacterGemsTransformer;
use App\Flare\Transformers\CharacterInventoryCountTransformer;
use App\Flare\Transformers\InventoryTransformer;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Flare\Transformers\UsableItemTransformer;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\CharacterInventory\Builders\EquipManyBuilder;
use App\Game\Character\CharacterInventory\Services\CharacterGemBagService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\ComparisonService;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use App\Game\Character\CharacterInventory\Services\UseItemService;
use App\Game\Character\CharacterInventory\Validations\SetHandsValidation;
use App\Game\Character\CharacterSheet\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Core\Values\ValidEquipPositionsValue;
use App\Game\Gems\Services\ItemAtonements;
use App\Game\Shop\Services\ShopService;
use App\Game\Skills\Services\DisenchantManyService;
use App\Game\Skills\Services\MassDisenchantService;
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

        $this->app->bind(Manager::class, function () {
            return new Manager;
        });

        $this->app->bind(SetHandsValidation::class, function () {
            return new SetHandsValidation;
        });

        $this->app->bind(InventorySetService::class, function ($app) {
            return new InventorySetService(
                $app->make(SetHandsValidation::class),
                $app->make(CharacterInventoryService::class),
                $app->make(UpdateCharacterAttackTypesHandler::class),
            );
        });

        $this->app->bind(EquipItemService::class, function ($app) {
            return new EquipItemService(
                $app->make(Manager::class),
                $app->make(CharacterAttackTransformer::class),
                $app->make(InventorySetService::class),
                $app->make(CharacterInventoryService::class),
                $app->make(UpdateCharacterAttackTypesHandler::class)
            );
        });

        $this->app->bind(CharacterInventoryService::class, function ($app) {
            return new CharacterInventoryService(
                $app->make(ItemEnricherFactory::class),
                $app->make(EquippableItemTransformer::class),
                $app->make(QuestItemTransformer::class),
                $app->make(UsableItemTransformer::class),
                $app->make(InventoryTransformer::class),
                $app->make(MassDisenchantService::class),
                $app->make(UpdateCharacterSkillsService::class),
                $app->make(UpdateCharacterAttackTypesHandler::class),
                $app->make(Pagination::class),
                $app->make(Manager::class)
            );
        });

        $this->app->bind(UseItemService::class, function ($app) {
            return new UseItemService(
                $app->make(Manager::class),
                $app->make(CharacterSheetBaseInfoTransformer::class),
                $app->make(UpdateCharacterAttackTypesHandler::class),
                $app->make(CharacterInventoryService::class),
            );
        });

        $this->app->bind(ComparisonService::class, function ($app) {
            return new ComparisonService(
                $app->make(ValidEquipPositionsValue::class),
                $app->make(CharacterInventoryService::class),
                $app->make(EquipItemService::class),
                $app->make(ItemAtonements::class)
            );
        });

        $this->app->bind(EquipManyBuilder::class, function () {
            return new EquipManyBuilder;
        });

        $this->app->bind(MultiInventoryActionService::class, function ($app) {
            return new MultiInventoryActionService(
                $app->make(InventorySetService::class),
                $app->make(EquipItemService::class),
                $app->make(EquipManyBuilder::class),
                $app->make(ShopService::class),
                $app->make(CharacterInventoryService::class),
                $app->make(UpdateCharacterAttackTypesHandler::class),
                $app->make(DisenchantManyService::class),
                $app->make(Manager::class),
                $app->make(CharacterInventoryCountTransformer::class),
            );
        });

        $this->app->bind(CharacterGemBagService::class, function ($app) {
            return new CharacterGemBagService(
                $app->make(Manager::class),
                $app->make(PlainDataSerializer::class),
                $app->make(CharacterGemSlotsTransformer::class),
                $app->make(CharacterGemsTransformer::class),
                $app->make(Pagination::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
