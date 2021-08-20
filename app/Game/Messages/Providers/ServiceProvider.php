<?php

namespace App\Game\Messages\Providers;


use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\MonsterTransfromer;
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

        $this->app->bind(NpcCommandHandler::class, function($app) {
            return new NpcCommandHandler(
                $app->make(NpcServerMessageBuilder::class),
                $app->make(CharacterAttackTransformer::class),
                $app->make(MonsterTransfromer::class),
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
