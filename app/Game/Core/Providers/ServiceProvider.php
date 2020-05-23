<?php

namespace App\Game\Core\Providers;

use App\Game\Core\Comparison\WeaponComparison;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use League\Fractal\Manager;
use App\Game\Core\Services\EquipItemService;

class ServiceProvider extends ApplicationServiceProvider
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

        $this->app->bind(WeaponComparison::class, function($app) {
            return new WeaponComparison();
        });

        $this->app->tag(WeaponComparison::class, 'weapon');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
