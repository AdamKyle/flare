<?php

namespace App\Game\Character\Builders\StatDetailsBuilder\Providers;

use App\Game\Character\Builders\StatDetailsBuilder\StatModifierDetails;
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

        $this->app->bind(StatModifierDetails::class, function () {
            return new StatModifierDetails;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
