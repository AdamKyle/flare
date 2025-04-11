<?php

namespace App\Flare\Pagination\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Flare\Pagination\Pagination;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->bind(Pagination::class, function () {
            return new Pagination;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
