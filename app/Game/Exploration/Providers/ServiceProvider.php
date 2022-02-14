<?php

namespace App\Game\Exploration\Providers;


use App\Flare\Builders\Character\ClassDetails\ClassBonuses;
use App\Flare\Services\FightService;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Handlers\FactionHandler;
use App\Game\Exploration\Console\Commands\ClearExplorationTimeOuts;
use App\Game\Exploration\Handlers\ExplorationHandler;
use App\Game\Exploration\Handlers\FightHandler;
use App\Game\Exploration\Handlers\PlunderHandler;
use App\Game\Exploration\Handlers\RewardHandler;
use App\Game\Exploration\Jobs\Exploration;
use App\Game\Exploration\Middleware\IsCharacterExploring;
use App\Game\Exploration\Services\EncounterService;
use App\Game\Exploration\Services\ExplorationAutomationService;
use App\Game\Exploration\Services\ProcessExplorationFightService;
use App\Game\Skills\Services\SkillService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {

        $this->app->bind(ProcessExplorationFightService::class, function($app) {
            return new ProcessExplorationFightService(
                $app->make(FightService::class),
                $app->make(BattleEventHandler::class),
                $app->make(ClassBonuses::class),
            );
        });

        $this->app->bind(ExplorationAutomationService::class, function($app) {
            return new ExplorationAutomationService(
                $app->make(SkillService::class)
            );
        });

        $this->app->bind(ExplorationHandler::class, function($app) {
            return new ExplorationHandler(
                $app->make(ProcessExplorationFightService::class)
            );
        });

        $this->app->bind(FightHandler::class, function($app) {
            return new FightHandler(
                $app->make(ProcessExplorationFightService::class)
            );
        });

        $this->app->bind(PlunderHandler::class, function($app) {
            return new PlunderHandler(
                $app->make(FightHandler::class)
            );
        });

        $this->app->bind(RewardHandler::class, function($app) {
            return new RewardHandler($app->make(FactionHandler::class));
        });

        $this->app->bind(EncounterService::class, function($app) {
           return new EncounterService(
               $app->make(ExplorationHandler::class),
               $app->make(FightHandler::class),
               $app->make(PlunderHandler::class),
               $app->make(RewardHandler::class)
           );
        });

        $this->commands([ClearExplorationTimeOuts::class]);
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
