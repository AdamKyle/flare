<?php

namespace App\Game\NpcActions\LabyrinthOracle\Providers;

use App\Game\NpcActions\LabyrinthOracle\Services\ItemTransferService;
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

        $this->app->bind(ItemTransferService::class, function ($app) {
            return new ItemTransferService;
        });

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
