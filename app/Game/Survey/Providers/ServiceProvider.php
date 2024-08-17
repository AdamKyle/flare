<?php

namespace App\Game\Survey\Providers;

use App\Game\Survey\Console\Commands\StartSurvey;
use App\Game\Survey\Services\SurveyService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->commands([
            StartSurvey::class,
        ]);

        $this->app->bind(SurveyService::class, function () {
            return new SurveyService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
