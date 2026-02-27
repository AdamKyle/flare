<?php

namespace App\Flare\GameImporter\Providers;

use App\Flare\GameImporter\Console\Commands\ImportGameData;
use App\Flare\GameImporter\Console\Commands\MassImportCustomData;
use App\Flare\GameImporter\Values\ExcelMapper;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ExcelMapper::class, function () {
            return new ExcelMapper;
        });

        $this->commands([
            ImportGameData::class,
            MassImportCustomData::class,
        ]);
    }

    /**
     * @return void
     */
    public function boot() {}
}
