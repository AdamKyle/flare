<?php

namespace App\Game\Reincarnate\Providers;

use App\Flare\Values\BaseStatValue;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Reincarnate\Services\CharacterReincarnationService;
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
        $this->app->bind(CharacterReincarnationService::class, function ($app) {
            return new CharacterReincarnationService(
                $app->make(UpdateCharacterAttackTypesHandler::class),
                $app->make(BaseStatValue::class)
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
        //
    }
}
