<?php

namespace App\Game\Core\Providers;

use App\Flare\Builders\BuildMythicItem;
use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Services\CharacterXPService;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\InventoryTransformer;
use App\Flare\Transformers\Serializers\CoreSerializer;
use App\Flare\Transformers\UsableItemTransformer;
use App\Game\Battle\Services\BattleDrop;
use App\Game\Core\Handlers\HandleGoldBarsAsACurrency;
use App\Game\Core\Services\CharacterPassiveSkills;
use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Services\HolyItemService;
use App\Game\Core\Services\InventorySetService;
use App\Game\Core\Services\RandomEnchantmentService;
use App\Game\Core\Services\SeerService;
use App\Game\Core\Services\UseItemService;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Skills\Services\MassDisenchantService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use League\Fractal\Manager;
use App\Game\Core\Comparison\ItemComparison;
use App\Game\Core\Middleware\IsCharacterAtLocationMiddleware;
use App\Game\Core\Middleware\IsCharacterWhoTheySayTheyAre;
use App\Game\Core\Services\AdventureRewardService;
use App\Game\Core\Services\CharacterInventoryService;
use App\Game\Core\Services\CharacterService;
use App\Game\Core\Services\CraftingSkillService;
use App\Game\Core\Services\EquipItemService;
use App\Game\Core\Services\ShopService;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Manager::class, function($app) {
            $manager = new Manager();

            // Attach the serializer
            $manager->setSerializer(new CoreSerializer());

            return $manager;
        });

        $this->app->bind(InventorySetService::class, function($app) {
            return new InventorySetService();
        });

        $this->app->bind(CharacterPassiveSkills::class, function() {
            return new CharacterPassiveSkills();
        });

        $this->app->bind(DropCheckService::class, function($app) {
            return new DropCheckService(
                $app->make(BattleDrop::class),
                $app->make(BuildMythicItem::class)
            );
        });

        $this->app->bind(EquipItemService::class, function($app) {
            return new EquipItemService($app->make(Manager::class), $app->make(CharacterAttackTransformer::class), $app->make(InventorySetService::class));
        });

        $this->app->bind(ItemComparison::class, function($app) {
            return new ItemComparison();
        });

        $this->app->bind(CharacterInventoryService::class, function($app) {
            return new CharacterInventoryService(
                $app->make(InventoryTransformer::class),
                $app->make(UsableItemTransformer::class),
                $app->make(MassDisenchantService::class),
                $app->make(UpdateCharacterSkillsService::class),
                $app->make(Manager::class)
            );
        });

        $this->app->bind(UseItemService::class, function($app) {
            return new UseItemService(
                $app->make(Manager::class),
                $app->make(CharacterSheetBaseInfoTransformer::class),
            );
        });

        $this->app->bind(RandomEnchantmentService::class, function($app) {
            return new RandomEnchantmentService(
                $app->make(RandomAffixGenerator::class)
            );
        });

        $this->app->bind(HolyItemService::class, function() {
            return new HolyItemService();
        });

        $this->app->bind(HandleGoldBarsAsACurrency::class, function($app) {
            return new HandleGoldBarsAsACurrency($app->make(UpdateKingdomHandler::class));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
