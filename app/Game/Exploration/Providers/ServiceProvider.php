<?php

namespace App\Game\Exploration\Providers;


use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Exploration\Middleware\IsCharacterExploring;
use App\Game\Exploration\Services\ExplorationAutomationService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {


        $this->app->bind(ExplorationAutomationService::class, function($app) {
            return new ExplorationAutomationService(
                $app->make(MonsterPlayerFight::class),
                $app->make(BattleEventHandler::class),
                $app->make(CharacterCacheData::class)
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

        $router->aliasMiddleware('is.character.exploring', IsCharacterExploring::class);
    }
}
