<?php

namespace App\Admin\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Admin\Middleware\IsAdminMiddleware;
use App\Admin\Services\AssignSkillService;
use App\Admin\Services\ItemAffixService;
use App\Admin\Services\UpdateCharacterStatsService;
use App\Admin\Services\UserService;

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
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        $router = $this->app['router'];

        $router->aliasMiddleware('is.admin', IsAdminMiddleware::class);
    }
}
