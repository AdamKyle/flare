<?php

namespace App\Game\ClassRanks\Providers;

use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Game\ClassRanks\Services\ManageClassService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
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

        $this->app->bind(ManageClassService::class, function($app) {
            return new ManageClassService(
                $app->make(UpdateCharacterAttackTypes::class),
                $app->make(UpdateCharacterSkillsService::class),
                $app->make(ClassRankService::class),
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
