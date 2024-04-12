<?php

namespace App\Game\Character\CharacterInventory\Providers;

use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\InventoryTransformer;
use App\Flare\Transformers\Serializers\CoreSerializer;
use App\Flare\Transformers\UsableItemTransformer;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\ComparisonService;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use App\Game\Character\CharacterInventory\Services\UseItemService;
use App\Game\Character\CharacterInventory\Validations\SetHandsValidation;
use App\Game\Core\Values\ValidEquipPositionsValue;
use App\Game\Gems\Services\ItemAtonements;
use App\Game\Skills\Services\MassDisenchantService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use League\Fractal\Manager;

class ServiceProvider extends ApplicationServiceProvider {

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(Manager::class, function ($app) {
            $manager = new Manager();

            // Attach the serializer
            $manager->setSerializer(new CoreSerializer());

            return $manager;
        });

        $this->app->bind(SetHandsValidation::class, function () {
            return new SetHandsValidation();
        });

        $this->app->bind(InventorySetService::class, function ($app) {
            return new InventorySetService(
                $app->make(
                    SetHandsValidation::class
                )
            );
        });

        $this->app->bind(EquipItemService::class, function ($app) {
            return new EquipItemService($app->make(Manager::class), $app->make(CharacterAttackTransformer::class), $app->make(InventorySetService::class));
        });

        $this->app->bind(CharacterInventoryService::class, function ($app) {
            return new CharacterInventoryService(
                $app->make(InventoryTransformer::class),
                $app->make(UsableItemTransformer::class),
                $app->make(MassDisenchantService::class),
                $app->make(UpdateCharacterSkillsService::class),
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
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
    }
}
