<?php

namespace App\Game\Maps\Providers;

use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\Maps\Values\MapPositionValue;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;


class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(DistanceCalculation::class, function($app) {
            return new DistanceCalculation();
        });

        $this->app->bind(MapPositionValue::class, function($app) {
            return new MapPositionValue();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    public function provides()
    {
        return [];
    }
}
