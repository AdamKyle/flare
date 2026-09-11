<?php

namespace App\Game\Core\Providers;

use App\Game\Battle\Services\BattleDrop;
use App\Game\Character\CharacterCreation\Calculators\BaseStatCalculator;
use App\Game\Core\Items\Builders\BuildMythicItem;
use App\Game\Core\Services\CharacterPassiveSkills;
use App\Game\Core\Services\CharactersOnline;
use App\Game\Core\Services\CharacterStatRepairService;
use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Services\GoldRush;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use League\Fractal\Manager;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Manager::class, function ($app) {
            return new Manager;
        });

        $this->app->bind(CharacterPassiveSkills::class, function () {
            return new CharacterPassiveSkills;
        });

        $this->app->bind(GoldRush::class, function () {
            return new GoldRush;
        });

        $this->app->bind(DropCheckService::class, function ($app) {
            return new DropCheckService(
                $app->make(BattleDrop::class),
                $app->make(BuildMythicItem::class),
                $app->make(CharacterAreaGemEffectService::class),
            );
        });

        $this->app->bind(CharactersOnline::class, function ($app) {
            return new CharactersOnline;
        });

        $this->app->bind(CharacterStatRepairService::class, function ($app) {
            return new CharacterStatRepairService(
                $app->make(BaseStatCalculator::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
