<?php

namespace App\Game\Survey\Providers;

use App\Flare\Values\RandomAffixDetails;
use App\Game\Survey\Console\Commands\StartSurvey;
use App\Game\Survey\Services\SurveyService;
use App\Game\Survey\Validator\SurveyValidator;
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

        $this->app->bind(SurveyValidator::class, function() {
            return new SurveyValidator;
        });

        $this->app->bind(SurveyService::class, function ($app) {
            return new SurveyService(
                $app->make(SurveyValidator::class),
                $app->make(RandomAffixDetails::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
