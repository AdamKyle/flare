<?php

namespace App\Flare\ImageGeneration\Providers;

use App\Flare\Github\Components\ReleaseNote;
use App\Flare\ImageGeneration\Commands\Console\GenerateMonsterImages;
use App\Flare\ImageGeneration\DeepAi\DeepAiImageGeneration;
use App\Flare\ImageGeneration\Services\DeepAiImageTextGenerationService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->commands([
            GenerateMonsterImages::class,
        ]);

        $this->app->bind(DeepAiImageGeneration::class, function () {
            return new DeepAiImageGeneration();
        });

        $this->app->bind(DeepAiImageTextGenerationService::class, function ($app) {
            return new DeepAiImageTextGenerationService(
                $app->make(DeepAiImageGeneration::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        // Release Notes Blade Component
        Blade::component('release-note', ReleaseNote::class);
    }
}
