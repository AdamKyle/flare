<?php

namespace App\Game\Exploration\Providers;

use App\Game\Automation\Services\ExplorationAutomationService;
use App\Game\Automation\Services\ExplorationCreatureCountCalculator;
use App\Game\Automation\Services\ExplorationLogService;
use App\Game\Automation\Services\ExplorationWarningService;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Exploration\Middleware\IsCharacterExploring;
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

        $this->app->bind(ExplorationAutomationService::class, function ($app) {
            return new ExplorationAutomationService(
                $app->make(CharacterCacheData::class),
                $app->make(ExplorationCreatureCountCalculator::class),
                $app->make(ExplorationLogService::class),
                $app->make(ExplorationWarningService::class),
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
