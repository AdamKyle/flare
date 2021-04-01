<?php

namespace App\Admin\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use ConsoleTVs\Charts\Registrar as Charts;
use App\Admin\Console\Commands\CreateAdminAccount;
use App\Admin\Middleware\IsAdminMiddleware;
use App\Admin\Services\AssignSkillService;
use App\Admin\Services\ItemAffixService;
use App\Admin\Services\UpdateCharacterStatsService;
use App\Admin\Services\UserService;
use App\Charts\BattleSimulationChart;

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

        $this->commands([CreateAdminAccount::class]);
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
            BattleSimulationChart::class,
        ]);

        $router->aliasMiddleware('is.admin', IsAdminMiddleware::class);
    }
}
