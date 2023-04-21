<?php

namespace App\Game\GuideQuests\Providers;

use App\Flare\Builders\RandomItemDropBuilder;
use App\Game\GuideQuests\Services\GuideQuestService;
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
        $this->app->bind(GuideQuestService::class, function($app) {
            return new GuideQuestService(
                $app->make(RandomItemDropBuilder::class)
            );
        });
        // @codeCoverageIgnoreEnd
    }
}
