<?php

namespace App\Game\Messages\Providers;


use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\MonsterTransfromer;
use App\Game\Messages\Handlers\NpcKingdomHandler;
use App\Game\Messages\Handlers\NpcQuestRewardHandler;
use App\Game\Messages\Handlers\NpcQuestsHandler;
use App\Game\Messages\Services\NpcCommandService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Messages\Console\Commands\CleanChat;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Handlers\NpcCommandHandler;
use League\Fractal\Manager;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        $this->commands([CleanChat::class]);

        $this->app->bind(NpcCommandService::class, function($app) {
            return new NpcCommandService(
                $app->make(NpcKingdomHandler::class),
                $app->make(NpcServerMessageBuilder::class),
            );
        });

        $this->app->bind(NpcKingdomHandler::class, function($app) {
            return new NpcKingdomHandler(
                $app->make(NpcServerMessageBuilder::class)
            );
        });

        $this->app->bind(NpcCommandHandler::class, function($app) {
            return new NpcCommandHandler(
                $app->make(NpcServerMessageBuilder::class),
                $app->make(NpcQuestsHandler::class),
                $app->make(NpcKingdomHandler::class),
            );
        });

        $this->app->bind(NpcQuestsHandler::class, function($app) {
            return new NpcQuestsHandler(
                $app->make(NpcServerMessageBuilder::class),
                $app->make(NpcQuestRewardHandler::class),
            );
        });

        $this->app->bind(NpcQuestRewardHandler::class, function($app) {
            return new NpcQuestRewardHandler(
                $app->make(NpcServerMessageBuilder::class),
                $app->make(CharacterSheetBaseInfoTransformer::class),
                $app->make(BuildCharacterAttackTypes::class),
                $app->make(Manager::class)
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
