<?php

namespace App\Flare\GameImporter\Providers;

use App\Flare\GameImporter\Console\Commands\ImportGameData;
use App\Flare\GameImporter\Values\ExcelMapper;
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
        $this->app->bind(ExcelMapper::class, function () {
            return new ExcelMapper;
        });

        $this->commands([
            ImportGameData::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
