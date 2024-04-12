<?php

namespace App\Game\Character\CharacterCreation\Providers;

use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterCreation\Services\CharacterBuilderService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void {
        $this->app->bind(CharacterBuilderService::class, function ($app) {
            return new CharacterBuilderService(
                $app->make(BuildCharacterAttackTypes::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void {}
}
