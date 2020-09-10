<?php

namespace App\Game\Core\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use League\Fractal\Manager;
use App\Game\Core\Comparison\ItemComparison;
use App\Game\Core\Middleware\IsCharacterAdventuringMiddleware;
use App\Game\Core\Services\AdventureRewardService;
use App\Game\Core\Services\CharacterService;
use App\Game\Core\Services\CraftingSkillService;
use App\Game\Core\Services\EquipItemService;

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
            return new Manager();
        });

        $this->app->bind(EquipItemService::class, function($app) {
            return new EquipItemService();
        });

        $this->app->bind(ItemComparison::class, function($app) {
            return new ItemComparison();
        });

        $this->app->bind(CraftingSkillService::class, function($app) {
            return new CraftingSkillService();
        });

        $this->app->bind(AdventureRewardService::class, function($app) {
            return new AdventureRewardService(new CharacterService);
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
