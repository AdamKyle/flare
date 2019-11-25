<?php

namespace App\Game\Battle\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use League\Fractal\Manager;
use App\Game\Battle\Services\CharacterService;
use App\Game\Battle\Values\LevelUpValue;

class ServiceProvider extends ApplicationServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Manager::class, function ($app) {
            return new Manager();
        });

        $this->app->singleton(CharacterService::class, function($app) {
            return new CharacterService();
        });

        $this->app->singleton(LevelUpValue::class, function($app) {
            return new LevelUpValue();
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

    public function provides()
    {
        return [
            Manager::class,
            CharacterService::class,
            LevelUpValue::class,
        ];
    }
}
