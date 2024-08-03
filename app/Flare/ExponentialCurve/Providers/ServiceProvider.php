<?php

namespace App\Flare\ExponentialCurve\Providers;

use App\Flare\ExponentialCurve\Curve\ExponentialAttributeCurve;
use App\Flare\ExponentialCurve\Curve\ExponentialLevelCurve;
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

        $this->app->bind(ExponentialLevelCurve::class, function () {
            return new ExponentialLevelCurve;
        });

        $this->app->bind(ExponentialAttributeCurve::class, function () {
            return new ExponentialAttributeCurve;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
