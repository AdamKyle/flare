<?php

namespace App\Game\Character\CharacterCreation\Providers;

use App\Flare\Values\BaseSkillValue;
use App\Flare\Values\BaseStatValue;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterCreation\Pipeline\Steps\BuildCache;
use App\Game\Character\CharacterCreation\Pipeline\Steps\CharacterCreator;
use App\Game\Character\CharacterCreation\Pipeline\Steps\SkillAssigner;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CharacterBuildState::class, function () {
            return new CharacterBuildState;
        });

        $this->app->bind(CharacterCreator::class, function ($app) {
            return new CharacterCreator(
                $app->make(BaseStatValue::class),
            );
        });

        $this->app->bind(SkillAssigner::class, function ($app) {
            return new SkillAssigner(
                $app->make(BaseSkillValue::class),
            );
        });

        $this->app->bind(BuildCache::class, function ($app) {
            return new BuildCache(
                $app->make(BuildCharacterAttackTypes::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
