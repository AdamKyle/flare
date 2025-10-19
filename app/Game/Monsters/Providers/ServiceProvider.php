<?php

namespace App\Game\Monsters\Providers;

use App\Game\Monsters\Console\Commands\CreateMonsterCache;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use App\Game\Monsters\Services\MonsterListService;
use App\Game\Monsters\Services\MonsterStatsService;
use App\Game\Monsters\Transformers\MonsterTransformer;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use League\Fractal\Manager;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->bind(MonsterListService::class, function () {
            return new MonsterListService;
        });

        $this->app->bind(BuildMonsterCacheService::class, function ($app) {
            return new BuildMonsterCacheService(
                $app->make(Manager::class),
                $app->make(MonsterTransformer::class),
            );
        });

        $this->app->bind(MonsterStatsService::class, function ($app) {
            return new MonsterStatsService(
                $app->make(MonsterListService::class),
            );
        });

        $this->commands([
            CreateMonsterCache::class,
        ]);
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
