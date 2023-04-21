<?php

namespace App\Game\Reincarnate\Providers;

use App\Admin\Services\QuestService;
use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Game\Maps\Validation\CanTravelToMap;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Quests\Handlers\NpcQuestRewardHandler;
use App\Game\Quests\Handlers\NpcQuestsHandler;
use App\Game\Quests\Services\QuestHandlerService;
use App\Game\Reincarnate\Services\CharacterReincarnateService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(CharacterReincarnateService::class, function($app) {
            return new CharacterReincarnateService(
                $app->make(UpdateCharacterAttackTypes::class)
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
