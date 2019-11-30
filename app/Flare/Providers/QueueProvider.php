<?php

namespace App\Flare\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use App\Flare\Workers\QueueWorker;

class QueueProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton('queue.worker', function () {
            $isDownForMaintenance = function () {
                return $this->app->isDownForMaintenance();
            };

            return new QueueWorker(
                $this->app['queue'], $this->app['events'], $this->app[ExceptionHandler::class], $isDownForMaintenance
            );
        });
    }
}
