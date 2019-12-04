<?php

namespace App\Game\Core\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use League\Fractal\Manager;
use App\Game\Core\Services\EquipItemService;

class ServiceProvider extends ApplicationServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Manager::class, function($app) {
            return new Manager();
        });

        $this->app->bind(EquipItemService::class, function($app) {
            return new EquipItemService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }

    public function provides()
    {
        return [
            Manager::class,
            EquipItemService::class,
        ];
    }
}
