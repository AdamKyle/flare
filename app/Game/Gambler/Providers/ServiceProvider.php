<?php

namespace App\Game\Gambler\Providers;

use App\Game\Gambler\Handlers\SpinHandler;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Gambler\Services\GamblerService;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(SpinHandler::class, function() {
            return new SpinHandler();
        });

        $this->app->bind(GamblerService::class, function($app) {
            return new GamblerService(
                $app->make(SpinHandler::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        //
    }
}
