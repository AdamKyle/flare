<?php

namespace App\Game\Gambler\Providers;

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
        $this->app->bind(GamblerService::class, function($app) {
            return new GamblerService();
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
