<?php

namespace App\Game\Core\Gems\Providers;

use App\Flare\Transformers\CharacterGemsTransformer;
use App\Game\Core\Gems\Services\AttachedGemService;
use App\Game\Core\Gems\Services\GemComparison;
use App\Game\Core\Middleware\IsCharacterAtLocationMiddleware;
use App\Game\Core\Middleware\IsCharacterWhoTheySayTheyAre;
use App\Game\Core\Services\AdventureRewardService;
use App\Game\Core\Services\CharacterInventoryService;
use App\Game\Core\Services\CraftingSkillService;
use App\Game\Core\Services\ShopService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use League\Fractal\Manager;

class ServiceProvider extends ApplicationServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {

        $this->app->bind(AttachedGemService::class, function($app) {
            return new AttachedGemService(
                $app->make(CharacterGemsTransformer::class),
                $app->make(Manager::class),
                $app->make(CharacterInventoryService::class)
            );
        });

        $this->app->bind(GemComparison::class, function($app) {
            return new GemComparison($app->make(CharacterGemsTransformer::class), $app->make(Manager::class));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
