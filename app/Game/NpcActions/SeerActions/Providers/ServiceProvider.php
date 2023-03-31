<?php

namespace App\Game\NpcActions\SeerActions\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Core\Gems\Services\GemComparison;
use App\Game\NpcActions\SeerActions\Services\SeerService;

class ServiceProvider extends ApplicationServiceProvider {

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(SeerService::class, function($app) {
            return new SeerService($app->make(GemComparison::class));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
    }
}
