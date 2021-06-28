<?php

namespace App\Game\Battle\Providers;

use App\Flare\Transformers\CharacterAttackTransformer;
use App\Game\Battle\Console\Commands\ClearCelestials;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\CelestialFightService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use League\Fractal\Manager;
use App\Flare\Transformers\CharacterSheetTransformer;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Battle\Services\ConjureService;
use App\Game\Messages\Builders\NpcServerMessageBuilder;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ConjureService::class, function($app) {
            return new ConjureService(
                $app->make(Manager::class),
                $app->make(KingdomTransformer::class),
                $app->make(CharacterSheetTransformer::class),
                $app->make(NpcServerMessageBuilder::class),
            );
        });

        $this->app->bind(BattleEventHandler::class, function($app) {
            return new BattleEventHandler(
                $app->make(Manager::class),
                $app->make(CharacterAttackTransformer::class)
            );
        });

        $this->app->bind(CelestialFightService::class, function($app) {
            return new CelestialFightService($app->make(BattleEventHandler::class));
        });

        $this->commands([
            ClearCelestials::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // ...
    }
}
