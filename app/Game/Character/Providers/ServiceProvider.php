<?php

namespace App\Game\Character\Providers;

use App\Game\Battle\Services\AttackTimerService;
use App\Game\Character\Builders\AttackBuilders\AttackDetails\CharacterAttackBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ClassRanksWeaponMasteriesBuilder;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\CharacterAttack\Transformers\CharacterAttackTransformer;
use App\Game\Character\CharacterCreation\Calculators\BaseStatCalculator;
use App\Game\Character\CharacterCreation\Pipeline\CharacterCreationPipeline;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use App\Game\Character\CharacterInventory\Transformers\CharacterInventoryCountTransformer;
use App\Game\Character\CharacterInventory\Transformers\InventoryTransformer;
use App\Game\Character\CharacterSheet\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Character\Console\Commands\AssignNewFactionsToCharacters;
use App\Game\Character\Console\Commands\CreateCharacterAttackDataCache;
use App\Game\Character\Services\CharacterDeletion;
use App\Game\Core\Items\Enricher\ItemEnricherFactory;
use App\Game\Kingdoms\Handlers\GiveKingdomsToNpcHandler;
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
        $this->app->bind(CharacterDeletion::class, fn ($app) => new CharacterDeletion(
            $app->make(GiveKingdomsToNpcHandler::class),
            $app->make(CharacterCreationPipeline::class),
            $app->make(CharacterBuildState::class),
        ));

        $this->app->bind(BaseStatCalculator::class, function () {
            return new BaseStatCalculator;
        });

        $this->app->bind(CharacterAttackBuilder::class, function ($app) {
            return new CharacterAttackBuilder(
                $app->make(CharacterStatBuilder::class)
            );
        });

        $this->app->bind(CharacterAttackTransformer::class, function ($app) {
            return new CharacterAttackTransformer;
        });

        $this->app->bind(CharacterSheetBaseInfoTransformer::class, function ($app) {
            return new CharacterSheetBaseInfoTransformer(
                $app->make(CharacterStatBuilder::class),
                $app->make(AttackTimerService::class),
                $app->make(CharacterInventoryCountTransformer::class),
            );
        });

        $this->app->bind(InventoryTransformer::class, function ($app) {
            return new InventoryTransformer($app->make(ItemEnricherFactory::class));
        });

        $this->app->bind(ClassRanksWeaponMasteriesBuilder::class, function () {
            return new ClassRanksWeaponMasteriesBuilder;
        });

        $this->commands([
            CreateCharacterAttackDataCache::class,
            AssignNewFactionsToCharacters::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
