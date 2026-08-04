<?php

namespace App\Game\Character\Providers;

use App\Game\Character\CharacterCreation\Pipeline\CharacterCreationPipeline;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use App\Game\Character\Console\Commands\AssignNewFactionsToCharacters;
use App\Game\Character\Console\Commands\CreateCharacterAttackDataCache;
use App\Game\Character\Services\CharacterDeletion;
use App\Game\Kingdoms\Handlers\GiveKingdomsToNpcHandler;
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
        $this->app->bind(CharacterDeletion::class, fn ($app) => new CharacterDeletion(
            $app->make(GiveKingdomsToNpcHandler::class),
            $app->make(CharacterCreationPipeline::class),
            $app->make(CharacterBuildState::class),
        ));

        $this->commands([
            CreateCharacterAttackDataCache::class,
            AssignNewFactionsToCharacters::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
