<?php

namespace App\Game\Messages\Providers;

use App\Game\Messages\Services\NpcCommandService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Messages\Console\Commands\CleanChat;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Handlers\NpcCommandHandler;

class ServiceProvider extends ApplicationServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        $this->commands([CleanChat::class]);

        $this->app->bind(NpcCommandService::class, function($app) {
            return new NpcCommandService(
                $app->make(NpcServerMessageBuilder::class),
            );
        });

        $this->app->bind(NpcCommandHandler::class, function($app) {
            return new NpcCommandHandler(
                $app->make(NpcServerMessageBuilder::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        //
    }
}
