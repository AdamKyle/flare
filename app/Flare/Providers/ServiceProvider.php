<?php

namespace App\Flare\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Flare\Values\BaseStatValue;
use App\Flare\Builders\CharacterBuilder;

class ServiceProvider extends ApplicationServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(BaseStatValue::class, function ($app) {
            return new BaseStatValue();
        });

        $this->app->singleton(CharacterBuilder::class, function ($app) {
            return new CharacterBuilder();
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

    public function provides()
    {
        return [
            BaseStatValue::class,
            CharacterBuilder::class,
        ];
    }
}
