<?php

namespace App\Game\Battle\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Battle\Services\CharacterService;
use App\Game\Battle\Values\LevelUpValue;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
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
        //
    }
}
