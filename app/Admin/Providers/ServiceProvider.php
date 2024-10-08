<?php

namespace App\Admin\Providers;

use App\Admin\Console\Commands\CreateAdminAccount;
use App\Admin\Console\Commands\GiveKingdomsToNpcs;
use App\Admin\Middleware\IsAdminMiddleware;
use App\Admin\Services\AssignSkillService;
use App\Admin\Services\FeedbackService;
use App\Admin\Services\GuideQuestService;
use App\Admin\Services\InfoPageService;
use App\Admin\Services\ItemAffixService;
use App\Admin\Services\ItemsService;
use App\Admin\Services\LocationService;
use App\Admin\Services\QuestService;
use App\Admin\Services\SiteStatisticsService;
use App\Admin\Services\SuggestionAndBugsService;
use App\Admin\Services\SurveyService;
use App\Admin\Services\UpdateCharacterStatsService;
use App\Admin\Services\UserService;
use App\Flare\Cache\CoordinatesCache;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ItemAffixService::class, function ($app) {
            return new ItemAffixService;
        });

        $this->app->bind(AssignSkillService::class, function ($app) {
            return new AssignSkillService;
        });

        $this->app->bind(UpdateCharacterStatsService::class, function ($app) {
            return new UpdateCharacterStatsService;
        });

        $this->app->bind(UserService::class, function ($app) {
            return new UserService;
        });

        $this->app->bind(ItemsService::class, function ($app) {
            return new ItemsService;
        });

        $this->app->bind(QuestService::class, function () {
            return new QuestService;
        });

        $this->app->bind(InfoPageService::class, function () {
            return new InfoPageService;
        });

        $this->app->bind(GuideQuestService::class, function () {
            return new GuideQuestService;
        });

        $this->app->bind(LocationService::class, function ($app) {
            return new LocationService($app->make(CoordinatesCache::class));
        });

        $this->app->bind(SuggestionAndBugsService::class, function() {
            return new SuggestionAndBugsService;
        });

        $this->app->bind(SurveyService::class, function() {
            return new SurveyService;
        });

        $this->app->bind(FeedbackService::class, function() {
            return new FeedbackService;
        });

        $this->app->bind(SiteStatisticsService::class, function() {
            return new SiteStatisticsService;
        });

        $this->commands([CreateAdminAccount::class, GiveKingdomsToNpcs::class]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        $router = $this->app['router'];

        $router->aliasMiddleware('is.admin', IsAdminMiddleware::class);
    }
}
