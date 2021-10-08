<?php

namespace App\Game\Adventures\Providers;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Services\FightService;
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

        $this->app->bind(AdventureFightService::class, function($app) {
            return new AdventureFightService(
                $app->make(CharacterInformationBuilder::class),
                $app->make(FightService::class),
                $app->make(RewardBuilder::class),
            );
        });

        $this->app->bind(AdventureService::class, function($app) {
            return new AdventureService(
                $app->make(AdventureFightService::class)
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
