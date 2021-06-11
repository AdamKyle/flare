<?php

namespace App\Game\Messages\Providers;


use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Messages\Console\Commands\CleanChat;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Handlers\NpcCommandHandler;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        $this->commands([CleanChat::class]);

        $this->app->bind(NpcCommandHandler::class, function() {
            return new NpcCommandHandler(new NpcServerMessageBuilder);
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
