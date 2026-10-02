<?php

namespace App\Game\Gems\Providers;

use App\Game\Gems\Contracts\CharacterGemDetails;
use App\Game\Gems\Contracts\CharacterGemEffects;
use App\Game\Gems\Contracts\CharacterGemRollFacts;
use App\Game\Gems\Contracts\GemAbilityDuplicateQuery;
use App\Game\Gems\Progression\Contracts\CharacterAreaGemEffects;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Services\CharacterGemDetailsService;
use App\Game\Gems\Services\CharacterGemEffectService;
use App\Game\Gems\Services\CharacterGemRollService;
use App\Game\Gems\Services\GemAbilityDuplicateService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Bind the Gems module's public contracts to their implementations.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(CharacterAreaGemEffects::class, CharacterAreaGemEffectService::class);
        $this->app->bind(CharacterGemEffects::class, CharacterGemEffectService::class);
        $this->app->bind(GemAbilityDuplicateQuery::class, GemAbilityDuplicateService::class);
        $this->app->bind(CharacterGemDetails::class, CharacterGemDetailsService::class);
        $this->app->bind(CharacterGemRollFacts::class, CharacterGemRollService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void {}
}
