<?php

namespace App\Game\PassiveSkills\Providers;

use App\Game\Core\Services\CharacterPassiveSkills;
use App\Game\PassiveSkills\Services\PassiveSkillTrainingService;
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
        // @codeCoverageIgnoreStart
        //
        // The test coverage never gets here.
        $this->app->bind(PassiveSkillTrainingService::class, function ($app) {
            return new PassiveSkillTrainingService($app->make(CharacterPassiveSkills::class));
        });
        // @codeCoverageIgnoreEnd
    }
}
