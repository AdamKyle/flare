<?php

namespace App\Game\SpecialtyShops\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\SpecialtyShops\Services\SpecialtyShop;

class ServiceProvider extends ApplicationServiceProvider {

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void {
        $this->app->bind(SpecialtyShop::class, function() {
            return new SpecialtyShop;
        });
    }
}
