<?php

namespace App\Flare\GemWorldGeneration\Providers;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\GemWorldGeneration\Services\GemWorldGenerationService;
use App\Flare\GemWorldGeneration\Services\GemWorldImageGenerator;
use App\Flare\GemWorldGeneration\Services\GemWorldLocationPlacementService;
use App\Flare\GemWorldGeneration\Services\GemWorldPlaneGenerationSettings;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GemWorldPlaneGenerationSettings::class, function () {
            return new GemWorldPlaneGenerationSettings();
        });

        $this->app->bind(GemWorldImageGenerator::class, function ($app) {
            return new GemWorldImageGenerator($app->make(GemWorldPlaneGenerationSettings::class));
        });

        $this->app->bind(GemWorldLocationPlacementService::class, function ($app) {
            return new GemWorldLocationPlacementService(
                $app->make(CoordinatesCache::class),
                $app->make(GemWorldPlaneGenerationSettings::class),
            );
        });

        $this->app->bind(GemWorldGenerationService::class, function ($app) {
            return new GemWorldGenerationService(
                $app->make(GemWorldImageGenerator::class),
                $app->make(GemWorldLocationPlacementService::class),
            );
        });
    }
}
