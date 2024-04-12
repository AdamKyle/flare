<?php

namespace App\Game\Character\Providers;

use App\Game\Character\Console\Commands\AssignNewFactionsToCharacters;
use App\Game\Character\Console\Commands\AssignNewSkillsToPlayers;
use App\Game\Character\Console\Commands\CreateCharacterAttackDataCache;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider {

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        $this->commands([
            CreateCharacterAttackDataCache::class,
            AssignNewFactionsToCharacters::class,
            AssignNewSkillsToPlayers::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
    }
}
