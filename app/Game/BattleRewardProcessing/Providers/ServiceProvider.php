<?php

namespace App\Game\BattleRewardProcessing\Providers;

use App\Flare\Models\GlobalEventGoal;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Flare\Services\CharacterRewardService;
use App\Game\Battle\Handlers\FactionHandler;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\BattleRewardProcessing\Services\SecondaryRewardService;
use App\Game\ClassRanks\Services\ClassRankService;
use App\Game\Core\Services\GoldRush;
use App\Game\Mercenaries\Services\MercenaryService;

class ServiceProvider extends ApplicationServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {


        $this->app->bind(BattleRewardService::class, function ($app) {
            return new BattleRewardService(
                $app->make(FactionHandler::class),
                $app->make(CharacterRewardService::class),
                $app->make(GoldRush::class),
                $app->make(GlobalEventGoal::class)
            );
        });

        $this->app->bind(SecondaryRewardService::class, function ($app) {
            return new SecondaryRewardService(
                $app->make(MercenaryService::class),
                $app->make(ClassRankService::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
    }
}
