<?php

namespace App\Game\Messages\Providers;

use App\Flare\Handlers\MessageThrottledHandler;
use App\Game\Maps\Services\PctService;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Console\Commands\CleanChat;
use App\Game\Messages\Factories\AssignMessageType;
use App\Game\Messages\Handlers\ServerMessageHandler;
use App\Game\Messages\Services\FetchMessages;
use App\Game\Messages\Services\PrivateMessage;
use App\Game\Messages\Services\PublicEntityCommand;
use App\Game\Messages\Services\PublicMessage;
use App\Game\Messages\Services\ServerMessage;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->commands([CleanChat::class]);

        $this->app->bind(FetchMessages::class, function () {
            return new FetchMessages;
        });

        $this->app->bind(PublicMessage::class, function () {
            return new PublicMessage;
        });

        $this->app->bind(PrivateMessage::class, function () {
            return new PrivateMessage;
        });

        $this->app->bind(ServerMessage::class, function ($app) {
            return new ServerMessage(
                $app->make(MessageThrottledHandler::class),
                $app->make(ServerMessageBuilder::class)
            );
        });

        $this->app->bind(PublicEntityCommand::class, function ($app) {
            return new PublicEntityCommand(
                $app->make(PctService::class),
            );
        });

        $this->app->bind(ServerMessageHandler::class, function ($app) {
            return new ServerMessageHandler(
                $app->make(ServerMessageBuilder::class)
            );
        });

        $this->app->bind(AssignMessageType::class, function () {
            return new AssignMessageType();
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
