<?php

namespace App\Game\Automation\Delve\Providers;

use App\Game\Automation\Delve\Services\DelveExplorationAutomationService;
use App\Game\Automation\Delve\Services\DelveStatusService;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Items\Transformers\ItemTransformer;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register the Delve automation services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(DelveExplorationAutomationService::class, function ($app) {
            return new DelveExplorationAutomationService(
                $app->make(CharacterCacheData::class),
            );
        });

        $this->app->bind(DelveStatusService::class, function ($app) {
            return new DelveStatusService($app->make(ItemTransformer::class));
        });
    }
}
