<?php

namespace App\Game\Gems\Providers;

use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Transformers\CharacterGemsTransformer;
use App\Game\Gems\Contracts\CharacterGemEffects;
use App\Game\Gems\Progression\Contracts\CharacterAreaGemEffects;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Services\AttachedGemService;
use App\Game\Gems\Services\CharacterGemEffectService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use League\Fractal\Manager;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(AttachedGemService::class, function ($app) {
            return new AttachedGemService(
                $app->make(CharacterGemsTransformer::class),
                $app->make(Manager::class),
                $app->make(PlainDataSerializer::class),
                $app->make(CharacterInventoryService::class)
            );
        });

        $this->app->bind(CharacterAreaGemEffects::class, CharacterAreaGemEffectService::class);
        $this->app->bind(CharacterGemEffects::class, CharacterGemEffectService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void {}
}
