<?php

namespace App\Game\Tops\Providers;

use App\Game\Tops\Console\Commands\SnapshotMonthlyTops;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SnapshotMonthlyTops::class,
            ]);
        }
    }
}
