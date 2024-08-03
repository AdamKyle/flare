<?php

namespace App\Game\Quests\Providers;

use App\Game\Maps\Validation\CanTravelToMap;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Quests\Console\Commands\CreateQuestCache;
use App\Game\Quests\Handlers\NpcQuestRewardHandler;
use App\Game\Quests\Handlers\NpcQuestsHandler;
use App\Game\Quests\Services\BuildQuestCacheService;
use App\Game\Quests\Services\QuestHandlerService;
use App\Game\Quests\Transformers\QuestTransformer;
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

        $this->commands([
            CreateQuestCache::class,
        ]);

        $this->app->bind(NpcQuestsHandler::class, function ($app) {
            return new NpcQuestsHandler(
                $app->make(NpcServerMessageBuilder::class),
                $app->make(NpcQuestRewardHandler::class),
            );
        });

        $this->app->bind(NpcQuestRewardHandler::class, function ($app) {
            return new NpcQuestRewardHandler(
                $app->make(NpcServerMessageBuilder::class),
            );
        });

        $this->app->bind(QuestHandlerService::class, function ($app) {
            return new QuestHandlerService(
                $app->make(NpcQuestsHandler::class),
                $app->make(CanTravelToMap::class),
                $app->make(MapTileValue::class),
                $app->make(BuildQuestCacheService::class)
            );
        });

        $this->app->bind(BuildQuestCacheService::class, function ($app) {
            return new BuildQuestCacheService(
                $app->make(QuestTransformer::class),
                $app->make(Manager::class),
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
