<?php

namespace App\Game\Adventures\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Adventures\Console\Commands\DeleteAdventureLogs;
use App\Game\Adventures\Builders\RewardBuilder;
use App\Game\Adventures\Services\AdventureFightService;
use App\Game\Adventures\Services\AdventureService;
use App\Game\Core\Services\CharacterService;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {


        $this->app->singleton(CharacterService::class, function($app) {
            return new CharacterService();
        });

        $this->app->bind(RewardBuilder::class, function($app) {
            return new RewardBuilder();
        });

        $this->app->bind(AdventureFightService::class, function($app, $parameters) {
            return new AdventureFightService($parameters['character'], $parameters['adventure']);
        });

        $this->app->bind(AdventureService::class, function($app, $parameters) {
            return new AdventureService(
                $parameters['character'],
                $parameters['adventure'],
                $parameters['rewardBuilder'],
                $parameters['name']
            );
        });

        $this->commands([DeleteAdventureLogs::class]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
