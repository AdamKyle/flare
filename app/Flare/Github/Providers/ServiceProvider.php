<?php

namespace App\Flare\Github\Providers;

use App\Flare\Github\Commands\GetReleaseData;
use App\Flare\Github\Components\ReleaseNote;
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
            GetReleaseData::class,
        ]);
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
