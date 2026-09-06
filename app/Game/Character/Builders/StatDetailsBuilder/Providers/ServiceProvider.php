<?php

namespace App\Game\Character\Builders\StatDetailsBuilder\Providers;

use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\Builders\StatDetailsBuilder\StatModifierDetails;
use App\Game\Gems\Services\AreaGemEffectService;
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

        $this->app->bind(StatModifierDetails::class, function ($app) {
            return new StatModifierDetails(
                $app->make(CharacterStatBuilder::class),
                $app->make(AreaGemEffectService::class),
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
