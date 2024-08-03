<?php

namespace App\Game\SpecialtyShops\Providers;

use App\Game\SpecialtyShops\Services\SpecialtyShop;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SpecialtyShop::class, function () {
            return new SpecialtyShop;
        });
    }
}
