<?php

namespace App\Game\Character\Builders\AttackBuilders\Providers;

use App\Flare\Transformers\CharacterAttackDataTransformer;
use App\Game\Character\Builders\AttackBuilders\AttackDetails\CharacterAttackBuilder;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
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
        $this->app->bind(CharacterCacheData::class, function ($app) {
            return new CharacterCacheData(
                $app->make(Manager::class),
                $app->make(CharacterAttackDataTransformer::class),
                $app->make(CharacterStatBuilder::class)
            );
        });

        $this->app->bind(BuildCharacterAttackTypes::class, function ($app) {
            return new BuildCharacterAttackTypes(
                $app->make(CharacterAttackBuilder::class),
                $app->make(CharacterCacheData::class)
            );
        });

        $this->app->bind(UpdateCharacterAttackTypesHandler::class, function ($app) {
            return new UpdateCharacterAttackTypesHandler($app->make(BuildCharacterAttackTypes::class));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
