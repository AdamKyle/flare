<?php

namespace App\Game\ClassRanks\Providers;

use App\Flare\Handlers\UpdateCharacterAttackTypes;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\ClassRanks\Services\ClassRankService;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ClassRankService::class, function($app) {
            return new ClassRankService(
                $app->make(UpdateCharacterAttackTypes::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // ...
    }
}
