<?php

namespace App\Flare\ExponentialCurve\Providers;

use App\Flare\ExponentialCurve\Curve\ExponentialAttributeCurve;
use App\Flare\ExponentialCurve\Curve\ExponentialLevelCurve;
use App\Flare\ExponentialCurve\Curve\LinearAttributeCurve;
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

        $this->app->bind(LinearAttributeCurve::class, function () {
            return new LinearAttributeCurve;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
