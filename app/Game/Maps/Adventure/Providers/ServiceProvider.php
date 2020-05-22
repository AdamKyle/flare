<?php

namespace App\Game\Maps\Adventure\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Maps\Adventure\Services\PortService;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\Maps\Values\MapPositionValue;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(PortService::class, function($app) {
            return new PortService($app->make(DistanceCalculation::class), $app->make(MapPositionValue::class));
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
