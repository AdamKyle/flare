<?php

namespace App\Flare\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Flare\Values\BaseStatValue;
use App\Flare\Builders\CharacterBuilder;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Console\Commands\CreateAdminAccount;
use App\Flare\Transformers\CharacterAttackTransformer;

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

        $this->app->singleton(CharacterInformationBuilder::class, function($app) {
            return new CharacterInformationBuilder();
        });

        $this->app->bind(CharacterAttackTransformer::class, function($app) {
            return new CharacterAttackTransformer();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateAdminAccount::class,
            ]);
        }
    }

    public function provides()
    {
        return [
            BaseStatValue::class,
            CharacterBuilder::class,
            CharacterInformationBuilder::class,
            CharacterAttackTransformer::class,
        ];
    }
}
