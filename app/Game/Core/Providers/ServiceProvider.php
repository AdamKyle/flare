<?php

namespace App\Game\Core\Providers;

use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\Serializers\CoreSerializer;
use App\Game\Core\Services\InventorySetService;
use App\Game\Core\Services\UseItemService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use League\Fractal\Manager;
use App\Game\Core\Comparison\ItemComparison;
use App\Game\Core\Middleware\IsCharacterAdventuringMiddleware;
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

        $this->app->bind(EquipItemService::class, function($app) {
            return new EquipItemService($app->make(Manager::class), $app->make(CharacterAttackTransformer::class), $app->make(InventorySetService::class));
        });

        $this->app->bind(ItemComparison::class, function($app) {
            return new ItemComparison();
        });

        $this->app->bind(AdventureRewardService::class, function($app) {
            return new AdventureRewardService(new CharacterService);
        });

        $this->app->bind(CharacterInventoryService::class, function($app) {
            return new CharacterInventoryService();
        });

        $this->app->bind(ShopService::class, function($app) {
            return new ShopService();
        });

        $this->app->bind(UseItemService::class, function($app) {
            return new UseItemService(
                $app->make(Manager::class),
                $app->make(CharacterAttackTransformer::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('is.character.adventuring', IsCharacterAdventuringMiddleware::class);
    }
}
