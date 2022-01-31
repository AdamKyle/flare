<?php

namespace App\Game\Battle\Providers;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Game\Battle\Console\Commands\ClearCelestials;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Handlers\FactionHandler;
use App\Game\Battle\Jobs\BattleAttackHandler;
use App\Game\Battle\Services\BattleRewardProcessing;
use App\Game\Battle\Services\CelestialFightService;
use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Services\GoldRush;
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

        $this->app->bind(FactionHandler::class, function($app) {
            return new FactionHandler(
                $app->make(RandomAffixGenerator::class)
            );
        });

        $this->app->bind(GoldRush::class, function($app) {
            return new GoldRush();
        });

        $this->app->bind(BattleRewardProcessing::class, function($app) {
            return new BattleRewardProcessing(
                $app->make(FactionHandler::class),
                $app->make(CharacterRewardService::class),
                $app->make(GoldRush::class),
            );
        });

        $this->app->bind(BattleEventHandler::class, function($app) {
            return new BattleEventHandler(
                $app->make(Manager::class),
                $app->make(CharacterAttackTransformer::class),
                $app->make(BattleRewardProcessing::class),
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
