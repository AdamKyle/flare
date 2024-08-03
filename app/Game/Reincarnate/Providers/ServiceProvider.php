<?php

namespace App\Game\Reincarnate\Providers;

use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Reincarnate\Services\CharacterReincarnateService;
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
        $this->app->bind(CharacterReincarnateService::class, function ($app) {
            return new CharacterReincarnateService(
                $app->make(UpdateCharacterAttackTypesHandler::class)
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
