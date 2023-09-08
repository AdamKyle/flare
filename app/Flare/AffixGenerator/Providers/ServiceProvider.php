<?php

namespace App\Flare\AffixGenerator\Providers;

use App\Flare\AffixGenerator\Builders\AffixBuilder;
use App\Flare\AffixGenerator\DTO\AffixGeneratorDTO;
use App\Flare\AffixGenerator\Generator\GenerateAffixes;
use App\Flare\ExponentialCurve\Curve\ExponentialLevelCurve;
use App\Flare\ExponentialCurve\Curve\ExponentialAttributeCurve;
use App\Flare\AffixGenerator\Console\Commands\MassGenerateAffixes;
use App\Flare\AffixGenerator\DTO\AffixCurveDTO;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider {

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(AffixGeneratorDTO::class, function() {
            return new AffixGeneratorDTO();
        });

        $this->app->bind(ExponentialLevelCurve::class, function() {
            return new ExponentialLevelCurve();
        });

        $this->app->bind(ExponentialAttributeCurve::class, function() {
            return new ExponentialAttributeCurve();
        });

        $this->app->bind(AffixCurveDTO::class, function() {
            return new AffixCurveDTO();
        });

        $this->app->bind(AffixBuilder::class, function() {
            return new AffixBuilder();
        });

        $this->app->bind(GenerateAffixes::class, function($app) {
            return new GenerateAffixes(
                $app->make(ExponentialAttributeCurve::class),
                $app->make(ExponentialLevelCurve::class),
                $app->make(AffixCurveDTO::class),
                $app->make(AffixBuilder::class),
            );
        });

        $this->commands([
            MassGenerateAffixes::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return voiduse App\Flare\AffixGenerator\Console\Commands\GenerateAffixes;
     */
    public function boot() {}
}
