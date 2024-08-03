<?php

namespace App\Flare\MapGenerator\Providers;

use App\Flare\MapGenerator\Builders\ImageBuilder;
use App\Flare\MapGenerator\Builders\MapBuilder;
use App\Flare\MapGenerator\Console\Commands\CreateMap;
use ChristianEssl\LandmapGeneration\Settings\MapSettings;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(MapSettings::class, function ($app) {
            return new MapSettings;
        });

        $this->app->singleton(MapBuilder::class, function ($app) {
            return new MapBuilder($app->make(MapSettings::class), $app->make(ImageBuilder::class));
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
                CreateMap::class,
            ]);
        }
    }

    public function provides()
    {
        return [
            MapSettings::class,
            MapBuilder::class,
        ];
    }
}
