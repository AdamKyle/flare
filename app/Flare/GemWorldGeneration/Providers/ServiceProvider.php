<?php

namespace App\Flare\GemWorldGeneration\Providers;

use App\Flare\GemWorldGeneration\Services\GemWorldGenerationService;
use App\Flare\GemWorldGeneration\Services\GemWorldImageGenerator;
use App\Flare\GemWorldGeneration\Services\GemWorldLocationPlacementService;
use App\Flare\GemWorldGeneration\Services\GemWorldPlaneGenerationSettings;
use App\Flare\GemWorldGeneration\Values\GemWorldGenerationConfig;
use App\Flare\MapGenerator\Contracts\LandMapImageFactory;
use App\Flare\MapGenerator\Contracts\MapPixelReaderFactory;
use App\Flare\MapGenerator\Services\MapTileGenerationService;
use App\Flare\MapGenerator\Support\GdPngImageWriter;
use App\Game\Maps\Contracts\CoordinatesQuery;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GemWorldPlaneGenerationSettings::class, function () {
            return new GemWorldPlaneGenerationSettings();
        });

        $this->app->bind(GemWorldGenerationConfig::class, function () {
            return GemWorldGenerationConfig::fromConfig();
        });

        $this->app->bind(GemWorldImageGenerator::class, function ($app) {
            return new GemWorldImageGenerator(
                $app->make(GemWorldPlaneGenerationSettings::class),
                $app->make(LandMapImageFactory::class),
                $app->make(GdPngImageWriter::class),
                $app->make(GemWorldGenerationConfig::class),
            );
        });

        $this->app->bind(GemWorldLocationPlacementService::class, function ($app) {
            return new GemWorldLocationPlacementService(
                $app->make(CoordinatesQuery::class),
                $app->make(GemWorldPlaneGenerationSettings::class),
                $app->make(MapPixelReaderFactory::class),
                $app->make(GemWorldGenerationConfig::class),
            );
        });

        $this->app->bind(GemWorldGenerationService::class, function ($app) {
            return new GemWorldGenerationService(
                $app->make(GemWorldImageGenerator::class),
                $app->make(GemWorldLocationPlacementService::class),
                $app->make(MapTileGenerationService::class),
            );
        });
    }
}
