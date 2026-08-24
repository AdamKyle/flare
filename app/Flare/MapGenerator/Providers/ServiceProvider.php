<?php

namespace App\Flare\MapGenerator\Providers;

use App\Flare\MapGenerator\Builders\ImageBuilder;
use App\Flare\MapGenerator\Builders\MapBuilder;
use App\Flare\MapGenerator\Console\Commands\BreakMapsIntoPieces;
use App\Flare\MapGenerator\Console\Commands\CreateMap;
use App\Flare\MapGenerator\Contracts\LandMapImageFactory;
use App\Flare\MapGenerator\Contracts\MapPixelReaderFactory;
use App\Flare\MapGenerator\Services\ImageTilerService;
use App\Flare\MapGenerator\Services\MapTileGenerationService;
use App\Flare\MapGenerator\Support\GdLandMapImageFactory;
use App\Flare\MapGenerator\Support\GdMapPixelReaderFactory;
use App\Flare\MapGenerator\Support\GdPngImageWriter;
use ChristianEssl\LandmapGeneration\Settings\MapSettings;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use Intervention\Image\Drivers\Gd\Driver as GDDriver;
use Intervention\Image\ImageManager;

class ServiceProvider extends ApplicationServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(MapSettings::class, function () {
            return new MapSettings;
        });

        $this->app->bind(GdPngImageWriter::class, function () {
            return new GdPngImageWriter;
        });

        $this->app->bind(LandMapImageFactory::class, GdLandMapImageFactory::class);

        $this->app->bind(MapPixelReaderFactory::class, GdMapPixelReaderFactory::class);

        $this->app->bind(ImageBuilder::class, function ($app) {
            return new ImageBuilder($app->make(GdPngImageWriter::class));
        });

        $this->app->singleton(MapBuilder::class, function ($app) {
            return new MapBuilder($app->make(MapSettings::class), $app->make(ImageBuilder::class), $app->make(LandMapImageFactory::class));
        });

        $this->app->bind(GDDriver::class, function () {
            return new GDDriver;
        });

        $this->app->bind(ImageManager::class, function ($app) {
            return new ImageManager($app->make(GDDriver::class));
        });

        $this->app->bind(ImageTilerService::class, function ($app) {
            return new ImageTilerService($app->make(ImageManager::class));
        });

        $this->app->bind(MapTileGenerationService::class, function ($app) {
            return new MapTileGenerationService($app->make(ImageTilerService::class));
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
                BreakMapsIntoPieces::class,
            ]);
        }
    }

    public function provides()
    {
        return [
            MapSettings::class,
            MapBuilder::class,
            MapTileGenerationService::class,
            MapPixelReaderFactory::class,
            LandMapImageFactory::class,
            GdPngImageWriter::class,
            ImageBuilder::class,
            ImageTilerService::class,
        ];
    }
}
