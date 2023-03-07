<?php

namespace App\Flare\Github\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Flare\Github\Commands\GetReleaseData;
use App\Flare\Github\Components\ReleaseNote;
use App\Flare\Github\Services\Github;
use App\Flare\Github\Services\Markdown;

class ServiceProvider extends ApplicationServiceProvider {

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void {
        $this->commands([
            GetReleaseData::class
        ]);

        $this->app->bind(Github::class, function() {
            return new Github();
        });

        $this->app->bind(Markdown::class, function() {
            return new Markdown();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void {

        // Release Notes Blade Component
        Blade::component('release-note', ReleaseNote::class);
    }
}
