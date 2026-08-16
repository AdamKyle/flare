<?php

namespace App\Game\Automation\Exploration\Providers;

use App\Game\Automation\Exploration\Middleware\IsCharacterExploring;
use App\Game\Automation\Exploration\Services\ExplorationAutomationService;
use App\Game\Automation\Exploration\Services\ExplorationCreatureCountCalculator;
use App\Game\Automation\Exploration\Services\ExplorationLogService;
use App\Game\Automation\Exploration\Services\ExplorationWarningService;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register the Exploration automation services.
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
     * Register the Exploration automation middleware alias.
     *
     * @return void
     */
    public function boot()
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('is.character.exploring', IsCharacterExploring::class);
    }
}
