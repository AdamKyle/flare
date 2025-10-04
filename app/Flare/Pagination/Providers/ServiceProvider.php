<?php

namespace App\Flare\Pagination\Providers;

use App\Flare\Pagination\Pagination;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use League\Fractal\Manager;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->bind(Pagination::class, function ($app) {
            return new Pagination(
                $app->make(Manager::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
