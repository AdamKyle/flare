<?php

namespace App\Game\Quests\Providers;

use App\Admin\Services\QuestService;
use App\Game\Maps\Validation\CanTravelToMap;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Quests\Handlers\NpcQuestRewardHandler;
use App\Game\Quests\Handlers\NpcQuestsHandler;
use App\Game\Quests\Services\QuestHandlerService;
use App\Game\Raids\Services\RaidEventService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(RaidEventService::class, function() {
            return new RaidEventService();
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
