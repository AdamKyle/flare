<?php

namespace App\Game\Automation\Providers;

use App\Game\Automation\Services\DelveExplorationAutomationService;
use App\Game\Automation\Services\FactionLoyaltyAutomationService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Automation\Middleware\IsCharacterExploring;
use App\Game\Automation\Services\ExplorationAutomationService;

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
                $app->make(CharacterCacheData::class)
            );
        });

        $this->app->bind(DelveExplorationAutomationService::class, function ($app) {
            return new DelveExplorationAutomationService(
                $app->make(CharacterCacheData::class)
            );
        });

        $this->app->bind(FactionLoyaltyAutomationService::class, function ($app) {
            return new FactionLoyaltyAutomationService();
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
