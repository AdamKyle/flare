<?php

namespace App\Admin\Providers;

use App\Admin\Requests\QuestManagement;
use App\Admin\Services\GuideQuestService;
use App\Admin\Services\InfoPageService;
use App\Admin\Services\ItemsService;
use App\Admin\Services\LocationService;
use App\Admin\Services\QuestService;
use App\Charts\AllCharacterGoldChart;
use App\Flare\Cache\CoordinatesCache;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use ConsoleTVs\Charts\Registrar as Charts;
use App\Admin\Console\Commands\CreateAdminAccount;
use App\Admin\Middleware\IsAdminMiddleware;
use App\Admin\Services\AssignSkillService;
use App\Admin\Services\ItemAffixService;
use App\Admin\Services\UpdateCharacterStatsService;
use App\Admin\Services\UserService;
use App\Admin\Console\Commands\GiveKingdomsToNpcs;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ItemAffixService::class, function ($app) {
            return new ItemAffixService();
        });

        $this->app->bind(AssignSkillService::class, function ($app) {
            return new AssignSkillService();
        });

        $this->app->bind(UpdateCharacterStatsService::class, function ($app) {
            return new UpdateCharacterStatsService();
        });

        $this->app->bind(UserService::class, function($app) {
            return new UserService();
        });

        $this->app->bind(ItemsService::class, function($app) {
            return new ItemsService();
        });

        $this->app->bind(QuestService::class, function() {
            return new QuestService();
        });

        $this->app->bind(InfoPageService::class, function() {
            return new InfoPageService();
        });

        $this->app->bind(GuideQuestService::class, function() {
            return new GuideQuestService();
        });

        $this->app->bind(LocationService::class, function($app) {
            return new LocationService($app->make(CoordinatesCache::class));
        });

        $this->commands([CreateAdminAccount::class, GiveKingdomsToNpcs::class]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(Charts $charts)
    {

        $router = $this->app['router'];

        $charts->register([
            AllCharacterGoldChart::class,
        ]);

        $router->aliasMiddleware('is.admin', IsAdminMiddleware::class);
    }
}
