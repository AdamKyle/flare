<?php

namespace App\Game\ClassRanks\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\ClassRanks\Services\ClassRankService;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ClassRankService::class, function($app) {
            return new ClassRankService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // ...
    }
}
